<?php

namespace App\Services\LLM;

use Illuminate\Support\Facades\Http;

class DeepSeekAdapter implements LLMAdapter
{
    private string $baseUrl = 'https://api.deepseek.com/v1';

    private string $model = 'deepseek-chat';

    public function name(): string
    {
        return 'deepseek';
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
            'Authorization' => 'Bearer '.config('services.deepseek.api_key'),
        ])->timeout(60)->post("{$this->baseUrl}/chat/completions", $payload);

        $response->throw();

        return $response->json('choices.0.message.content', '');
    }

    public function embed(string $text): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.deepseek.api_key'),
        ])->post("{$this->baseUrl}/embeddings", [
            'model' => 'deepseek-chat',
            'input' => $text,
        ]);

        $response->throw();

        return $response->json('data.0.embedding', []);
    }

    public function maxTokens(): int
    {
        return 64000;
    }
}
