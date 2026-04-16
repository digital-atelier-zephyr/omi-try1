<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatStreamController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/chat/stream', [ChatStreamController::class, 'stream']);
