<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasFactory;

    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'privacy_last_seen', // 'everyone', 'contacts', 'nobody'
        'privacy_profile_photo', // 'everyone', 'contacts', 'nobody'
        'privacy_about', // 'everyone', 'contacts', 'nobody'
        'privacy_status_updates', // 'everyone', 'contacts', 'nobody'
        'read_receipts',
        'security_notifications',
        'two_factor_enabled',
        'notification_push',
        'notification_sounds',
        'notification_previews',
    ];

    protected $casts = [
        'read_receipts' => 'boolean',
        'security_notifications' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'notification_push' => 'boolean',
        'notification_sounds' => 'boolean',
        'notification_previews' => 'boolean',
    ];

    /**
     * User associated with these settings.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
