<?php

namespace App\Services\Memory;

use App\Models\Memory;
use App\Services\LLM\EmbeddingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Retriever
{
    public function __construct(private EmbeddingService $embedder) {}

    /**
     * Получить релевантные знания — vector similarity (cosine) через pgvector.
     * Fallback на ILIKE если Ollama недоступен.
     */
    public function recall(string $query, int $limit = 5): Collection
    {
        $embedding = $this->embedder->embed($query);

        if (!empty($embedding)) {
            // v2: vector cosine similarity ( 1 - cosine = distance, меньше = ближе )
            $vecSql = EmbeddingService::toSql($embedding);

            $memories = Memory::active()
                ->selectRaw('*, (embedding <=> ?) as distance', [$vecSql])
                ->whereNotNull('embedding')
                ->orderBy('distance')
                ->limit($limit)
                ->get();
        } else {
            // v1 fallback: текстовый поиск
            $memories = Memory::active()
                ->where(function ($q) use ($query) {
                    $q->where('title', 'ILIKE', "%{$query}%")
                        ->orWhere('content', 'ILIKE', "%{$query}%");
                })
                ->orderByDesc('relevance_score')
                ->limit($limit)
                ->get();
        }

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
