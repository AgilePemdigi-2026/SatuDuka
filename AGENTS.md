# SATUDUKA — Aturan Proyek (dibaca di awal setiap sesi)

Proyek: aplikasi web SATUDUKA, digitalisasi pencatatan dan administrasi kematian Kota Yogyakarta.
Sumber kebenaran: `docs/SRS_clean.md` (SRS, 165 KB), `docs/DATABASE.md`, `docs/gui/` (mockup), `docs/diagram/`.

## Aturan hemat konteks (WAJIB)
- JANGAN membaca `docs/SRS_clean.md` utuh. Buka `docs/PETA_SRS.md`, lalu baca hanya rentang baris yang dibutuhkan fase aktif dengan `sed -n 'A,Bp'`.
- Kerjakan SATU fase per sesi. Jangan mengerjakan fase lain.
- Di awal sesi baca `PROGRESS.md`. Di akhir sesi perbarui `PROGRESS.md` (fase selesai, keputusan, masalah terbuka).
- Commit git di akhir tiap fase dengan pesan berbahasa Indonesia.

## Stack
- Laravel versi stabil terbaru (PHP), Blade, arsitektur MVC.
- Database: PostgreSQL di Supabase, dipakai HANYA sebagai Postgres. Autentikasi, otorisasi, dan logika tetap di Laravel (jangan pakai Supabase Auth atau API REST Supabase).
- Koneksi: pakai driver `pgsql` Laravel dengan `sslmode=require`. Migrasi dijalankan lewat koneksi langsung atau session pooler (port 5432). Jangan pakai transaction pooler (port 6543) untuk migrasi karena tidak mendukung prepared statement/sesi.
- Keamanan Supabase: semua tabel di skema `public` terbuka lewat Data API bila tidak diberi RLS. Aktifkan Row Level Security di SEMUA tabel tanpa policy (Laravel memakai role `postgres` yang tidak terkena RLS), sehingga data NIK tidak bisa dibaca lewat anon key.
- Kredensial Supabase hanya di `.env` (jangan commit). `.env.example` berisi placeholder.
- Tailwind CSS via Vite (bawaan Laravel). Tanpa Breeze/Jetstream: autentikasi dibuat manual karena tabel `pengguna` kustom.
- Pengujian: Behat + Mink dengan skenario Gherkin dari SRS Bab VI. Jika integrasi Behat–Laravel macet lebih dari 30 menit, ganti ke feature test PHPUnit yang memetakan skenario Gherkin yang sama, lalu catat di `PROGRESS.md`.
- PDF dokumen: `barryvdh/laravel-dompdf`.

## Bahasa: SEMUA berbahasa Indonesia
Nama tabel, kolom, model, controller, route, view, variabel, komentar, teks UI, pesan validasi, dan pesan commit. Contoh: `Pengguna`, `LaporanKematian`, `LaporanController`, route `laporan.ajukan`, view `resources/views/laporan/ajukan.blade.php`.
Locale `id`, timezone `Asia/Jakarta`, siapkan `lang/id` untuk pesan validasi.
Hanya sintaks PHP/Laravel (`class`, `function`, `Route::`, dll.) yang tetap bahasa Inggris.

## Arsitektur (mengikuti Class Diagram Bab XI, pola BCE)
- Controller: LoginController, RegistrasiController, ProfilController, LaporanController, StatusPengajuanController, VerifikasiController, RekonsiliasiController, TandaTanganController.
- Layanan eksternal dibuat sebagai INTERFACE + implementasi Mock (data dummy), diikat lewat service provider/konfigurasi: `SIAKService`, `FaskesService`, `TTEService`, `NotifikasiService`.
- Entity = model Eloquent sesuai `docs/DATABASE.md`.

## Aturan bisnis yang tidak boleh dilanggar (SRS 2.6 dan Bab XII)
- Peran: `pemohon`, `verifikator`, `pejabat`. Akses dibatasi middleware peran.
- Verifikator dan Pejabat login memakai NIP; Pemohon sesuai use case Login.
- Kredensial TTE (PIN/OTP) TIDAK PERNAH disimpan di database. Salah 3 kali berturut-turut mengunci TTE selama 15 menit.
- Jika layanan eksternal gagal/down: jangan tolak otomatis. Tandai berkas untuk verifikasi manual dan catat ke `log_audit`.
- `log_audit` bersifat append-only (tidak ada update/delete lewat aplikasi).
- Kata sandi di-hash, CSRF aktif, validasi sisi server, berkas diunggah hanya PDF/JPG/PNG dengan batas ukuran.
- SIAK adalah sumber data kependudukan; SATUDUKA tidak menggantikannya.

## Definisi selesai sebuah fase
1. `php artisan migrate:fresh --seed` sukses.
2. Fitur fase berjalan di browser sesuai use case dan mockup.
3. Test fase hijau.
4. `PROGRESS.md` diperbarui dan commit dibuat.
5. Beri laporan singkat (maks 8 baris) plus langkah cek manual, lalu BERHENTI menunggu perintah "lanjut".
