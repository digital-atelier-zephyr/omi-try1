<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    protected $table = 'chat_sessions';

    protected $fillable = [
        'title',
        'summary',
        'system_prompt',
        'model_used',
        'episodes_count',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'chat_session_id');
    }
}
