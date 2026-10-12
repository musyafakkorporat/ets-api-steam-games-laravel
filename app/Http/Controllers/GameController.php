<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $query = Game::query();

        if ($request->filled('genre')) {
            $g = $request->query('genre');

            $query->where(function ($q) use ($g) {
                $q->where('genres', $g)
                  ->orWhere('genres', 'like', $g . ';%')
                  ->orWhere('genres', 'like', '%;' . $g)
                  ->orWhere('genres', 'like', '%;' . $g . ';%');
            });
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->query('max_price'));
        }

        if ($request->filled('year')) {
            $query->where('release_year', $request->query('year'));
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        return response()->json($query->orderBy('appid')->paginate($perPage));
    }

    public function search(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        if ($keyword === '') {
            return response()->json([
                'detail' => 'Validasi gagal',
                'errors' => ['q' => ['Parameter q wajib diisi']],
            ], 422);
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 100));

        $games = Game::where('name', 'like', '%' . $keyword . '%')
            ->orderBy('appid')
            ->paginate($perPage);

        return response()->json($games);
    }

    public function top(Request $request)
    {
        $limit = max(1, min((int) $request->query('limit', 10), 100));
        $minVotes = max(0, (int) $request->query('min_votes', 1000));

        $games = Game::whereRaw('positive_ratings + negative_ratings >= ?', [$minVotes])
            ->orderByDesc('positive_pct')
            ->orderByDesc('positive_ratings')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $games]);
    }

    public function show($appid)
    {
        $game = Game::find($appid);

        if (! $game) {
            return response()->json([
                'detail' => "Game dengan appid $appid tidak ditemukan",
            ], 404);
        }

        return response()->json($game);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'genres'           => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'release_date'     => 'nullable|date_format:Y-m-d',
            'developer'        => 'nullable|string|max:255',
            'publisher'        => 'nullable|string|max:255',
            'platforms'        => 'nullable|string|max:100',
            'positive_ratings' => 'nullable|integer|min:0',
            'negative_ratings' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $positive = $data['positive_ratings'] ?? 0;
        $negative = $data['negative_ratings'] ?? 0;
        $total = $positive + $negative;

        $game = Game::create([
            'appid'            => (Game::max('appid') ?? 0) + 1,
            'name'             => $data['name'],
            'release_date'     => $data['release_date'] ?? null,
            'release_year'     => isset($data['release_date'])
                ? (int) substr($data['release_date'], 0, 4)
                : null,
            'developer'        => $data['developer'] ?? 'Unknown',
            'publisher'        => $data['publisher'] ?? 'Unknown',
            'genres'           => $data['genres'],
            'platforms'        => $data['platforms'] ?? 'windows',
            'price'            => $data['price'],
            'is_free'          => ((float) $data['price']) == 0,
            'positive_ratings' => $positive,
            'negative_ratings' => $negative,
            'positive_pct'     => $total > 0 ? round($positive / $total * 100, 1) : null,
            'owners_min'       => 0,
            'owners_max'       => 0,
        ]);

        return response()->json($game, 201);
    }

    public function update(Request $request, $appid)
    {
        $game = Game::find($appid);

        if (! $game) {
            return response()->json([
                'detail' => "Game dengan appid $appid tidak ditemukan",
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|required|string|max:255',
            'genres'           => 'sometimes|required|string|max:255',
            'price'            => 'sometimes|required|numeric|min:0',
            'release_date'     => 'sometimes|nullable|date_format:Y-m-d',
            'developer'        => 'sometimes|required|string|max:255',
            'publisher'        => 'sometimes|required|string|max:255',
            'platforms'        => 'sometimes|required|string|max:100',
            'positive_ratings' => 'sometimes|required|integer|min:0',
            'negative_ratings' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'detail' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (array_key_exists('price', $data)) {
            $data['is_free'] = ((float) $data['price']) == 0;
        }

        if (array_key_exists('release_date', $data)) {
            $data['release_year'] = $data['release_date'] !== null
                ? (int) substr($data['release_date'], 0, 4)
                : null;
        }

        if (isset($data['positive_ratings']) || isset($data['negative_ratings'])) {
            $positive = $data['positive_ratings'] ?? $game->positive_ratings;
            $negative = $data['negative_ratings'] ?? $game->negative_ratings;
            $total = $positive + $negative;

            $data['positive_pct'] = $total > 0
                ? round($positive / $total * 100, 1)
                : null;
        }

        $game->update($data);

        return response()->json($game);
    }

    public function destroy($appid)
    {
        $game = Game::find($appid);

        if (! $game) {
            return response()->json([
                'detail' => "Game dengan appid $appid tidak ditemukan",
            ], 404);
        }

        $game->delete();

        return response()->json([
            'detail' => "Game dengan appid $appid berhasil dihapus",
        ]);
    }
}