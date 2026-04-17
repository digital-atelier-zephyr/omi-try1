<?php

namespace Tests\Feature;

use Tests\TestCase;

class LiveKitTokenTest extends TestCase
{
    public function test_livekit_token_returns_json(): void
    {
        config([
            'services.livekit.api_key' => 'test-key',
            'services.livekit.api_secret' => 'test-secret',
            'services.livekit.url' => 'wss://test.livekit.cloud',
        ]);

        $response = $this->postJson('/api/livekit/token');

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'url', 'room', 'identity']);
    }

    public function test_livekit_token_fails_without_config(): void
    {
        config([
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);

        $response = $this->postJson('/api/livekit/token');

        $response->assertStatus(500)
            ->assertJson(['error' => 'LiveKit not configured']);
    }
}
