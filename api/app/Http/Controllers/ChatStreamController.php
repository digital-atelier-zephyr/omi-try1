<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Services\LLM\DeepSeekAdapter;
use App\Services\LLM\EmbeddingService;
use App\Services\LLM\OpenAIAdapter;
use App\Services\Memory\EpisodicStore;
use App\Services\Memory\Retriever;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatStreamController extends Controller
{
    public function stream(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'chatSessionId' => 'nullable|uuid',
            'model' => 'nullable|string',
        ]);

        $content = $request->input('content');
        $chatSessionId = $request->input('chatSessionId');
        $modelName = $request->input('model', 'deepseek');

        // Отключаем буферизацию PHP
        if (ob_get_level()) {
            ob_end_clean();
        }

        return new StreamedResponse(function () use ($content, $chatSessionId, $modelName) {
            try {
                if ($chatSessionId) {
                    $session = Session::findOrFail($chatSessionId);
                } else {
                    $session = Session::create([
                        'title' => 'Новая сессия',
                        'system_prompt' => null,
                    ]);
                }

                $embedder = new EmbeddingService;
                $episodicStore = new EpisodicStore($embedder);
                $retriever = new Retriever($embedder);

                $llm = match ($modelName) {
                    'openai' => new OpenAIAdapter,
                    default => new DeepSeekAdapter,
                };

                // Сохраняем сообщение пользователя
                $episodicStore->save($session, 'user', $content);

                // Собираем историю (последние 10 сообщений)
                $history = $session->episodes()
                    ->latest()
                    ->take(10)
                    ->get()
                    ->reverse()
                    ->map(fn ($ep) => [
                        'role' => $ep->role,
                        'content' => $ep->content,
                    ])->toArray();

                // Ищем релевантный контекст (векторный поиск)
                $contextEntries = $retriever->recall($content, 5);
                $contextText = implode("\n", array_map(fn ($c) => $c->content, $contextEntries));

                $systemPrompt = $session->system_prompt ?: 'Ты полезный ИИ-ассистент.';
                if ($contextText !== '') {
                    $systemPrompt .= "\nКонтекст прошлых бесед:\n".$contextText;
                }

                // Вызываем LLM в потоке
                $assistantContent = $llm->completeStream($systemPrompt, $history, function ($chunk) {
                    // Отправляем SSE-chunk
                    $this->sendSSE('chunk', ['content' => $chunk]);
                });

                // Поток окончен, сохраняем ответ ассистента в БД
                $episode = $episodicStore->save($session, 'assistant', $assistantContent);

                $titleGenerated = false;

                // Авто-заголовок (если это первый обмен сообщениями)
                if (! $chatSessionId && $session->episodes()->count() === 2) {
                    try {
                        $titlePrompt = "Сгенерируй короткий заголовок (3-5 слов) для этого чата:\nUser: {$content}\nAssistant: {$assistantContent}\nВыведи только заголовок, без кавычек.";
                        $newTitle = $llm->complete($titlePrompt);
                        $session->update(['title' => trim($newTitle)]);
                        $titleGenerated = true;
                    } catch (\Exception $e) {
                        // Игнорируем ошибку генерации заголовка
                    }
                }

                // Отправляем финальный эвент с метаданными
                $this->sendSSE('done', [
                    'episodeId' => $episode->id,
                    'sessionId' => $session->id,
                    'title' => $titleGenerated ? $session->title : null,
                ]);

            } catch (\Exception $e) {
                // Если сломалось - красиво отправляем ошибку на фронт
                $this->sendSSE('error', ['message' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Отключить буферизацию в Nginx!
        ]);
    }

    private function sendSSE(string $event, array $data)
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
        flush();
    }
}
