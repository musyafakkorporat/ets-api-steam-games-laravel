<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Steam Store Games API',
    ]);
});

Route::get('/games', [GameController::class, 'index']);