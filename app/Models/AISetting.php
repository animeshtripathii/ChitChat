<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AISetting extends Model
{
    use HasFactory;

    protected $table = 'ai_settings';

    protected $fillable = [
        'user_id',
        'is_auto_reply_enabled',
        'prompt_behavior',
        'tone',
        'summary_frequency',
    ];

    protected $casts = [
        'is_auto_reply_enabled' => 'boolean',
    ];

    /**
     * User associated with these AI settings.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
