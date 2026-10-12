<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;

class ImportGames extends Command
{
    protected $signature = 'games:import {file=database/data/steam_clean.csv}';

    protected $description = 'Impor data game dari file CSV ke tabel games';

    public function handle()
    {
        $path = base_path($this->argument('file'));

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: $path");
            return self::FAILURE;
        }

        Game::truncate();

        $fh = fopen($path, 'r');
        $header = fgetcsv($fh, 0, ',', '"', '');

        $batch = [];
        $total = 0;

        while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            $batch[] = $this->mapRow(array_combine($header, $row));

            if (count($batch) === 500) {
                Game::insert($batch);
                $total += count($batch);
                $batch = [];
            }
        }

        if ($batch) {
            Game::insert($batch);
            $total += count($batch);
        }

        fclose($fh);

        $this->info("Berhasil mengimpor $total game.");

        return self::SUCCESS;
    }

    private function mapRow(array $r): array
    {
        return [
            'appid'            => (int) $r['appid'],
            'name'             => $r['name'],
            'release_date'     => $r['release_date'] !== '' ? $r['release_date'] : null,
            'release_year'     => $r['release_year'] !== '' ? (int) $r['release_year'] : null,
            'developer'        => $r['developer'] !== '' ? $r['developer'] : 'Unknown',
            'publisher'        => $r['publisher'] !== '' ? $r['publisher'] : 'Unknown',
            'genres'           => $r['genres'],
            'platforms'        => $r['platforms'],
            'price'            => (float) $r['price'],
            'is_free'          => strtolower($r['is_free']) === 'true',
            'positive_ratings' => (int) $r['positive_ratings'],
            'negative_ratings' => (int) $r['negative_ratings'],
            'positive_pct'     => $r['positive_pct'] !== '' ? (float) $r['positive_pct'] : null,
            'owners_min'       => (int) $r['owners_min'],
            'owners_max'       => (int) $r['owners_max'],
        ];
    }
}