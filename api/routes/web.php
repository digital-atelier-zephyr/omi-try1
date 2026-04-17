<?php

use App\Http\Controllers\ChatStreamController;
use App\Http\Controllers\LiveKitTokenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/chat/stream', [ChatStreamController::class, 'stream']);
Route::post('/api/livekit/token', [LiveKitTokenController::class, 'token']);
