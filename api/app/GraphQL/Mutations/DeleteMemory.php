<?php

namespace App\GraphQL\Mutations;

use App\Models\Memory;

class DeleteMemory
{
    public function __invoke(mixed $root, array $args): Memory
    {
        $memory = Memory::findOrFail($args['id']);
        $memory->delete();

        return $memory;
    }
}
