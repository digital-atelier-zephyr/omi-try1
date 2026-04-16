<?php

namespace App\Services\LLM;

interface LLMAdapter
{
    public function name(): string;

    public function complete(string $prompt, array $messages = []): string;

    /**
     * Потоковая генерация ответа (SSE).
     * @param string $prompt Системный промпт
     * @param array $messages История сообщений
     * @param callable $onChunk Функция обратного вызова onChunk(string $text)
     * @return string Полный итоговый текст ответа
     */
    public function completeStream(string $prompt, array $messages, callable $onChunk): string;

    public function embed(string $text): array;

    public function maxTokens(): int;
}
