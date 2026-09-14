# kat.on Worklog

Aplikasi manajemen task pribadi berbasis Laravel. Setiap pengguna registrasi/login sendiri, lalu membuat, mengubah, mencari, memfilter, dan menghapus task miliknya sendiri. Dibuat sebagai mini project persiapan sertifikasi BNSP (skema Junior Web Developer / Programmer).

## Fitur

- Register, Login, Logout (session-based, password di-hash dengan bcrypt)
- Dashboard ringkasan task milik user yang login (total, belum dimulai, dikerjakan, selesai)
- CRUD Task (create, read, update, delete)
- Update status task
- Search task berdasarkan judul/deskripsi
- Filter task berdasarkan status dan prioritas (bisa dikombinasikan)
- Validasi input di sisi server untuk semua form
- Ownership & keamanan: user hanya dapat melihat/mengubah/menghapus task miliknya sendiri; percobaan mengakses task milik user lain melalui URL (ID tampering) akan menghasilkan **403 Forbidden**

## Stack

- Laravel 13.31.0
- PHP 8.4 (via Docker)
- MySQL 8.4
- Blade + Bootstrap 5 (CDN, tanpa build step frontend)
- Docker Desktop (Docker Compose)

## Requirement

- Docker Desktop (Windows/macOS/Linux)
- Git

Tidak perlu install PHP/Composer/MySQL secara lokal — semuanya berjalan di dalam container.

## Arsitektur

```
Browser → Route → Middleware (guest/auth) → Controller → Eloquent Model → MySQL → Blade View
```

Ownership pada task diperiksa lewat `App\Policies\TaskPolicy`, yang di-autoload otomatis oleh Laravel berdasarkan konvensi nama (`Task` model → `TaskPolicy`). Controller memanggil `$this->authorize()` sebelum menampilkan/mengubah/menghapus task, sehingga akses ke task milik user lain otomatis menghasilkan HTTP 403.

## Docker Setup

Struktur container:

| Service | Image             | Port host → container |
|---------|-------------------|------------------------|
| app     | build dari `Dockerfile` (php:8.4-cli) | 8001 → 8000 |
| mysql   | mysql:8.4         | 3307 → 3306            |

## Cara Menjalankan

1. Clone repository ini, lalu masuk ke foldernya.
2. Salin file environment:

   ```bash
   cp .env.example .env
   ```

3. Build dan jalankan container:

   ```bash
   docker compose up -d --build
   ```

4. Install dependency PHP (jika `vendor/` belum ada) dan generate application key:

   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   ```

5. Jalankan migration dan seeder:

   ```bash
   docker compose exec app php artisan migrate --seed
   ```

6. Buka aplikasi di browser:

   ```
   http://localhost:8001
   ```

Semua perintah `artisan` dijalankan di dalam container:

```bash
docker compose exec app php artisan <perintah>
```

## Konfigurasi Database

Environment (`.env`) sudah diarahkan ke service `mysql` pada docker-compose:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=katon_worklog
DB_USERNAME=katon
DB_PASSWORD=katon_password
```

Database `katon_worklog` beserta user `katon` sudah otomatis dibuat oleh service `mysql` di `docker-compose.yml` saat container pertama kali dijalankan.

## Migration & Seeding

Menjalankan migration dari kondisi database kosong:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Tabel yang dibuat: `users`, `cache`, `jobs`, `categories`, `tasks` (beserta tabel bawaan Laravel: `sessions`, `password_reset_tokens`).

Seeder (`database/seeders/DatabaseSeeder.php`) menghasilkan:

- 2 akun test (untuk demo fitur ownership)
- 3 kategori dasar (Pekerjaan, Pribadi, Urgent) agar form Create Task langsung bisa dipakai

## Akun Test

| Email               | Password   | Keterangan                          |
|---------------------|------------|--------------------------------------|
| usera@example.com   | password   | User A — untuk demo task milik sendiri |
| userb@example.com   | password   | User B — untuk demo URL tampering (403) |

Akun-akun ini hanya untuk keperluan development/demo lokal, bukan akun produksi.

## URL Aplikasi

- App: http://localhost:8001
- Login: http://localhost:8001/login
- Register: http://localhost:8001/register
- Dashboard: http://localhost:8001/dashboard
- Task list: http://localhost:8001/tasks

## Cara Menjalankan Test

```bash
docker compose exec app php artisan test
```

Skenario manual (login, CRUD, validasi, ownership, URL tampering) didokumentasikan di [TESTING.md](TESTING.md).

## Catatan Keamanan

- Password di-hash dengan bcrypt (`Hash::make`, plus cast `hashed` pada model `User`).
- Session di-regenerate setelah login berhasil (mencegah session fixation).
- Session di-invalidate dan CSRF token di-regenerate saat logout.
- `user_id` pada task **selalu** diambil dari `auth()->user()`, tidak pernah dari input form — mencegah user menitipkan `user_id` milik orang lain saat submit.
- Setiap akses ke task spesifik (`show`, `edit`, `update`, `destroy`) diperiksa lewat `TaskPolicy`; percobaan ID tampering menghasilkan 403, bukan data user lain.
- Seluruh form menyertakan `@csrf`.

## Known Limitation

- Tidak ada fitur reset password / verifikasi email (di luar scope wajib).
- Task list tidak dipaginasi (cukup untuk skala demo; bisa ditambah `paginate()` bila data besar).
- Kategori bersifat statis dari seeder, belum ada halaman manajemen kategori (sesuai scope, agar kompleksitas tetap minimal).
