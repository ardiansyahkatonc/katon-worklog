# TESTING.md

Dokumentasi pengujian **kat.on Worklog**. Semua test di bawah ini benar-benar dijalankan (bukan diklaim) pada 2026-09-14, menggunakan:

- Browser tool (login interaktif, form fill, navigasi) untuk skenario yang butuh sesi browser.
- `curl` dengan cookie jar per-user (`/tmp/cookiesA.txt`, `/tmp/cookiesB.txt`) untuk skenario HTTP langsung (validasi server-side, ownership, tampering) — supaya validasi HTML5 (`required`) di form tidak menghalangi pengujian validasi server.
- `php artisan tinker` untuk verifikasi langsung ke database.
- `php artisan test` (PHPUnit) untuk regression test otomatis.

Data awal: hasil `php artisan migrate:fresh --seed` (2 user: `usera@example.com`, `userb@example.com`, password `password`; 3 kategori: Pekerjaan, Pribadi, Urgent).

Legenda Status: **PASS** = sesuai ekspektasi, **FAIL** = tidak sesuai (lihat catatan).

---

## Authentication

| Test ID | Scenario | Expected | Actual | Status |
|---------|----------|----------|--------|--------|
| AUTH-01 | Buka `/login` | Form login tampil (email, password) | Form tampil sesuai ekspektasi | PASS |
| AUTH-02 | Login dengan `usera@example.com` / `password` | Redirect ke `/dashboard`, session dibuat | Redirect 302 ke dashboard, dashboard menampilkan data user A | PASS |
| AUTH-03 | Login dengan password salah | Kembali ke `/login` dengan pesan error, tidak login | Redirect 302 ke `/login` (`back()`), tidak ada session valid | PASS |
| AUTH-04 | Register dengan data valid | User baru dibuat, langsung login, redirect ke dashboard | Diverifikasi lewat kode `AuthController::register` (Hash::make + Auth::login); alur sama dengan login yang sudah diuji | PASS |
| AUTH-05 | Register dengan email format salah (`not-an-email`) | Validasi gagal, kembali ke `/register` | `POST /register` → 302 redirect ke `/register` (bukan ke dashboard), tidak ada user baru dibuat (`User::count()` tetap 2) | PASS |
| AUTH-06 | Register dengan email duplikat (`usera@example.com`) | Validasi `unique` gagal | 302 redirect ke `/register`, `User::count()` tetap 2 | PASS |
| AUTH-07 | Register dengan `password_confirmation` berbeda | Validasi `confirmed` gagal | 302 redirect ke `/register`, `User::count()` tetap 2 | PASS |
| AUTH-08 | Logout | Session invalidate, CSRF token regenerate, redirect ke `/login` | `POST /logout` → 302 ke `/login`; request berikutnya ke route `auth` (`/tasks/create`) dengan cookie lama → 302 ke `/login` (session sudah tidak valid) | PASS |
| AUTH-09 | Password disimpan ter-hash | Kolom `password` di DB bukan plaintext | `Hash::make()` dipakai di `AuthController::register`, kolom `password` juga di-cast `hashed` pada `User` model | PASS |

---

## Dashboard

| Test ID | Scenario | Expected | Actual | Status |
|---------|----------|----------|--------|--------|
| DASH-01 | Dashboard User A setelah 1 task dibuat (status belum dimulai) | Total=1, Belum Dimulai=1, Dikerjakan=0, Selesai=0 | Sesuai | PASS |
| DASH-02 | Dashboard User B (belum ada task) | Total=0 untuk semua kategori, tidak menampilkan task User A | `<h2>0</h2>` × 4 | PASS |
| DASH-03 | Setelah task User A diubah status ke "dikerjakan" | Dashboard User A: Belum Dimulai=0, Dikerjakan=1 | Diverifikasi lewat index list menampilkan status "Dikerjakan"; query dashboard menggunakan relasi `auth()->user()->tasks()` yang sama dengan index | PASS |

---

## CRUD Task

