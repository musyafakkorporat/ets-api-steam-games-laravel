# Catatan Belajar: REST API Game Steam (Laravel + MySQL)

Proyek latihan ETS Pemrograman API. Dataset: *Steam Store Games (Clean dataset)* dari Kaggle (nikdavis).
Tujuan: memahami fondasinya (HTTP, database, Laravel), bukan hanya membuatnya jadi.

Cara memakai catatan ini: setiap selesai satu langkah, centang daftarnya, tulis hasilnya, lalu `git commit`.
Riwayat commit jadi jurnal belajarmu.

## Lingkungan

| Alat | Versi / keterangan |
| --- | --- |
| Sistem | Windows, VS Code |
| XAMPP | MariaDB 10.4.32 (MySQL), Apache |
| PHP | 8.2.12 |
| Composer | 2.10.0 |
| Laravel | 12 (dipilih otomatis oleh Composer karena PHP 8.2) |
| Pengujian | Postman |
| Folder proyek | `C:\Projects\steam_learn` (sengaja di luar OneDrive) |

**Kenapa di luar OneDrive:** path Windows dibatasi sekitar 260 karakter dan folder `vendor` punya path yang dalam; OneDrive juga menyinkronkan puluhan ribu file kecil dan kadang mengunci file saat Composer menulis.

## Langkah 0: cek alat

```
php -v
composer -V
```

- `php -v` menampilkan versi PHP. Laravel 12 butuh PHP 8.2 atau lebih baru.
- `composer -V` menampilkan versi Composer, pengelola paket PHP (seperti `pip` di Python atau `npm` di JavaScript).
- MySQL di XAMPP harus berwarna hijau sebelum lanjut.

## Langkah 1: buat proyek Laravel

```
cd C:\Projects
composer create-project laravel/laravel steam_learn
cd steam_learn
code .
php artisan serve
```

- `create-project laravel/laravel steam_learn` mengunduh kerangka Laravel dan semua library ke folder baru `steam_learn`.
- Kalau nama folder tidak ditulis, Composer memakai nama bawaan `laravel`. Perbaikannya: `ren laravel steam_learn`.
- Composer mengecek versi PHP dan memilih versi Laravel yang cocok (v13 butuh PHP 8.3, jadi dipilih Laravel 12).
- `php artisan serve` menjalankan server pengembangan di `http://127.0.0.1:8000`.

Isi folder yang penting:

| Folder / file | Fungsi |
| --- | --- |
| `routes/` | Daftar URL dan ke mana URL itu diarahkan |
| `app/` | Kode aplikasi: controller (logika) dan model (akses data) |
| `database/` | Migration (rancangan tabel) dan data |
| `.env` | Pengaturan khusus komputer ini (koneksi database), tidak diunggah ke Git |
| `vendor/` | Library hasil unduhan Composer, jangan diedit |
| `artisan` | Alat perintah Laravel (`php artisan ...`) |

Hasil: [x] halaman sambutan Laravel muncul di browser.

## Langkah 2: sambungkan ke MySQL

**2a. Buat database kosong** (phpMyAdmin, tab SQL):

```sql
CREATE DATABASE steam_learn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

- `utf8mb4` bisa menyimpan semua huruf (termasuk huruf Jepang pada judul game).
- `unicode_ci` membuat pembandingan teks tidak membedakan huruf besar dan kecil.

**2b. Atur `.env`:**

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=steam_learn
DB_USERNAME=root
DB_PASSWORD=
```

- `root` tanpa password adalah bawaan XAMPP; hanya cocok untuk belajar di komputer sendiri.
- `.env` berisi pengaturan rahasia dan berbeda tiap komputer, jadi tidak ikut ke Git.

**2c. Jalankan migration:**

```
php artisan migrate
```

- Migration adalah file PHP yang mendefinisikan tabel; `migrate` menjalankannya ke database di `.env`.
- Ini sekaligus tes koneksi: kalau salah, langsung error.
- `php artisan migrate:status` menunjukkan migration mana yang sudah `Ran`. "Nothing to migrate" berarti semuanya sudah dijalankan, bukan error.

Hasil: [x] tiga baris DONE (`users`, `cache`, `jobs`), [x] tabelnya terlihat di phpMyAdmin.

## Langkah 3: aktifkan fitur API

```
php artisan install:api
```

