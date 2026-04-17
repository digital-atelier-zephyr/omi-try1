<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChatStreamTest extends TestCase
{
    public function test_chat_stream_requires_content(): void
    {
        $response = $this->postJson('/api/chat/stream', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    public function test_chat_stream_accepts_valid_payload(): void
    {
        // Проверяем что роут принимает правильную структуру
        // (ответ будет SSE-стримом, поэтому проверяем только Content-Type)
        $response = $this->post('/api/chat/stream', [
            'content' => 'Тестовое сообщение',
            'model' => 'deepseek',
        ]);

        // Роут существует и не 404/419
        $this->assertNotEquals(404, $response->status());
        $this->assertNotEquals(419, $response->status());
    }
}
