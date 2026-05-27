<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'type', // 'individual', 'group', 'channel'
        'status', // 'active', 'banned'
    ];

    /**
     * Users belonging to this channel.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'channel_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Messages inside this channel.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'channel_id');
    }

    /**
     * Group metadata if this channel is a group/channel chat.
     */
    public function groupMetadata()
    {
        return $this->hasOne(GroupMetadata::class, 'channel_id');
    }

    /**
     * The latest message in this channel.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'channel_id')->latestOfMany();
    }
}
