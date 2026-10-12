<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function genre()
    {
        $stats = [];

        foreach (Game::select('genres', 'price', 'positive_pct')->cursor() as $game) {
            foreach (explode(';', $game->genres) as $genre) {
                $genre = trim($genre);

                if ($genre === '') {
                    continue;
                }

                $stats[$genre] ??= [
                    'genre' => $genre,
                    'total_games' => 0,
                    'price_sum' => 0.0,
                    'pct_sum' => 0.0,
                    'pct_count' => 0,
                ];

                $stats[$genre]['total_games']++;
                $stats[$genre]['price_sum'] += $game->price;

                if ($game->positive_pct !== null) {
                    $stats[$genre]['pct_sum'] += $game->positive_pct;
                    $stats[$genre]['pct_count']++;
                }
            }
        }

        $result = collect($stats)->map(fn ($s) => [
            'genre' => $s['genre'],
            'total_games' => $s['total_games'],
            'avg_price' => round($s['price_sum'] / $s['total_games'], 2),
            'avg_positive_pct' => $s['pct_count'] > 0
                ? round($s['pct_sum'] / $s['pct_count'], 1)
                : null,
        ])->sortByDesc('total_games')->values();

        return response()->json(['data' => $result]);
    }

    public function year()
    {
        $rows = Game::query()
            ->whereNotNull('release_year')
            ->select(
                'release_year',
                DB::raw('COUNT(*) as total_games'),
                DB::raw('ROUND(AVG(price), 2) as avg_price'),
                DB::raw('ROUND(AVG(positive_pct), 1) as avg_positive_pct')
            )
            ->groupBy('release_year')
            ->orderBy('release_year')
            ->get()
            ->map(fn ($r) => [
                'release_year' => $r->release_year,
                'total_games' => (int) $r->total_games,
                'avg_price' => (float) $r->avg_price,
                'avg_positive_pct' => $r->avg_positive_pct !== null
                    ? (float) $r->avg_positive_pct
                    : null,
            ]);

        return response()->json(['data' => $rows]);
    }
}