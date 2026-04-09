<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Episode extends Model
{
    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'embedding',
        'tokens_used',
        'model_used',
        'metadata',
        'consolidated',
    ];

    protected $casts = [
        'metadata' => 'array',
        'consolidated' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'chat_session_id');
    }
}
