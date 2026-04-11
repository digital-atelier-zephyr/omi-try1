<?php

namespace App\GraphQL\Mutations;

use App\Models\Episode;
use App\Models\Session;
use App\Services\LLM\DeepSeekAdapter;
use App\Services\LLM\EmbeddingService;
use App\Services\Memory\EpisodicStore;
use App\Services\Memory\Retriever;

class SendMessage
{
    public function __invoke(mixed $root, array $args): Episode
    {
        $content = $args['content'];
        $sessionId = $args['chatSessionId'] ?? null;
        $model = $args['model'] ?? 'deepseek';

        // Получаем или создаём сессию
        $session = $sessionId
            ? Session::findOrFail($sessionId)
            : Session::create(['started_at' => now(), 'model_used' => $model]);

        $embedder = new EmbeddingService;
        $episodicStore = new EpisodicStore($embedder);
        $retriever = new Retriever($embedder);
        $llm = new DeepSeekAdapter;

        // 1. Сохраняем сообщение пользователя
        $episodicStore->save($session, 'user', $content);

        // 2. Достаём контекст из памяти
        $memoryContext = $retriever->buildContext($content);

        // 3. Собираем system prompt
        $systemPrompt = "Ты — AI ассистент с памятью. Отвечай кратко и по делу.\n\n";
        if ($memoryContext) {
            $systemPrompt .= "Вот что ты помнишь:\n{$memoryContext}\n";
        }

        // 4. Собираем историю диалога
        $history = $episodicStore->recent($session, 20);

        // 5. Отправляем LLM
        $response = $llm->complete($systemPrompt, $history);

        // 6. Сохраняем ответ
        $episode = $episodicStore->save($session, 'assistant', $response, $model);

        return $episode;
    }
}
