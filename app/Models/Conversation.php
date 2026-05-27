<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'type', // 'direct', 'group', 'channel'
        'name',
        'description',
        'icon',
        'visibility',
        'who_can_send_messages',
        'member_visibility',
        'created_by',
        'status', // 'active', 'banned'
    ];

    protected $casts = [
        'member_visibility' => 'boolean',
    ];

    /**
     * Creator of the conversation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Users (members) in this conversation.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user', 'conversation_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Channel users (members) in this conversation if it is a channel.
     */
    public function channelUsers()
    {
        return $this->belongsToMany(User::class, 'channel_user', 'conversation_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Messages inside this conversation (for group & direct conversations).
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'group_id');
    }

    /**
     * The latest message in this conversation (for group & direct conversations).
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'group_id')->latestOfMany();
    }

    /**
     * Messages inside this conversation (for channel conversations).
     */
    public function channelMessages()
    {
        return $this->hasMany(Message::class, 'channel_id');
    }

    /**
     * The latest message in this conversation (for channel conversations).
     */
    public function latestChannelMessage()
    {
        return $this->hasOne(Message::class, 'channel_id')->latestOfMany();
    }

    /**
     * Get the other user in a direct conversation.
     */
    public function getOtherUser($currentUserId)
    {
        return $this->users()->where('users.id', '!=', $currentUserId)->first();
    }

    /**
     * Get unread messages count in this conversation for a specific user.
     */
    public function getUnreadCount($userId)
    {
        // Unread messages where sender is not current user and is_read is false
        if ($this->type === 'channel') {
            return $this->channelMessages()
                ->where('sender_id', '!=', $userId)
                ->where('is_read', false)
                ->count();
        }

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }
}
