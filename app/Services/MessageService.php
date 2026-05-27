<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use App\Models\Reaction;
use App\Events\MessageSent;
use App\Events\MessageStatusUpdated;
use App\Events\ReactionUpdated;
use Illuminate\Support\Facades\DB;
use Exception;

class MessageService
{
    protected $privacyService;
    protected $mediaUploadService;

    public function __construct(PrivacyService $privacyService, MediaUploadService $mediaUploadService)
    {
        $this->privacyService = $privacyService;
        $this->mediaUploadService = $mediaUploadService;
    }

    /**
     * Send a message to a channel (individual, group, or broadcast).
     */
    public function sendMessage(User $sender, int $channelId, array $data): Message
    {
        // 1. Fetch Channel and verify membership
        $channel = Channel::with('users')->findOrFail($channelId);
        $membership = $channel->users->first(fn($u) => $u->id === $sender->id);

        // Allow temporary users (guests) or system admins to bypass membership check
        if (!$membership && $sender->role !== 'temp' && $sender->role !== 'admin') {
            throw new Exception("You are not a member of this chat room.", 403);
        }

        // 2. Enforce channel-specific permissions
        if ($channel->type === 'individual') {
            $recipient = $channel->users->first(fn($u) => $u->id !== $sender->id);
            if ($recipient && $this->privacyService->isBlocked($sender->id, $recipient->id)) {
                throw new Exception("You cannot send messages to this contact.", 403);
            }
        } elseif ($channel->type === 'group') {
            // Group Chat Constraints
            $metadata = $channel->groupMetadata;
            $isAdmin = $membership && $membership->pivot->role === 'admin';
            if ($metadata && $metadata->restrict_send_messages && !$isAdmin && $sender->role !== 'admin') {
                throw new Exception("Only administrators are permitted to send messages in this group.", 403);
            }
        } elseif ($channel->type === 'channel') {
            // One-way Broadcast Channel Constraints: Only channel admins or system admins can write
            $isAdmin = $membership && $membership->pivot->role === 'admin';
            if (!$isAdmin && $sender->role !== 'admin') {
                throw new Exception("Only administrators are permitted to post messages in this broadcast channel.", 403);
            }
        }

        // 3. Handle media uploads if file exists in data
        $body = $data['body'] ?? '';
        $type = $data['type'] ?? 'text';
        $caption = $data['caption'] ?? null;

        if (isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
            $type = $this->resolveMediaType($data['file']);
            $body = $this->mediaUploadService->upload($data['file'], $type);
        }

        // 4. Create and persist message
        return DB::transaction(function () use ($channelId, $sender, $type, $body, $caption, $data) {
            $message = Message::create([
                'channel_id' => $channelId,
                'sender_id' => $sender->id,
                'type' => $type,
                'body' => $body,
                'caption' => $caption,
                'status' => 'sent',
                'parent_message_id' => $data['parent_message_id'] ?? null,
            ]);

            // Broadcast real-time WebSocket event
            broadcast(new MessageSent($message))->toOthers();

            return $message;
        });
    }

    /**
     * Update a single message status and broadcast the real-time event.
     */
    public function updateStatus(User $user, int $messageId, string $status): Message
    {
        $message = Message::findOrFail($messageId);

        // Check if user is a member of the channel
        $channel = Channel::with('users')->findOrFail($message->channel_id);
        if (!$channel->users->contains('id', $user->id) && $user->role !== 'temp' && $user->role !== 'admin') {
            throw new Exception("You are not a member of this chat room.", 403);
        }

        // Only recipients (not the sender) can update the status
        if ($message->sender_id === $user->id) {
            return $message;
        }

        // Ensure status sequence is linear (delivered -> read)
        if ($message->status === 'read' || ($message->status === 'delivered' && $status === 'delivered')) {
            return $message;
        }

        $message->status = $status;
        $message->save();

        broadcast(new MessageStatusUpdated($message))->toOthers();

        return $message;
    }

    /**
     * Soft delete a message ("Delete for Everyone").
     */
    public function deleteMessage(User $user, int $messageId): bool
    {
        $message = Message::findOrFail($messageId);

        if ($message->sender_id !== $user->id && $user->role !== 'admin') {
            throw new Exception("You are only authorized to delete your own messages.", 403);
        }

        // Perform Laravel Soft Delete
        return $message->delete();
    }

    /**
     * Toggle an emoji reaction on a message.
     */
    public function toggleReaction(User $user, int $messageId, string $emoji): array
    {
        $message = Message::findOrFail($messageId);
        
        // Verify user is in channel
        $channel = Channel::findOrFail($message->channel_id);
        if (!$channel->users->contains('id', $user->id) && $user->role !== 'temp' && $user->role !== 'admin') {
            throw new Exception("You are not authorized to react to messages in this chat.", 403);
        }

        $reaction = Reaction::where('message_id', $messageId)
            ->where('user_id', $user->id)
            ->first();

        if ($reaction) {
            if ($reaction->emoji === $emoji) {
                // Remove reaction if clicked same emoji
                $reaction->delete();
            } else {
                // Update to new emoji
                $reaction->update(['emoji' => $emoji]);
            }
        } else {
            // Add new reaction
            Reaction::create([
                'message_id' => $messageId,
                'user_id' => $user->id,
                'emoji' => $emoji
            ]);
        }

        // Fetch all reactions on the message, grouped by emoji
        $reactionsList = Reaction::where('message_id', $messageId)
            ->with('user')
            ->get()
            ->map(function ($r) {
                return [
                    'emoji' => $r->emoji,
                    'user_id' => $r->user_id,
                    'username' => $r->user->username
                ];
            })
            ->toArray();

        // Broadcast reaction updated event
        broadcast(new ReactionUpdated($messageId, $message->channel_id, $reactionsList))->toOthers();

        return $reactionsList;
    }

    /**
     * Forward a message to another channel.
     */
    public function forwardMessage(User $user, int $messageId, int $targetChannelId): Message
    {
        $message = Message::findOrFail($messageId);
        
        $payload = [
            'body' => $message->body,
            'type' => $message->type,
            'caption' => $message->caption,
            'parent_message_id' => null // Clear reply context on forward
        ];

        return $this->sendMessage($user, $targetChannelId, $payload);
    }

    /**
     * Resolve the WhatsApp message type enum from an uploaded file mime.
     */
    protected function resolveMediaType(\Illuminate\Http\UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }
}
