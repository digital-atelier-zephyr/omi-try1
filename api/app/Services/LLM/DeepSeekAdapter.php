<?php

namespace App\Services\LLM;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Utils;
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

        $client = new Client(['timeout' => 120]);

        try {
            $response = $client->post("$this->baseUrl/chat/completions", [
                'headers' => [
                    'Authorization' => 'Bearer '.config('services.deepseek.api_key'),
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream',
                ],
                'json' => $payload,
                'stream' => true,
            ]);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $body['error']['message'] ?? $e->getMessage();
            throw new \Exception('DeepSeek Error: '.$message);
        }

        $body = $response->getBody();
        $fullText = '';

        while (! $body->eof()) {
            $line = Utils::readLine($body);

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
