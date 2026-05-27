<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Exception;

class ChannelService
{
    protected $privacyService;

    public function __construct(PrivacyService $privacyService)
    {
        $this->privacyService = $privacyService;
    }

    /**
     * Initiate an individual chat with a user by email or phone number.
     */
    public function initiateChat(User $user, string $identifier): Channel
    {
        // 1. Find target user by phone_number or email
        $targetUser = User::where('phone_number', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$targetUser) {
            throw new Exception("User identified by '{$identifier}' is not registered.", 404);
        }

        if ($user->id === $targetUser->id) {
            throw new Exception("You cannot initiate a chat with yourself.", 400);
        }

        // 2. Check if a blocked relation exists
        if ($this->privacyService->isBlocked($user->id, $targetUser->id)) {
            throw new Exception("You cannot initiate a chat with this user.", 403);
        }

        // 3. Check if an individual chat already exists between these users
        $existingChannel = Channel::where('type', 'individual')
            ->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->whereHas('users', function ($q) use ($targetUser) {
                $q->where('users.id', $targetUser->id);
            })
            ->first();

        if ($existingChannel) {
            return $existingChannel;
        }

        // 4. Create new channel and pivot entries
        return DB::transaction(function () use ($user, $targetUser) {
            $channel = Channel::create(['type' => 'individual']);

            // Attach current user and target user
            $channel->users()->attach([
                $user->id => ['role' => 'member', 'joined_at' => now()],
                $targetUser->id => ['role' => 'member', 'joined_at' => now()]
            ]);

            return $channel;
        });
    }

    /**
     * Retrieve all active individual and group channels for a user, ordered by the latest message.
     */
    public function getUserChannels(User $user)
    {
        // Fetch channels where the user is a member
        $query = Channel::whereHas('users', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        });

        // For temporary users, we also allow them to see groups/channels they explicitly joined
        // (they are joined in channel_user pivot, so whereHas is sufficient)

        return $query->with(['users', 'latestMessage', 'groupMetadata'])
        ->get()
        ->map(function ($channel) use ($user) {
            // Count unread messages (sender_id != current user and status != 'read')
            $unreadCount = Message::where('channel_id', $channel->id)
                ->where('sender_id', '!=', $user->id)
                ->where('status', '!=', 'read')
                ->count();

            // Enrich channel model with custom attributes
            $channel->unread_count = $unreadCount;

            // Apply visibility privacy constraints to individual chat contacts
            if ($channel->type === 'individual') {
                $recipient = $channel->users->first(fn($u) => $u->id !== $user->id);
                if ($recipient) {
                    // Filter details according to privacy matrix
                    if (!$this->privacyService->canViewProfilePhoto($recipient, $user)) {
                        $recipient->profile_picture_url = null;
                    }
                    if (!$this->privacyService->canViewAbout($recipient, $user)) {
                        $recipient->bio = null;
                    }
                    if (!$this->privacyService->canViewLastSeen($recipient, $user)) {
                        $recipient->status = 'offline';
                        $recipient->last_seen_at = null;
                    }
                }
            }

            return $channel;
        })
        // Sort by latest message timestamp (fallback to channel creation time if empty)
        ->sortByDesc(function ($channel) {
            return $channel->latestMessage ? $channel->latestMessage->created_at : $channel->created_at;
        })
        ->values();
    }
}
