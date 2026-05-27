<?php

namespace App\Services;

use App\Models\User;
use App\Models\Block;
use App\Models\Channel;

class PrivacyService
{
    /**
     * Check if a block exists between user A and user B.
     */
    public function isBlocked(int $userId, int $targetUserId): bool
    {
        return Block::where(function ($query) use ($userId, $targetUserId) {
            $query->where('user_id', $userId)
                  ->where('blocked_user_id', $targetUserId);
        })->orWhere(function ($query) use ($userId, $targetUserId) {
            $query->where('user_id', $targetUserId)
                  ->where('blocked_user_id', $userId);
        })->exists();
    }

    /**
     * Check if User A and User B are "contacts".
     * Defined as: they share an active individual chat with each other.
     */
    public function areContacts(User $user1, User $user2): bool
    {
        return Channel::where('type', 'individual')
            ->whereHas('users', function ($query) use ($user1) {
                $query->where('users.id', $user1->id);
            })
            ->whereHas('users', function ($query) use ($user2) {
                $query->where('users.id', $user2->id);
            })
            ->exists();
    }

    /**
     * Verify if the viewer is allowed to see the owner's field based on visibility rules.
     */
    public function canViewField(User $owner, User $viewer, string $settingField): bool
    {
        // Blocked users cannot see anything (Last Seen, Profile Photo, About, etc.)
        if ($this->isBlocked($owner->id, $viewer->id)) {
            return false;
        }

        // Fetch owner settings, default if not set
        $settings = $owner->settings;
        if (!$settings) {
            return true; // Default to 'everyone'
        }

        $visibility = $settings->$settingField; // 'everyone', 'contacts', 'nobody'

        if ($visibility === 'everyone') {
            return true;
        }

        if ($visibility === 'contacts') {
            return $this->areContacts($owner, $viewer);
        }

        return false; // 'nobody'
    }

    /**
     * Can viewer see owner's Last Seen and Online status?
     */
    public function canViewLastSeen(User $owner, User $viewer): bool
    {
        return $this->canViewField($owner, $viewer, 'privacy_last_seen');
    }

    /**
     * Can viewer see owner's Profile Photo?
     */
    public function canViewProfilePhoto(User $owner, User $viewer): bool
    {
        return $this->canViewField($owner, $viewer, 'privacy_profile_photo');
    }

    /**
     * Can viewer see owner's About text?
     */
    public function canViewAbout(User $owner, User $viewer): bool
    {
        return $this->canViewField($owner, $viewer, 'privacy_about');
    }
}
