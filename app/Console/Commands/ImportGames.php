<?php

namespace App\Console\Commands;

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

        $fh = fopen($path, 'r');
        $header = fgetcsv($fh, 0, ',', '"', '');

        $count = 0;
        while (($row = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            $count++;
        }
        fclose($fh);

        $this->info('Kolom: ' . implode(', ', $header));
        $this->info("Jumlah baris data: $count");

        return self::SUCCESS;
    }
}