<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;

class OpenAIAdapter implements LLMAdapter
{
    private string $baseUrl = 'https://api.openai.com/v1';

    private string $model = 'gpt-4o-mini';

    public function name(): string
    {
        return 'openai';
    }

    public function complete(string $prompt, array $messages = []): string
    {
        $payload = [
            'model' => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $prompt]],
                $messages
            ),
            'temperature' => 0.7,
            'max_tokens' => 2048,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.openai.api_key'),
        ])->timeout(60)->post("{$this->baseUrl}/chat/completions", $payload);

        $response->throw();

        return $response->json('choices.0.message.content', '');
    }

    public function embed(string $text): array
    {
        // Эмбеддинги через отдельный EmbeddingService (Ollama)
        return [];
    }

    public function maxTokens(): int
    {
        return 128000;
    }
}
