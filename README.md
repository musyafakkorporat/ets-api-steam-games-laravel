# Steam Games API

REST API untuk mencari, menyaring, dan melihat statistik game Steam berdasarkan genre, harga, dan rating. Dibuat dengan Laravel 12 dan MySQL sebagai tugas ETS Pemrograman API.

## Dataset

[Steam Store Games (Clean dataset)](https://www.kaggle.com/datasets/nikdavis/steam-store-games) dari Kaggle (nikdavis), berisi 27.075 game (data sekitar Mei 2019).

Data sudah dibersihkan sebelum dipakai: duplikat dibuang berdasarkan `appid`, developer dan publisher yang kosong diisi `Unknown`, tanggal dinormalkan, kolom `owners` (rentang) dipecah menjadi `owners_min` dan `owners_max`, serta ditambahkan kolom turunan `positive_pct` dan `is_free`. Kolom yang tidak dipakai dibuang. Hasilnya ada di `database/data/steam_clean.csv`.

## Teknologi

PHP 8.2, Laravel 12, MySQL/MariaDB (XAMPP), Postman.

## Cara menjalankan

Syarat: XAMPP (MySQL menyala), PHP 8.2 atau lebih baru, Composer.

```
git clone https://github.com/musyafakkorporat/ets-api-steam-games-laravel.git
cd ets-api-steam-games-laravel
composer install
copy .env.example .env
php artisan key:generate
```

Buat database kosong lewat phpMyAdmin:

```sql
CREATE DATABASE steam_learn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Atur koneksi di file `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=steam_learn
DB_USERNAME=root
DB_PASSWORD=
```

Buat tabel, isi data, lalu jalankan server:

```
php artisan migrate
php artisan games:import
php artisan serve
```

API berjalan di `http://127.0.0.1:8000/api`.

## Endpoint

| Method | URL | Fungsi | Status |
| --- | --- | --- | --- |
| GET | `/api/games` | Daftar game dengan filter dan pagination | 200 |
| GET | `/api/games/search?q=` | Cari game berdasarkan nama | 200, 422 |
| GET | `/api/games/top` | Game dengan rating tertinggi | 200 |
| GET | `/api/games/{appid}` | Detail satu game | 200, 404 |
| POST | `/api/games` | Tambah game | 201, 422 |
| PUT | `/api/games/{appid}` | Ubah game (boleh sebagian kolom) | 200, 404, 422 |
| DELETE | `/api/games/{appid}` | Hapus game | 200, 404 |
| GET | `/api/stats/genre` | Statistik per genre | 200 |
| GET | `/api/stats/year` | Statistik per tahun rilis | 200 |

### Parameter

**`GET /api/games`**: `genre`, `min_price`, `max_price`, `year`, `per_page` (1 sampai 100, bawaan 20).
Contoh: `/api/games?genre=RPG&min_price=10&max_price=30&per_page=5`

**`GET /api/games/search`**: `q` (wajib), `per_page`.

**`GET /api/games/top`**: `limit` (1 sampai 100, bawaan 10), `min_votes` (minimal jumlah ulasan, bawaan 1000).

**`POST /api/games`** (body JSON): `name`, `genres`, `price` wajib. Opsional: `release_date` (format `YYYY-MM-DD`), `developer`, `publisher`, `platforms`, `positive_ratings`, `negative_ratings`. Kolom `appid`, `release_year`, `is_free`, dan `positive_pct` dihitung otomatis.

```json
{
  "name": "Game Belajar Saya",
  "genres": "Indie;Casual",
  "price": 4.99,
  "release_date": "2026-10-12",
  "positive_ratings": 80,
  "negative_ratings": 20
}
```

### Contoh respons galat

`404` (game tidak ada):
```json
{"detail": "Game dengan appid 999999999 tidak ditemukan"}
```

`422` (data tidak valid):
```json
{"detail": "Validasi gagal", "errors": {"price": ["The price field must be at least 0."]}}
```

## Pengujian

Impor `postman/Steam_Games_API.postman_collection.json` ke Postman. Pastikan variabel koleksi `base_url` bernilai `http://127.0.0.1:8000/api` dan server sedang berjalan. Klik **Run** pada koleksi untuk menjalankan semua request sekaligus: 17 request, 40 tes otomatis, semuanya harus lulus. Urutan request di folder `3 Games - Tulis` jangan diubah karena `Ubah` dan `Hapus` memakai game yang dibuat oleh `Tambah game`.

## Struktur penting

| File | Fungsi |
| --- | --- |
| `routes/api.php` | Daftar endpoint |
| `app/Http/Controllers/GameController.php` | Logika daftar, cari, top, detail, tambah, ubah, hapus |
| `app/Http/Controllers/StatsController.php` | Logika statistik |
| `app/Models/Game.php` | Model tabel `games` |
| `app/Console/Commands/ImportGames.php` | Perintah `games:import` untuk mengisi tabel dari CSV |
| `database/migrations/` | Rancangan tabel |
| `database/data/steam_clean.csv` | Data hasil pembersihan |
| `postman/` | Koleksi Postman beserta tes |
| `CATATAN.md` | Catatan belajar pembuatan proyek ini |

## Catatan

Genre disimpan sebagai satu teks yang dipisah titik koma (`Action;Indie`), jadi pencocokan genre memakai pola khusus agar "Sport" tidak ikut cocok dengan "Sports". Statistik genre dihitung di PHP karena alasan yang sama.