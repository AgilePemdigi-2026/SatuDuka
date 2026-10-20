# Progress Pengerjaan SATUDUKA

Aplikasi web SATUDUKA — digitalisasi pencatatan dan administrasi kematian Kota Yogyakarta.
Sumber kebenaran: `docs/SRS_clean.md`, `docs/DATABASE.md`, `docs/gui/`, `docs/diagram/`.

## Fase 0 — Setup Proyek

- **Status:** Selesai (perbaikan/bootstrap tanggal 2026-10-20).
- **Ringkasan:** Kerangka Laravel terpasang, konfigurasi lingkungan dan basis data diselaraskan,
  berkas `resources/views/tata_letak/utama.blade.php` dibersihkan dari pembungkus PowerShell,
  berkas progres dipindahkan menjadi `PROGRESS.md`, paket pengujian disetel, dan seluruh
  jejak Laravel Boost dihapus.

### Keputusan Teknis & Lingkungan

- **Framework:** Laravel `13.37.0` (versi stabil terbaru), PHP `8.3.33`, Composer `2.10.3`.
  Ringkasan dependency: `laravel/framework ^13.17`, `laravel/tinker ^3.0`, `laravel/pint ^1.27`,
  `laravel/pail ^1.2.5`.
- **Basis data:** PostgreSQL Supabase yang dipakai **hanya** sebagai Postgres. Driver `pgsql`
  bawaan Laravel dengan `sslmode=require`. Autentikasi, otorisasi, dan seluruh logika tetap
  di dalam Laravel (tanpa Supabase Auth dan tanpa REST API Supabase).
- **Port & emulate prepares:** koneksi lokal memakai **transaction pooler port `6543`** dengan
  `PDO::ATTR_EMULATE_PREPARES => true` pada `config/database.php`, karena port `5432`
  diblokir jaringan lokal. Sesuai `AGENTS.md`, migrasi idealnya dijalankan lewat session
  pooler (port `5432`) yang mendukung prepared statement/sesi; opsi `sslmode=require` tetap
  dipakai pada kedua jalur.
- **PDF dokumen:** `barryvdh/laravel-dompdf` dikunci pada constraint `^3.1` (terpasang 3.1.2).
- **Frontend:** Tailwind CSS melalui Vite bawaan Laravel, tanpa Breeze/Jetstream karena tabel
  `pengguna` bersifat kustom sehingga autentikasi dibangun manual.
- **Bahasa & lokalisasi:** locale `id`, timezone `Asia/Jakarta` (lewat `APP_TIMEZONE` pada
  `config/app.php`), dan berkas pesan `lang/id/{validation,auth,passwords,pagination}.php`
  sudah dibuat dalam bahasa Indonesia.
- **Kredensial:** hanya ada di `.env` (tidak di-commit). `.env.example` hanya berisi
  placeholder Supabase tanpa nilai asli.
- **Laravel Boost:** dihapus seluruhnya (paket `laravel/boost`, `boost.json`, `.mcp.json`,
  `CLAUDE.md`, `.claude/skills`, `.agents/skills`, `skills-lock.json`, serta baris
  `boost:update` pada `composer.json`).

### Pilihan Alat Pengujian

- **Alat yang benar-benar terpasang:** **Pest `4.7.8`** + `pestphp/pest-plugin-laravel 4.1.0`
  (di atas `phpunit/phpunit 12.5.33`), dipasang sebagai *dev dependency*.
- **Skrip:** `composer test` / `php artisan test`. Status saat ini: **hijau** (2 test, 2 asersi).
- **Isi basis data saat pengujian:** `phpunit.xml` tetap memakai SQLite in-memory
  (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). Pengujian **tidak pernah** diarahkan ke
  Supabase agar basis data pengembangan tidak ikut berubah oleh test.
- **Alasan Behat + Mink dilewati:** integrasi Behat–Laravel (lapisan service Behat, driver
  Laravel, Mink BrowserKit, dan berkas skenario Gherkin) tidak berhasil disiapkan dalam
  batas waktu 30 menit sesuai ketentuan `AGENTS.md`. Karena itu pengujian otomatis
  dipindahkan ke feature test PHPUnit/Pest yang memetakan skenario Gherkin yang sama
  (SRS Bab VI). Keputusan ini dicatat di sini sebagai pengecualian terhadap aturan
  "Pengujian: Behat + Mink" di `AGENTS.md`.

### Masalah Terbuka

- `.env` lokal masih memakai `APP_FALLBACK_LOCALE=en` dan `APP_FAKER_LOCALE=en_US` (file
  `.env` tidak diubah sesuai instruksi), sedangkan `.env.example` memakai `id` / `id_ID`.
  Perlu diselaraskan manual saat `.env` boleh diubah.
- Port `5432` (session pooler) terblokir jaringan lokal, sehingga jalur migrasi sesuai
  `AGENTS.md` belum dapat diverifikasi di lingkungan ini.
- Skema basis data domain (`pengguna`, `laporan_kematian`, `log_audit`, dan seterusnya)
  belum dibuat; itu adalah pekerjaan Fase 1.
- Verifikasi `php artisan migrate:fresh --seed` terhadap Supabase dan pengecekan browser
  belum dilakukan pada fase ini.

### Catatan Perbaikan Fase 0

- `resources/views/tata_letak/utama.blade.php`: dibersihkan dari pembungkus PowerShell
  (`New-Item`, `@"`, `"@ | Set-Content`) sehingga kini berisi HTML Blade murni.
- `progress.md` (isi masih terbungkus pembungkus PowerShell dan menyebut Laravel 11) diganti
  menjadi `PROGRESS.md` dengan isi yang sesuai kenyataan.
- `AGENTS.md` sudah disesuaikan (masih berlaku sebagai aturan proyek utama).
