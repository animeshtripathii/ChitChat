<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'status_message',
        'role', // 'user', 'admin'
        'password',
        'status', // 'online', 'offline'
        'last_seen_at',
        'public_key',
        'two_factor_pin',
        
        // old compatibility fields
        'username',
        'phone_number',
        'profile_picture_url',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_pin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * AI co-pilot settings of the user.
     */
    public function aiSettings()
    {
        return $this->hasOne(AISetting::class, 'user_id');
    }

    /**
     * Group conversations this user belongs to.
     */
    public function groupConversations()
    {
        return $this->belongsToMany(Conversation::class, 'group_user', 'user_id', 'conversation_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Channel conversations this user belongs to.
     */
    public function channelConversations()
    {
        return $this->belongsToMany(Conversation::class, 'channel_user', 'user_id', 'conversation_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Chats/Channels that this user belongs to (compatibility relation).
     */
    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'channel_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Messages sent by this user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Messages received by this user.
     */
    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Granular privacy settings of the user.
     */
    public function settings()
    {
        return $this->hasOne(UserSetting::class, 'user_id');
    }

    /**
     * Users blocked by this user.
     */
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocks', 'user_id', 'blocked_user_id')
            ->withTimestamps();
    }

    /**
     * Users who blocked this user.
     */
    public function blockedByUsers()
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocked_user_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Active statuses/stories created by this user.
     */
    public function statuses()
    {
        return $this->hasMany(Status::class, 'user_id');
    }

    /**
     * Return a privacy-respecting public profile array for display to OTHER users.
     * Masks avatar, status, and last_seen_at based on the user's privacy settings.
     *
     * @return array
     */
    public function getPublicProfile(): array
    {
        // Eager-load settings if not already loaded
        $settings = $this->relationLoaded('settings')
            ? $this->settings
            : $this->settings()->first();

        // Defaults: show everything if no settings row yet
        $showAvatar   = true;
        $showLastSeen = true;
        $showStatus   = true;

        if ($settings) {
            $photoPrivacy    = strtolower($settings->privacy_profile_photo ?? 'everyone');
            $lastSeenPrivacy = strtolower($settings->privacy_last_seen    ?? 'everyone');

            // 'nobody' OR 'my contacts' (we treat contacts same as nobody for now)
            $showAvatar   = $photoPrivacy    === 'everyone';
            $showLastSeen = $lastSeenPrivacy === 'everyone';
            $showStatus   = $lastSeenPrivacy === 'everyone'; // status also follows last-seen rule
        }

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'avatar'         => $showAvatar   ? $this->avatar         : null,
            'status'         => $showStatus   ? $this->status         : 'offline',
            'status_message' => $this->status_message, // bio is always public
            'last_seen_at'   => $showLastSeen ? ($this->last_seen_at?->toIso8601String()) : null,
        ];
    }
}
