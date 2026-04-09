<?php

namespace App\Services\Memory;

use App\Models\Memory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Retriever
{
    /**
     * Получить релевантные знания (пока по текстовому поиску, позже — vector)
     */
    public function recall(string $query, int $limit = 5): Collection
    {
        // v1: текстовый поиск по title и content
        // v2: vector similarity search через pgvector
        $memories = Memory::active()
            ->where(function ($q) use ($query) {
                $q->where('title', 'ILIKE', "%{$query}%")
                    ->orWhere('content', 'ILIKE', "%{$query}%");
            })
            ->orderByDesc('relevance_score')
            ->limit($limit)
            ->get();

        // Touch access
        $memories->each(fn (Memory $m) => $m->touch_access());

        return $memories;
    }

    /**
     * Получить все pinned знания (всегда включаются в контекст)
     */
    public function pinned(): Collection
    {
        return Memory::pinned()->get();
    }

    /**
     * Собрать контекст для промпта
     */
    public function buildContext(string $query): string
    {
        $pinned = $this->pinned();
        $recalled = $this->recall($query);

        $context = '';

        if ($pinned->isNotEmpty()) {
            $context .= "## Закреплённые знания\n";
            foreach ($pinned as $memory) {
                $context .= "- {$memory->title}: {$memory->content}\n";
            }
            $context .= "\n";
        }

        if ($recalled->isNotEmpty()) {
            $context .= "## Релевантный контекст\n";
            foreach ($recalled as $memory) {
                $context .= "- {$memory->title}: {$memory->content}\n";
            }
        }

        return $context;
    }
}
