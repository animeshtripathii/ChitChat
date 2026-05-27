<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMetadata extends Model
{
    use HasFactory;

    protected $table = 'group_metadata';

    protected $fillable = [
        'channel_id',
        'group_name',
        'group_description',
        'group_icon_url',
        'restrict_adjust_settings',
        'restrict_send_messages',
    ];

    protected $casts = [
        'restrict_adjust_settings' => 'boolean',
        'restrict_send_messages' => 'boolean',
    ];

    /**
     * Channel associated with this group metadata.
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }
}