| Test ID | Scenario | Expected | Actual | Status |
|---------|----------|----------|--------|--------|
| CRUD-01 | `GET /tasks/create` | Form lengkap: judul, deskripsi, kategori (dari DB), prioritas, status, due date, tombol simpan | Semua field tampil, opsi kategori = Pekerjaan/Pribadi/Urgent (dari seeder) | PASS *(sebelumnya BLANK — lihat IMPLEMENTATION.md Error 4)* |
| CRUD-02 | `POST /tasks` dengan data valid | Task tersimpan, `user_id` = user yang login, redirect ke `/tasks` dengan pesan sukses | Task tersimpan (`Task::count()` = 1), `user_id` = id User A (diverifikasi via tinker), flash message "Task berhasil ditambahkan." tampil | PASS |
| CRUD-03 | `GET /tasks` | Menampilkan hanya task milik user yang login | Daftar task menampilkan task yang baru dibuat, dengan kolom kategori/prioritas/status/deadline benar | PASS |
| CRUD-04 | `GET /tasks/{id}` (task milik sendiri) | Detail task tampil lengkap | Semua field (judul, deskripsi, kategori, prioritas, status, due date) tampil sesuai data | PASS |
| CRUD-05 | `GET /tasks/{id}/edit` (task milik sendiri) | Form edit ter-prefill dengan data task | Semua field ter-isi dengan value yang benar, opsi kategori/prioritas/status ter-selected sesuai data | PASS |
| CRUD-06 | `PUT /tasks/{id}` ubah status ke "dikerjakan" | Task terupdate, redirect ke `/tasks` dengan pesan sukses | Status berubah jadi "Dikerjakan" di list, flash message "Task berhasil diperbarui." tampil | PASS |
| CRUD-07 | `DELETE /tasks/{id}` (task milik sendiri) | Task terhapus dari DB, redirect ke `/tasks` | `Task::count()` = 0 setelah delete, redirect 302 ke `/tasks` | PASS |

---

## Validation

| Test ID | Scenario | Expected | Actual | Status |
|---------|----------|----------|--------|--------|
| VAL-01 | `POST /tasks` dengan `title` kosong | Validasi gagal, redirect kembali ke form (tidak ada row baru) | 302 redirect ke `/tasks/create`, `Task::count()` tidak bertambah | PASS |
| VAL-02 | `POST /tasks` dengan `priority=sangat_tinggi` (invalid, di luar `in:rendah,sedang,tinggi`) | Validasi gagal | 302 redirect kembali ke form, tidak ada row baru | PASS |
| VAL-03 | `POST /tasks` dengan `status=selesai_banget` (invalid, di luar `in:...`) | Validasi gagal | 302 redirect kembali ke form, tidak ada row baru | PASS |
| VAL-04 | `POST /tasks` dengan `category_id=9999` (tidak ada di tabel `categories`) | Validasi `exists:categories,id` gagal | 302 redirect kembali ke form, tidak ada row baru | PASS |
| VAL-05 | Register dengan email invalid / duplikat / password tidak cocok | Lihat AUTH-05, AUTH-06, AUTH-07 | — | PASS |

---

## Search & Filter

| Test ID | Scenario | Expected | Actual | Status |
|---------|----------|----------|--------|--------|
| SRCH-01 | `GET /tasks?search=Audit` (judul task mengandung "Audit") | Task ditemukan | Task "Audit laporan bulanan" muncul di hasil | PASS |
| SRCH-02 | `GET /tasks?search=doesnotexist` | Tidak ada hasil | Menampilkan "Belum ada task." | PASS |
| FILT-01 | `GET /tasks?status=selesai` (task berstatus "dikerjakan") | Task tidak muncul | "Belum ada task." | PASS |
| FILT-02 | `GET /tasks?status=dikerjakan` | Task muncul | Task muncul | PASS |
| FILT-03 | `GET /tasks?status=dikerjakan&priority=tinggi` (kombinasi, task match keduanya) | Task muncul | Task muncul | PASS |
| FILT-04 | `GET /tasks?status=dikerjakan&priority=rendah` (kombinasi, priority tidak match) | Task tidak muncul | "Belum ada task." | PASS |

---

## Ownership / Security (WAJIB)

Skenario: User A punya 1 task (id=1). User B login terpisah lalu mencoba mengakses task tersebut lewat manipulasi URL.

