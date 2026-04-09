<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Memory extends Model
{
    protected $fillable = [
        'title',
        'content',
        'embedding',
        'relevance_score',
        'access_count',
        'source_episodes',
        'pinned',
        'faded',
        'last_accessed_at',
    ];

    protected $casts = [
        'source_episodes' => 'array',
        'pinned' => 'boolean',
        'faded' => 'boolean',
        'relevance_score' => 'float',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Recall: touch access count and timestamp
     */
    public function touch_access(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Scope: only active (not faded) memories
     */
    public function scopeActive($query)
    {
        return $query->where('faded', false);
    }

    /**
     * Scope: pinned memories (never forgotten)
     */
    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }
}
