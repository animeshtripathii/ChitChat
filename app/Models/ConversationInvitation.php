<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationInvitation extends Model
{
    use HasFactory;

    protected $table = 'conversation_invitations';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'invited_by',
        'status',
    ];

    /**
     * The conversation being invited to.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * The user being invited.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The user who invited.
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
