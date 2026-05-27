<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blocked_user_id',
    ];

    /**
     * The user who blocked the contact.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The contact who was blocked.
     */
    public function blockedUser()
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }
}
