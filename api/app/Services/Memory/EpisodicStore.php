<?php

namespace App\Services\Memory;

use App\Models\Episode;
use App\Models\Session;

class EpisodicStore
{
    /**
     * Записать сообщение в эпизодическую память
     */
    public function save(Session $session, string $role, string $content, ?string $model = null, ?int $tokens = null): Episode
    {
        $episode = $session->episodes()->create([
            'role' => $role,
            'content' => $content,
            'model_used' => $model,
            'tokens_used' => $tokens,
        ]);

        $session->increment('episodes_count');

        return $episode;
    }

    /**
     * Получить последние эпизоды сессии
     */
    public function recent(Session $session, int $limit = 20): array
    {
        return $session->episodes()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Episode $e) => [
                'role' => $e->role,
                'content' => $e->content,
            ])
            ->toArray();
    }
}
