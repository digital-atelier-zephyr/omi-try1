<?php

namespace App\GraphQL\Queries;

use App\Services\Memory\Retriever;
use Illuminate\Support\Collection;

class RecallQuery
{
    public function __invoke(mixed $root, array $args): Collection
    {
        $retriever = new Retriever;

        return $retriever->recall(
            $args['query'],
            $args['limit'] ?? 5
        );
    }
}
