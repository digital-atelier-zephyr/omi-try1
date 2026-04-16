<?php

use App\Http\Controllers\ChatStreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/chat/stream', [ChatStreamController::class, 'stream']);
