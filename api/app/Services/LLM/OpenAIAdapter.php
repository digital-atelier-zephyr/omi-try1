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

    public function completeStream(string $prompt, array $messages, callable $onChunk): string
    {
        $payload = [
            'model' => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $prompt]],
                $messages
            ),
            'stream' => true,
        ];

        $client = new \GuzzleHttp\Client(['timeout' => 120]);

        try {
            $response = $client->post("{$this->baseUrl}/chat/completions", [
                'headers' => [
                    'Authorization' => 'Bearer '.config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream',
                ],
                'json' => $payload,
                'stream' => true,
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $body['error']['message'] ?? $e->getMessage();
            throw new \Exception("OpenAI Error: " . $message);
        }

        $body = $response->getBody();
        $fullText = '';

        while (!$body->eof()) {
            $line = \GuzzleHttp\Psr7\Utils::readLine($body);

            if (str_starts_with($line, 'data: ')) {
                $data = substr($line, 6);
                if (trim($data) === '[DONE]') {
                    break;
                }

                $chunk = json_decode($data, true);
                $text = $chunk['choices'][0]['delta']['content'] ?? '';

                if ($text !== '') {
                    $fullText .= $text;
                    $onChunk($text);
                }
            }
        }

        return $fullText;
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
