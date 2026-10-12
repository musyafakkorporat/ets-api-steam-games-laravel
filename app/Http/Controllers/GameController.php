<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $games = Game::orderBy('appid')->paginate(20);

        return response()->json($games);
    }
}