- Laravel punya dua jenis route. `routes/web.php` untuk halaman browser (memakai sesi dan CSRF), `routes/api.php` untuk API (*stateless*, alamat otomatis berawalan `/api`).
- Sejak Laravel 11, `routes/api.php` tidak dibuat bawaan. Perintah ini membuatnya, mendaftarkannya di `bootstrap/app.php`, dan memasang Sanctum (autentikasi token, belum kita pakai).
- Route pertama: `Route::get('/', fn)` yang mengembalikan JSON lewat `response()->json([...])`.
- Alamat `http://127.0.0.1:8000` menampilkan halaman sambutan (route web). Alamat `http://127.0.0.1:8000/api` menampilkan JSON (route API).
- `php artisan route:list --path=api` menampilkan semua route API yang terdaftar.

Hasil: [x] `/api` menampilkan `{"message":"Steam Store Games API"}`.

## Langkah 4: migration tabel `games`

```
php artisan make:migration create_games_table
```

- Migration adalah gambar rancangan tabel dalam kode. Strukturnya tercatat di Git, dan siapa pun bisa membangun tabel yang sama.
- `appid` dipakai sebagai primary key (unik dari Steam), bukan `id()` auto-increment.
- `->index()` dipasang hanya pada kolom yang sering dicari: `name`, `release_year`, `price`.
- `->nullable()` untuk data yang memang bisa kosong (`release_date`, `release_year`, `positive_pct`), lebih jujur daripada mengisi angka palsu.
- `decimal(8,2)` untuk uang (bukan `float`, supaya tidak ada galat pembulatan).
- Tidak ada `timestamps()` karena data dari Kaggle, bukan dibuat aplikasi.

Hasil: [x] `create_games_table ... DONE`, [x] 15 kolom terlihat di phpMyAdmin.

## Langkah 5: model `Game`

```
php artisan make:model Game
```

- Model adalah kelas PHP yang mewakili satu tabel (Eloquent ORM). `Game::where(...)->get()` diterjemahkan jadi SQL.
- Laravel punya asumsi bawaan: kelas `Game` berarti tabel `games`, primary key `id`, auto-increment, ada `created_at`/`updated_at`. Tabel kita menyimpang, jadi diatur: `$primaryKey = 'appid'`, `$incrementing = false`, `$timestamps = false`.
- `$guarded = []` mengizinkan semua kolom diisi lewat `create()`/`update()` (aman karena input divalidasi di controller).
- `casts()`: `price` dan `positive_pct` jadi `float`, `is_free` jadi `boolean`, `release_date` jadi `date:Y-m-d`. Tanpa ini MySQL mengirim desimal sebagai teks.
- `php artisan tinker` adalah terminal interaktif untuk mencoba kode. `App\Models\Game::count();` menghasilkan jumlah baris.

Hasil: [x] `Game::count()` di Tinker menghasilkan angka tanpa error.

## Langkah 6: impor data CSV

```
php artisan make:command ImportGames
php artisan games:import
```

- Mengisi 27.075 baris tidak masuk akal lewat tangan, jadi dibuat perintah artisan sendiri yang bisa diulang.
- `$signature = 'games:import {file=database/data/steam_clean.csv}'`: nama perintah dan argumen opsional.
- `fgetcsv()` membaca satu baris CSV per panggilan (hemat memori). Baris pertama adalah header.
- `array_combine($header, $row)` membuat array berkunci, jadi kode terbaca (`$r['price']`).
- Insert **per 500 baris** (`Game::insert($batch)`): jauh lebih cepat daripada satu-satu, dan aman dari batas parameter MySQL.
- `Game::truncate()` di awal supaya perintah bisa dijalankan berulang tanpa data dobel.
- `mapRow()` menerjemahkan teks CSV ke tipe yang benar: `(int)`, `(float)`, `'True'` jadi boolean, sel kosong jadi `NULL` atau `'Unknown'`.
- Sisa baris terakhir (di luar kelipatan 500) harus dimasukkan setelah loop (`if ($batch)`), kalau tidak hilang.

Hasil: [x] `Berhasil mengimpor 27075 game.` Tinker: `count()` = 27075, `find(10)->name` = Counter-Strike, game gratis = 2560.

## Langkah 7: route dan controller

Pola: **route** hanya menunjuk "URL ini ditangani siapa", **controller** berisi logikanya.

```
php artisan make:controller GameController
php artisan make:controller StatsController
```

