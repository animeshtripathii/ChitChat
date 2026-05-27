<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\GroupMetadata;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Exception;

class GroupService
{
    protected $mediaUploadService;

    public function __construct(MediaUploadService $mediaUploadService)
    {
        $this->mediaUploadService = $mediaUploadService;
    }

    /**
     * Create a group chat/channel with custom metadata and attach starting members.
     */
    public function createGroup(User $creator, string $name, ?string $description, $iconFile, array $memberIds, string $type = 'group'): Channel
    {
        return DB::transaction(function () use ($creator, $name, $description, $iconFile, $memberIds, $type) {
            // 1. Create the base Channel (type 'group' or 'channel')
            $channel = Channel::create(['type' => $type]);

            // 2. Upload Group Icon if provided
            $iconUrl = null;
            if ($iconFile && $iconFile instanceof \Illuminate\Http\UploadedFile) {
                $iconUrl = $this->mediaUploadService->upload($iconFile, 'group_icons');
            }

            // 3. Create Group Metadata
            GroupMetadata::create([
                'channel_id' => $channel->id,
                'group_name' => $name,
                'group_description' => $description,
                'group_icon_url' => $iconUrl,
                'restrict_adjust_settings' => ($type === 'channel'), // for channel, restrict by default
                'restrict_send_messages' => ($type === 'channel'),   // for channel, restrict by default
            ]);

            // 4. Attach members. Add creator as admin, others as members
            $attachments = [];
            $attachments[$creator->id] = ['role' => 'admin', 'joined_at' => now()];

            // Ensure unique members list (filter out creator if present)
            $uniqueMembers = array_unique(array_filter($memberIds, fn($id) => $id !== $creator->id));
            foreach ($uniqueMembers as $memberId) {
                $attachments[$memberId] = ['role' => 'member', 'joined_at' => now()];
            }

            $channel->users()->attach($attachments);

            // 5. Create system announcement message
            Message::create([
                'channel_id' => $channel->id,
                'sender_id' => $creator->id,
                'type' => 'text',
                'body' => "{$creator->username} created " . ($type === 'channel' ? 'channel' : 'group') . " \"{$name}\"",
                'status' => 'sent',
            ]);

            return $channel->load(['users', 'groupMetadata']);
        });
    }

    /**
     * Add new members to a group/channel chat (Admin only).
     */
    public function addMembers(User $admin, int $groupId, array $memberIds): void
    {
        $channel = Channel::findOrFail($groupId);
        $this->verifyAdminRole($admin, $channel);

        $attachments = [];
        $addedUsernames = [];

        foreach ($memberIds as $memberId) {
            // Check if already a member
            if ($channel->users->contains('id', $memberId)) {
                continue;
            }

            $attachments[$memberId] = ['role' => 'member', 'joined_at' => now()];
            $user = User::find($memberId);
            if ($user) {
                $addedUsernames[] = $user->username;
            }
        }

        if (count($attachments) > 0) {
            $channel->users()->attach($attachments);

            // Create announcement
            $usernamesStr = implode(', ', $addedUsernames);
            Message::create([
                'channel_id' => $channel->id,
                'sender_id' => $admin->id,
                'type' => 'text',
                'body' => "{$admin->username} added {$usernamesStr}",
                'status' => 'sent',
            ]);
        }
    }

    /**
     * Kick a member from a group/channel chat (Admin only).
     */
    public function kickMember(User $admin, int $groupId, int $userId): void
    {
        $channel = Channel::findOrFail($groupId);
        $this->verifyAdminRole($admin, $channel);

        if (!$channel->users->contains('id', $userId)) {
            throw new Exception("This user is not a member of the group.", 404);
        }

        if ($admin->id === $userId) {
            throw new Exception("You cannot kick yourself from the group.", 400);
        }

        $kickedUser = User::findOrFail($userId);
        $channel->users()->detach($userId);

        // Create announcement
        Message::create([
            'channel_id' => $channel->id,
            'sender_id' => $admin->id,
            'type' => 'text',
            'body' => "{$admin->username} removed {$kickedUser->username}",
            'status' => 'sent',
        ]);
    }

    /**
     * Adjust group permissions (Admin only).
     */
    public function updatePermissions(User $admin, int $groupId, array $permissions): GroupMetadata
    {
        $channel = Channel::findOrFail($groupId);
        $this->verifyAdminRole($admin, $channel);

        $metadata = GroupMetadata::where('channel_id', $groupId)->firstOrFail();

        if (isset($permissions['restrict_adjust_settings'])) {
            $metadata->restrict_adjust_settings = (bool) $permissions['restrict_adjust_settings'];
        }

        if (isset($permissions['restrict_send_messages'])) {
            $metadata->restrict_send_messages = (bool) $permissions['restrict_send_messages'];
        }

        $metadata->save();

        return $metadata;
    }

    /**
     * Verify that the user acts as an admin of the specified group/channel.
     */
    protected function verifyAdminRole(User $user, Channel $channel): void
    {
        if ($channel->type !== 'group' && $channel->type !== 'channel') {
            throw new Exception("This chat is not a group or channel.", 400);
        }

        // System admin has bypass role
        if ($user->role === 'admin') {
            return;
        }

        $membership = $channel->users()->where('users.id', $user->id)->first();

        if (!$membership || $membership->pivot->role !== 'admin') {
            throw new Exception("Unauthorized. You must be a group administrator to perform this action.", 403);
        }
    }
}