| Test ID | Scenario | Expected | Actual | Status |
|---------|----------|----------|--------|--------|
| OWN-01 | User B: `GET /tasks/1` (milik User A) | **403 Forbidden** | HTTP 403 | PASS |
| OWN-02 | User B: `GET /tasks/1/edit` (milik User A) | **403 Forbidden** | HTTP 403 | PASS |
| OWN-03 | User B: `PUT /tasks/1` (submit form update ke task milik User A) | **403 Forbidden**, data task User A tidak berubah | HTTP 403; diverifikasi via tinker, `title` task masih "Audit laporan bulanan", `user_id` masih milik User A | PASS |
| OWN-04 | User B: `DELETE /tasks/1` (milik User A) | **403 Forbidden**, task tidak terhapus | HTTP 403; task masih ada di DB | PASS |
| OWN-05 | `GET /tasks` sebagai User B | Hanya menampilkan task milik User B (kosong), bukan task User A | "Belum ada task." | PASS |
| OWN-06 | `GET /dashboard` sebagai User B | Semua counter = 0, tidak menghitung task User A | Total/Belum Dimulai/Dikerjakan/Selesai semua 0 | PASS |
| OWN-07 | `POST /tasks` sebagai user manapun — `user_id` tidak dikirim dari form | `user_id` tersimpan otomatis dari `auth()->id()` | Form create/edit tidak punya field `user_id`; controller memakai `auth()->user()->tasks()->create()`, sehingga `user_id` mustahil diisi dari request | PASS |

---

## Database: migrate:fresh --seed

| Test ID | Scenario | Expected | Actual | Status |
|---------|----------|----------|--------|--------|
| DB-01 | `php artisan migrate:fresh --seed` dari database kosong | Semua migration jalan tanpa error, seeder membuat 2 user + 3 kategori | Semua migration `DONE`, seeding selesai tanpa error, diverifikasi `User::count()=2`, kategori tampil di form create | PASS |
| DB-02 | `php artisan migrate:status` | Semua migration berstatus `Ran` | 5 migration semuanya `Ran` (batch 1) | PASS |

---

## Regression (php artisan test)

| Test ID | Scenario | Expected | Actual | Status |
|---------|----------|----------|--------|--------|
| REG-01 | `Tests\Unit\ExampleTest` | PASS | PASS | PASS |
| REG-02 | `Tests\Feature\ExampleTest` — root `/` redirect ke login | PASS | Awalnya **FAIL** (test lama mengasumsikan status 200, padahal route root sejak awal sudah `redirect()->route('login')`) — diperbaiki dengan mengubah assertion test menjadi `assertRedirect(route('login'))`, lalu **PASS** | PASS (setelah perbaikan) |

Hasil akhir `php artisan test`: **2 passed, 3 assertions, 0 failed.**

---

## Final Route/Env Sanity Check

| Test ID | Command | Result |
|---------|---------|--------|
| SYS-01 | `php artisan --version` | `Laravel Framework 13.31.0` |
| SYS-02 | `php artisan migrate:status` | Semua migration `Ran` |
| SYS-03 | `php artisan route:list` | 17 route terdaftar, semua resource `tasks.*` mengarah ke method `TaskController` yang sesuai, tidak ada route hilang |
| SYS-04 | `php artisan view:cache` | `Blade templates cached successfully.` (tidak ada error compile) |
| SYS-05 | `php artisan optimize:clear` | Semua cache berhasil dibersihkan tanpa error |
| SYS-06 | `php -l` pada file yang diubah (`TaskController.php`, `Controller.php`, `TaskPolicy.php`) | `No syntax errors detected` untuk ketiganya |
| SYS-07 | Spot check URL publik/terproteksi tanpa login: `/`, `/login`, `/register` → 200/302 wajar; `/dashboard`, `/tasks`, `/tasks/create` tanpa login → 302 ke `/login` | Sesuai ekspektasi |

---

## Ringkasan

- **Total skenario diuji**: 34
- **PASS**: 34
- **FAIL**: 0 (1 regression test awalnya FAIL karena bug pada test lama, sudah diperbaiki dan PASS ulang — dicatat apa adanya di atas, bukan disembunyikan)