| Method | URL | Fungsi | Status |
| --- | --- | --- | --- |
| GET | `/api/games` | daftar + filter + pagination | 200 |
| GET | `/api/games/search?q=` | cari nama | 200 / 422 |
| GET | `/api/games/top` | peringkat rating | 200 |
| GET | `/api/games/{appid}` | satu game | 200 / 404 |
| POST | `/api/games` | tambah game | 201 / 422 |
| PUT | `/api/games/{appid}` | ubah game | 200 / 404 / 422 |
| DELETE | `/api/games/{appid}` | hapus game | 200 / 404 |
| GET | `/api/stats/genre` | statistik per genre | 200 |
| GET | `/api/stats/year` | statistik per tahun | 200 |

Konsep yang dipelajari:

- **Path vs query string:** `/games/10` menunjuk satu sumber; `?genre=RPG&max_price=30` menyaring daftar.
- **REST:** URL menyebut bendanya, method menyebut tindakannya. URL `/games/10` sama, method berbeda: GET (lihat), PUT (ubah), DELETE (hapus).
- **Pagination:** `paginate(20)` agar tidak mengirim 27 ribu baris sekaligus. `per_page` dibatasi 1 sampai 100.
- **Filter opsional:** `$request->filled('x')` melewati syarat kalau parameter tidak dikirim.
- **Genre disimpan sebagai teks gabungan** (`Action;Indie`), jadi pencocokan genre utuh memakai 4 kondisi OR (`= g`, `g;%`, `%;g`, `%;g;%`). `LIKE '%Sport%'` saja akan ikut mencocokkan "Sports".
- **Urutan route penting:** `/games/search` dan `/games/top` harus di atas `/games/{appid}`. `whereNumber('appid')` jadi pengaman kedua.
- **Validasi:** `Validator::make(...)` dengan respons 422 buatan sendiri (selalu JSON, tidak bergantung header `Accept`). `sometimes` pada update berarti "validasi hanya kalau dikirim".
- **Data turunan dihitung server:** `is_free` dari `price`, `release_year` dari `release_date`, `positive_pct` dari jumlah ulasan.
- **`top` memakai `min_votes`** (bawaan 1000) agar game dengan 3 ulasan positif tidak mengalahkan game dengan ratusan ribu ulasan.
- **Agregasi:** statistik tahun dikerjakan MySQL (`GROUP BY`, `COUNT`, `AVG`); statistik genre dikerjakan PHP karena genre tersimpan sebagai teks gabungan (kelemahan desain kolom tunggal).
- **SQL injection:** Eloquent mengirim nilai sebagai parameter terikat, input tidak ditempel mentah ke SQL.
- **Status code:** 200 sukses, 201 dibuat, 404 tidak ada, 422 data tidak valid.

Hasil: [x] semua endpoint bekerja, angka cocok dengan perhitungan dari CSV (Action 11903, Sports 1322, Sport 0, tahun 2018 = 8160 game).

## Langkah 8: Postman

Struktur koleksi `Steam Games API`:

| Folder | Isi | Tujuan |
| --- | --- | --- |
| 1 Info | Root | Apakah server hidup? |
| 2 Games - Baca | daftar, filter, detail, detail tidak ada, cari, cari tanpa q, top | Semua cara membaca data, sukses dan gagal |
| 3 Games - Tulis | tambah, tambah gagal, ubah, ubah gagal, ubah tidak ada, hapus, hapus tidak ada | Menulis data dengan aman |
| 4 Statistik | genre, tahun | Ringkasan data |

- **Variabel koleksi:** `base_url` = `http://127.0.0.1:8000/api` (diketik sendiri); `new_id` terisi otomatis oleh skrip di request Tambah game.
- **Skrip "Before" di koleksi:** `pm.request.headers.upsert({ key: 'Accept', value: 'application/json' });` supaya semua galat berupa JSON.
- **Body POST/PUT:** tab Body, pilih `raw`, ubah `Text` menjadi `JSON`. Tanpa itu Laravel tidak membaca body dan semua kolom wajib dianggap kosong.
- **Skrip "After" (tes otomatis):** `pm.test('nama', () => { ... })` dengan `pm.response.to.have.status(200)` dan `pm.expect(nilai).to.eql(x)`.
- **Urutan di folder 3 penting** karena Runner berjalan dari atas ke bawah: Tambah, Tambah gagal, Ubah, Ubah gagal, Ubah tidak ada, Hapus, Hapus tidak ada. `Ubah` dan `Hapus` memakai `{{new_id}}` dari Tambah.
- **Uji jalur sukses dan jalur gagal.** API yang baik juga menolak input salah dengan jelas (404, 422).

Hasil: [x] Runner: 17 request, **40 tes, 40 lulus, 0 gagal**.

