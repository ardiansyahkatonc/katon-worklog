# IMPLEMENTATION.md

Catatan error aktual yang terjadi selama pengembangan **kat.on Worklog**, beserta root cause dan cara perbaikannya. Ditulis berdasarkan investigasi nyata (audit kode + reproduksi), bukan asumsi.

---

## ERROR 1: MissingAppKeyException

**Problem**: Aplikasi tidak bisa diakses, exception `MissingAppKeyException` muncul.

**Symptom**: Halaman error Laravel menyebutkan APP_KEY kosong/tidak valid.

**Root Cause**: `APP_KEY` di `.env` belum di-generate saat setup awal project.

**Resolution**:

```bash
docker compose exec app php artisan key:generate
```

**Verification**: Aplikasi bisa diakses kembali tanpa exception tersebut.

**Lesson**: `APP_KEY` wajib di-generate setiap kali `.env` dibuat baru dari `.env.example` (termasuk saat clone repo di environment baru).

---

## ERROR 2: Table 'katon_worklog.tasks' doesn't exist

**Problem**: Query ke tabel `tasks` gagal dengan `SQLSTATE[42S02]: Base table or view not found`.

**Symptom**: Error muncul saat mengakses fitur yang menyentuh model `Task`.

**Root Cause**: Migration untuk tabel `tasks` belum dibuat/dijalankan — tabel `tasks` belum ada di database `katon_worklog`.

**Resolution**: Membuat migration `2026_09_14_073020_create_tasks_table.php` (kolom `user_id`, `category_id` sebagai foreign key dengan `cascadeOnDelete()`, `title`, `description`, `priority`, `status`, `due_date`, timestamps), lalu menjalankan:

```bash
docker compose exec app php artisan migrate
```

**Verification**: `php artisan migrate:status` menampilkan migration tasks berstatus `Ran`; tabel `tasks` muncul di database.

**Lesson**: Setiap model Eloquent baru (`Task`) harus punya migration yang sesuai sebelum di-query, dan urutan migration harus mengikuti dependency foreign key (`users`, `categories` dibuat lebih dulu dari `tasks`).

---

## ERROR 3: View [dashboard] not found

**Problem**: Route `/dashboard` melempar `InvalidArgumentException: View [dashboard] not found.`

**Symptom**: Setelah login berhasil dan redirect ke `dashboard`, halaman menampilkan error, bukan ringkasan task.

**Root Cause**: File `dashboard.blade.php` berada di `resources/views/auth/dashboard.blade.php`, padahal route memanggil `view('dashboard')` yang me-resolve ke `resources/views/dashboard.blade.php` (root folder views, bukan subfolder `auth`).

**Resolution**: Memindahkan file dari `resources/views/auth/dashboard.blade.php` ke `resources/views/dashboard.blade.php`.

**Verification**: Setelah login, `/dashboard` menampilkan card ringkasan (Total Task, Belum Dimulai, Dikerjakan, Selesai) tanpa error.

**Lesson**: Nama argumen `view()` harus cocok persis dengan lokasi file relatif terhadap `resources/views`. Blade view dot-notation (`view('auth.dashboard')` vs `view('dashboard')`) menentukan folder, bukan hanya nama file.

---

## ERROR 4: Halaman blank putih di GET /tasks/create

**Problem**: Membuka `http://localhost:8001/tasks/create` menampilkan halaman kosong (blank white page) — tidak ada error Laravel, tidak ada error di `storage/logs/laravel.log`, dan `docker compose logs app` hanya menunjukkan request masuk tanpa exception.

**Symptom**:
- Browser: halaman putih kosong, status HTTP 200 (bukan 500).
- Log: tidak ada stack trace baru.
- Route terdaftar dengan benar di `php artisan route:list` (`GET|HEAD tasks/create › TaskController@create`).

**Investigasi yang dilakukan** (sesuai Phase 2 — bukan tebakan):

1. `php artisan route:list` → route `tasks.create` terdaftar dan mengarah ke `TaskController@create`, jadi bukan masalah routing.
2. Membaca isi `app/Http/Controllers/TaskController.php` → **seluruh method controller (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) masih berupa stub kosong** (`// TODO` / komentar saja), hasil scaffold `php artisan make:controller` yang belum diimplementasikan. Method `create()` tidak memanggil `view()` sama sekali dan tidak `return` apa pun.
3. Karena method controller tidak me-`return` apa pun, PHP mengembalikan `null` sebagai response. Laravel menerima `null` dari controller sebagai body response kosong dengan status 200 — **bukan exception**, sehingga tidak muncul di log maupun di halaman error Laravel. Ini menjelaskan kenapa halaman terlihat blank tanpa jejak error.
4. Diperiksa juga kemungkinan lain agar tidak sekadar menebak: query ke tabel `categories` (data ada, seeder sudah membuat 3 kategori), model `Category` (relasi benar), file `resources/views/tasks/create.blade.php` (isinya sudah lengkap dan valid, mengharapkan variabel `$categories` dari controller), `layouts.app` (valid), serta hasil `php artisan view:cache` dan `optimize:clear` (tidak ada error compile Blade). Semua komponen ini terbukti **tidak bermasalah** — akar masalah murni di controller yang belum diimplementasikan.

**Root Cause**: `TaskController` adalah hasil scaffold yang seluruh method-nya masih kosong (return `null`), bukan bug pada routing, view, atau database.

**Resolution**: Mengimplementasikan seluruh method `TaskController`:

- `index()` — mengambil task milik user yang login (`auth()->user()->tasks()`), dengan filter `search`, `status`, `priority` opsional via query string.
- `create()` — mengembalikan `view('tasks.create', compact('categories'))` dengan daftar kategori dari `Category::orderBy('name')->get()`.
- `store()` — validasi input (`title`, `category_id`, `priority`, `status` wajib; `description`, `due_date` opsional), lalu menyimpan task lewat relasi `auth()->user()->tasks()->create($data)` agar `user_id` **selalu** berasal dari user yang login, bukan dari input form.
- `show()`, `edit()`, `update()`, `destroy()` — memanggil `$this->authorize()` (didukung oleh `App\Policies\TaskPolicy` yang baru dibuat) sebelum memproses task, sehingga akses ke task milik user lain menghasilkan 403.

Juga menambahkan `use AuthorizesRequests;` pada `app/Http/Controllers/Controller.php` (dasar dari seluruh controller) agar `$this->authorize()` tersedia, dan membuat `app/Policies/TaskPolicy.php` yang mendefinisikan `view`, `update`, `delete` berdasarkan kecocokan `task->user_id` dengan user yang login. Policy ini otomatis dikenali Laravel lewat konvensi nama (`Task` → `TaskPolicy`), tanpa perlu registrasi manual.

**Verification**:
- `GET /tasks/create` menampilkan form lengkap (judul, deskripsi, kategori, prioritas, status, due date, tombol simpan) — diverifikasi lewat browser dan `get_page_text`.
- Task berhasil dibuat lewat form dan tersimpan di database dengan `user_id` yang benar (diverifikasi lewat `php artisan tinker`).
- Seluruh route CRUD (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) diuji manual dan berfungsi (lihat `TESTING.md`).

**Lesson**: Response kosong (blank page, status 200, tanpa exception) di Laravel sering menandakan controller/method yang belum di-`return`, bukan selalu bug infrastruktur (cache, routing, DB). Langkah audit yang benar adalah membaca isi controller lebih dulu sebelum menebak ke arah cache atau konfigurasi server.
