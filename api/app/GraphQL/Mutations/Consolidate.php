<?php

namespace App\GraphQL\Mutations;

class Consolidate
{
    public function __invoke(mixed $root, array $args): array
    {
        // v1: заглушка — будет вызывать LLM для суммаризации
        // v2: полноценный Consolidation Engine

        return [];
    }
}
