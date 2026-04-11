<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.url', 'http://host.docker.internal:11434');
        $this->model = config('services.ollama.embed_model', 'nomic-embed-text-v2-moe');
    }

    /**
     * Generate embedding vector for a text string.
     * Returns float[] of 768 dimensions, or empty array on failure.
     */
    public function embed(string $text): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/api/embeddings", [
                'model' => $this->model,
                'prompt' => $text,
            ]);

            $response->throw();

            return $response->json('embedding', []);
        } catch (\Throwable $e) {
            Log::warning('EmbeddingService: failed to generate embedding', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);

            return [];
        }
    }

    /**
     * Convert float[] to pgvector string format: '[0.1, 0.2, ...]'
     */
    public static function toSql(array $embedding): string
    {
        return '[' . implode(',', $embedding) . ']';
    }
}