## Masalah yang pernah muncul

| Gejala | Penyebab | Solusi |
| --- | --- | --- |
| XAMPP: "MySQL shutdown unexpectedly" | Tabel sistem `mysql.db` rusak (`Incorrect file format 'db'`), biasanya karena XAMPP dimatikan tanpa Stop | `ren data data_old`, lalu `xcopy backup data /E /I /H` di `C:\xampp\mysql` |
| Cara menemukan penyebabnya | Log XAMPP tidak menulis error | `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone --console` |
| `Unknown database 'steam_api'` | Koneksi berhasil tetapi database belum dibuat | Buat database di phpMyAdmin |
| `php artisan db:show` error `performance_schema` | Fitur itu nonaktif di XAMPP | Abaikan, jangan pakai `db:show` |
| Folder proyek bernama `laravel` | Nama folder tidak ditulis di `composer create-project` | `ren laravel steam_learn` |
| `php artisan migrate` "Nothing to migrate" | Semua migration sudah `Ran` | Cek dengan `php artisan migrate:status`; bukan error |
| `SQLSTATE[HY000] [2002] ... actively refused it` | MySQL di XAMPP sedang mati | Start MySQL di XAMPP, ulangi perintah |
| Halaman "Laravel" muncul, bukan JSON | Membuka `/` (route web), bukan `/api` | Buka `http://127.0.0.1:8000/api` |
| POST menghasilkan 422 padahal data sudah diisi | Body bertipe `Text`, bukan `JSON`, sehingga tidak terbaca | Body, raw, ubah ke JSON |
| Postman: kolom Value saja, tidak ada Initial/Current | Postman versi baru menggabungkannya | Isi kolom Value |
| Postman: tidak ada "Pre-request" | Namanya "Before" / "After" di versi baru | Pakai tab Scripts, Before untuk pre-request |
| `git rebase -i` macet menunggu editor | Tab `git-rebase-todo` belum ditutup / tidak muncul | Batalkan (`git rebase --abort`), pakai `git filter-branch --msg-filter` untuk menulis ulang pesan commit |
| Pesan commit tidak enak dibaca | Awalan "Langkah N:" terlalu seragam | `git filter-branch -f --msg-filter "sed -E 's/^Langkah [0-9a-z-]+: //'" -- main`, lalu `git push --force-with-lease origin main` |

Pencegahan: selalu klik **Stop** pada MySQL dan Apache di XAMPP sebelum menutup XAMPP atau mematikan komputer.

## Istilah

| Istilah | Arti singkat |
| --- | --- |
| Request / response | Permintaan dari client dan jawaban dari server |
| REST | Data dipandang sebagai "benda" (`/games`), method menyatakan tindakannya |
| CRUD | Create (POST), Read (GET), Update (PUT), Delete (DELETE) |
| Endpoint | Kombinasi method dan URL yang melayani satu fungsi |
| Status code | Angka hasil: 200 sukses, 201 dibuat, 404 tidak ada, 422 data tidak valid |
| Path parameter | Nilai di URL yang menunjuk satu item (`/games/10`) |
| Query string | Syarat tambahan setelah `?` untuk menyaring daftar |
| Body | Data yang dikirim bersama request (JSON) |
| Pagination | Membagi hasil panjang jadi halaman-halaman |
| Migration | File yang mendefinisikan struktur tabel |
| Model | Kelas PHP yang mewakili satu tabel (Eloquent) |
| Controller | Kelas yang berisi logika untuk menjawab request |
| Route | Pemetaan URL ke controller |
| Validasi | Memeriksa input sebelum disimpan |
| Agregasi | Merangkum banyak baris jadi sedikit angka (`COUNT`, `AVG`, `GROUP BY`) |
| Index | "Daftar isi" pada kolom agar pencarian cepat |
| Collection / Folder / Variable (Postman) | Wadah request / pengelompokan / nilai yang dipakai ulang |
| Runner | Menjalankan seluruh koleksi sekaligus dan merekap tes |

## Rencana langkah berikutnya

- [x] Langkah 3: aktifkan fitur API (`php artisan install:api`)
- [x] Langkah 4: migration tabel `games`
- [x] Langkah 5: model `Game`
- [x] Langkah 6: impor data dari CSV
- [x] Langkah 7: route dan controller, endpoint demi endpoint
- [x] Langkah 8: tes otomatis dan Runner di Postman
- [ ] Langkah 9: ekspor koleksi Postman ke repo, tulis README, uji clone bersih
