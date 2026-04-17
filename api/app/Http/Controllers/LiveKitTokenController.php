<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LiveKitTokenController extends Controller
{
    public function token(Request $request)
    {
        $apiKey = config('services.livekit.api_key');
        $apiSecret = config('services.livekit.api_secret');

        if (! $apiKey || ! $apiSecret) {
            return response()->json(['error' => 'LiveKit not configured'], 500);
        }

        $identity = 'user-'.uniqid();
        $roomName = 'omi-voice-'.($request->input('sessionId', 'default'));

        $header = $this->base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

        $now = time();
        $payload = $this->base64url(json_encode([
            'iss' => $apiKey,
            'sub' => $identity,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'jti' => $identity.'-'.$now,
            'video' => [
                'room' => $roomName,
                'roomJoin' => true,
                'canPublish' => true,
                'canSubscribe' => true,
                'canPublishData' => true,
            ],
            'roomConfig' => [
                'agents' => [
                    ['agentName' => 'omi-agent'],
                ],
            ],
            'metadata' => json_encode(['name' => 'OMI User']),
            'name' => 'OMI User',
        ]));

        $signature = $this->base64url(
            hash_hmac('sha256', "$header.$payload", $apiSecret, true)
        );

        return response()->json([
            'token' => "$header.$payload.$signature",
            'url' => config('services.livekit.url'),
            'room' => $roomName,
            'identity' => $identity,
        ]);
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
