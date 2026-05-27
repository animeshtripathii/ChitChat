<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AILog extends Model
{
    use HasFactory;

    protected $table = 'ai_logs';

    protected $fillable = [
        'user_id',
        'model',
        'status',
        'latency_ms',
        'tokens_used',
        'cost',
        'prompt',
        'response',
    ];

    /**
     * User associated with the AI log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
