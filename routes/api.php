<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Steam Store Games API',
    ]);
});

Route::get('/games', [GameController::class, 'index']);
Route::post('/games', [GameController::class, 'store']);
Route::get('/games/search', [GameController::class, 'search']);
Route::get('/games/top', [GameController::class, 'top']);
Route::get('/games/{appid}', [GameController::class, 'show'])->whereNumber('appid');
Route::put('/games/{appid}', [GameController::class, 'update'])->whereNumber('appid');
Route::delete('/games/{appid}', [GameController::class, 'destroy'])->whereNumber('appid');

Route::get('/stats/genre', [StatsController::class, 'genre']);
Route::get('/stats/year', [StatsController::class, 'year']);