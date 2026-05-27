<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'group_id',
        'channel_id',
        'body',
        'type', // 'text', 'media', 'audio'
        'mentions', // JSON array of { id, name } objects
        'is_read',
        'read_at',
        'status', // for compatibility
        'caption',
        'is_pinned',
        'parent_message_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_pinned' => 'boolean',
        'read_at' => 'datetime',
        'mentions' => 'array',
    ];

    /**
     * Sender of this message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Receiver of this message (for direct chats).
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Conversation group of this message.
     */
    public function group()
    {
        return $this->belongsTo(Conversation::class, 'group_id');
    }

    /**
     * Conversation channel of this message.
     */
    public function channel()
    {
        return $this->belongsTo(Conversation::class, 'channel_id');
    }

    /**
     * The message that this message replies to.
     */
    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    /**
     * Replies sent to this message.
     */
    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }

    /**
     * Reactions on this message.
     */
    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }
}
