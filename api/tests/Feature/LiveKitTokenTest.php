<?php

namespace Tests\Feature;

use Tests\TestCase;

class LiveKitTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Фейковые ключи для теста
        config([
            'services.livekit.api_key' => 'test-api-key',
            'services.livekit.api_secret' => 'test-secret-key-minimum-32-chars!!',
            'services.livekit.url' => 'wss://test.livekit.cloud',
        ]);
    }

    public function test_token_endpoint_returns_valid_structure(): void
    {
        $response = $this->postJson('/api/livekit/token', []);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'url', 'room', 'identity']);
    }

    public function test_token_contains_agent_dispatch(): void
    {
        $response = $this->postJson('/api/livekit/token', ['sessionId' => 'test-123']);

        $data = $response->json();
        $token = $data['token'];

        // Декодим JWT payload (вторая часть, base64url)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT должен состоять из 3 частей');

        $payload = json_decode(
            base64_decode(strtr($parts[1], '-_', '+/')),
            true
        );

        // Проверяем video grant
        $this->assertArrayHasKey('video', $payload);
        $this->assertEquals('omi-voice-test-123', $payload['video']['room']);
        $this->assertTrue($payload['video']['roomJoin']);

        // Проверяем agent dispatch (roomConfig на верхнем уровне JWT, НЕ внутри video)
        $this->assertArrayNotHasKey('roomConfig', $payload['video'], 'roomConfig НЕ должен быть внутри video');
        $this->assertArrayHasKey('roomConfig', $payload, 'roomConfig должен быть на верхнем уровне JWT');
        $this->assertArrayHasKey('agents', $payload['roomConfig']);
        $this->assertEquals('omi-agent', $payload['roomConfig']['agents'][0]['agentName']);
    }

    public function test_token_uses_default_room_name(): void
    {
        $response = $this->postJson('/api/livekit/token', []);
        $data = $response->json();

        $this->assertEquals('omi-voice-default', $data['room']);
    }

    public function test_returns_error_without_config(): void
    {
        config(['services.livekit.api_key' => null]);

        $response = $this->postJson('/api/livekit/token', []);
        $response->assertStatus(500);
    }
}
