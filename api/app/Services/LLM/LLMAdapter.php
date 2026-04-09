<?php

namespace App\Services\LLM;

interface LLMAdapter
{
    public function name(): string;

    public function complete(string $prompt, array $messages = []): string;

    public function embed(string $text): array;

    public function maxTokens(): int;
}
