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

- ~~`.env` lokal masih memakai `APP_FALLBACK_LOCALE=en` dan `APP_FAKER_LOCALE=en_US`~~ — sudah diperbaiki.
- Port `5432` (session pooler) terblokir jaringan lokal, sehingga jalur migrasi sesuai
  `AGENTS.md` belum dapat diverifikasi di lingkungan ini.
- Verifikasi `php artisan migrate:fresh --seed` terhadap Supabase dan pengecekan browser
  belum dilakukan pada fase ini.

### Catatan Perbaikan Fase 0

- `resources/views/tata_letak/utama.blade.php`: dibersihkan dari pembungkus PowerShell
  (`New-Item`, `@"`, `"@ | Set-Content`) sehingga kini berisi HTML Blade murni.
- `progress.md` (isi masih terbungkus pembungkus PowerShell dan menyebut Laravel 11) diganti
  menjadi `PROGRESS.md` dengan isi yang sesuai kenyataan.

---

## Fase 1 — Database

- **Status:** Selesai (2026-10-20), dilanjutkan dengan perbaikan berdasarkan keputusan
  pemilik proyek (lihat bagian "Keputusan Pemilik Proyek" di bawah).
- **Ringkasan:** Tujuh tabel aplikasi, model Eloquent, factory, seeder, migration RLS, dan
  tes relasi telah dibuat. `php artisan migrate:fresh --seed` berhasil dijalankan terhadap
  Supabase dan `php artisan test` hijau (21 test, 75 asersi).
- **Rincian lengkap, ketidakkonsistenan SRS, dan pertanyaan:** `docs/REKOMENDASI.md`
  (keputusan pemilik proyek sudah tercatat pada bagian di bawah).

### Skema yang dibuat

| Tabel | Kunci utama | Relasi keluar |
|---|---|---|
| `pengguna` | `id_pengguna` | 1:N `laporan_kematian`, 1:N `hasil_verifikasi`, 1:N `dokumen_resmi`, 1:N `log_audit` |
| `laporan_kematian` | `id_laporan` | N:1 `pengguna` (pemohon), 1:N `berkas_pendukung`, 1:N `hasil_verifikasi`, 1:1 `dokumen_resmi` |
| `berkas_pendukung` | `id_berkas` | N:1 `laporan_kematian` |
| `hasil_verifikasi` | `id_verifikasi` | N:1 `laporan_kematian`, N:1 `pengguna` (verifikator), 1:N `anomali_dokumen` |
| `anomali_dokumen` | `id_anomali` | N:1 `hasil_verifikasi` |
| `dokumen_resmi` | `id_dokumen` | N:1 `laporan_kematian` (unik, 1:1), N:1 `pengguna` (pejabat) |
| `log_audit` | `id_log` | N:1 `pengguna` (nullable) |

Tabel sesi/cache/job bawaan Laravel tetap dipertahankan (`sessions`,
`password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`).

### Keputusan Teknis Fase 1

- **Tabel `users` bawaan Laravel dihapus**; autentikasi memakai tabel `pengguna`
  (`config/auth.php` -> `App\Models\Pengguna`, `getAuthIdentifierName()` =
  `id_pengguna`, `getAuthPasswordName()` = `kata_sandi`). Model `User` dan
  `UserFactory` ikut dihapus. Alasan lengkap di `docs/REKOMENDASI.md` bagian (a).
- **Migration RLS** (`2026_10_20_000008_enable_row_level_security_table.php`) menjalankan
  `ALTER TABLE ... ENABLE ROW LEVEL SECURITY` untuk 7 tabel aplikasi dan seluruh tabel
  bawaan di skema `public`, tanpa policy. Migration dibungkus pemeriksaan driver
  (`pgsql` saja) sehingga pengujian SQLite tidak gagal. Terverifikasi: seluruh tabel
  `public` ber-RLS aktif dan `pg_policies` kosong.
- **Kompatibilitas SQLite:** `peran` memakai `enum` Laravel (menjadi
  `varchar + check` di kedua driver, bukan tipe ENUM PostgreSQL asli), `detail` memakai
  `jsonb` (Laravel menurunkannya menjadi `text` pada SQLite). Tidak ada tipe khusus
  PostgreSQL lain yang dipakai.
- **Kolom waktu:** seluruh tabel aplikasi kini memiliki `dibuat_pada` dan
  `diperbarui_pada`, kecuali `log_audit` yang hanya memiliki `dibuat_pada` dan bersifat
  append-only (model menolak update dan delete). Tabel `laporan_kematian`,
  `hasil_verifikasi`, `anomali_dokumen`, dan `dokumen_resmi` mendapat kolom tersebut
  melalui **migration baru** `2026_10_20_000009_tambah_kolom_waktu_ke_tabel_pengajuan`
  (migration lama tidak diubah). Tabel lain tetap memakai kolom waktu domainnya.
- **Relasi 1:1** `laporan_kematian` -> `dokumen_resmi` dijamin indeks unik pada
  `id_laporan`; `no_dokumen` dan `id_pengguna` pada `dokumen_resmi` nullable sampai
  proses TTE selesai.
- **Kredensial demo** (kata sandi semuanya `password123`):

  | Peran | Email | NIK / NIP |
  |---|---|---|
  | Pemohon | `pemohon@demo.satuduka.id` | NIK `9999990101900001` |
  | Verifikator | `verifikator@demo.satuduka.id` | NIP `999999991234567890` |
  | Pejabat | `pejabat@demo.satuduka.id` | NIP `999999992345678901` |

  Seluruh NIK/NIP memakai kode wilayah fiktif agar mustahil menabrak data warga asli.
- **Data contoh:** 5 pengguna, 10 laporan kematian tersebar pada 6 status, 35 berkas
  pendukung, 3 hasil verifikasi, 2 anomali (1 Terbuka, 1 Selesai), 2 dokumen resmi
  (1 Menunggu, 1 Selesai), 3 baris log audit. Data SIAK dummy (15 catatan) disimpan di
  `database/data/siak_dummy.json` (bukan tabel aplikasi) dan dibaca melalui
  `App\Support\DataSIAK`.
- **Pengujian:** SQLite in-memory (`phpunit.xml`) dengan `RefreshDatabase`; tidak pernah
  diarahkan ke Supabase. Tes relasi Tabel 8.2 ada di
  `tests/Feature/Database/RelasiBasisDataTest.php`.
- **Lingkungan:** ekstensi `pdo_sqlite` dan `sqlite3` diaktifkan pada `php.ini` lokal
  (bawaan Winget PHP 8.3 tidak mengaktifkannya) karena pengujian diwajibkan memakai
  SQLite in-memory. Koneksi ke Supabase tetap memakai port `6543` + emulate prepares
  seperti pada Fase 0 (tidak diubah).
- **Catatan:** paket `laravel/pao` (alat bantu keluaran JSON untuk agen AI) terpasang
  sendiri dari `composer.json` dan `composer.lock` selama sesi ini; paket ini sudah
  tidak diperlukan dan `php artisan test` kini menampilkan keluaran Pest biasa.
  `composer validate` dan `composer install --dry-run` memastikan berkas tetap sinkron
  (tidak ada paket yang perlu dipasang/dilepas).

### Target Database yang Diverifikasi

Sebelum `migrate:fresh` dijalankan, target diperiksa (tanpa password):
host `aws-0-ap-northeast-1.pooler.supabase.com`, port `6543`, basis data `postgres`,
user `postgres.grurcuiuzmnozciyjhiy` (project ref `grurcuiuzmnozciyjhiy`).
Isi database sebelum wiped hanya tabel skeleton Laravel dari Fase 0 dan seluruhnya
kosong, sehingga dipastikan ini proyek Supabase SATUDUKA.

### Keputusan Pemilik Proyek (hasil baca `docs/REKOMENDASI.md`, 2026-10-20)

| No | Keputusan | Dampak pada Fase 1 |
|---|---|---|
| 1 | Email **wajib** diisi saat registrasi (deviasi dari use case 5.2.1 yang hanya meminta NIK dan kata sandi) | `pengguna.email` sudah NOT NULL UNIQUE; penyimpangan dicatat di sini dan akan dicatat juga di view registrasi pada Fase 2 |
| 2 | Penghitung kunci 15 menit (login dan TTE) disimpan di **cache**, bukan tabel; setiap penguncian dicatat ke `log_audit` | Dibuat `App\Services\LayananPenguncian` (cache + TTL 15 menit + catat ke log audit) |
| 3 | Enkripsi NIK **ditunda ke Fase 9**; pencarian NIK dipusatkan pada satu method | Dibuat `Pengguna::cariByNik()` (bersama `cariByNip()`, `cariByEmail()`, `cariDenganNikAtauNip()`) sebagai satu-satunya jalur pencarian NIK |
| 4 | Status `Dalam Rekonsiliasi Anomali` dan `Pending - Menunggu Perbaikan Berkas Pelapor` **resmi** menjadi status pengajuan | Sudah menjadi konstanta dan masuk `LaporanKematian::DAFTAR_STATUS`; kini resmi, bukan status internal |
| 5 | Status anomali `Ditolak - Anomali Data Fatal` **dipertahankan** | Sudah menjadi `AnomaliDokumen::STATUS_DITOLAK_PERMANEN` dan masuk daftar status |
| 6 | Tambah `dibuat_pada` dan `diperbarui_pada` pada `laporan_kematian`, `hasil_verifikasi`, `anomali_dokumen`, `dokumen_resmi` lewat **migration baru** | Migration `2026_10_20_000009_tambah_kolom_waktu_ke_tabel_pengajuan`; model, factory, tes, dan `docs/DATABASE.md` diperbarui |
| 7 | Nomor pengajuan tetap `SD-YYYYMMDD-NNNN` tanpa kode wilayah, dan `NNNN` dibuat aman dari race condition | Dibuat `App\Services\LayananNomorPengajuan` (advisory lock `pg_advisory_xact_lock` pada PostgreSQL + indeks unik) |
| 8 | Tabel notifikasi dibuat **nanti pada Fase 6** | Tidak ada perubahan skema pada fase ini |
| 9 | Data SIAK dummy **diperbanyak pada Fase 6** | `database/data/siak_dummy.json` tetap 15 catatan untuk sekarang |
| 10 | Penghapusan tabel `users` **disetujui** | Migration bawaan diganti nama menjadi `0001_01_01_000000_create_sessions_and_password_reset_tokens_table.php` |

**Tambahan dari pemilik proyek:**

- **Login satu formulir "NIK atau NIP":** identitas 16 digit dibaca sebagai `nik`,
  identitas 18 digit dibaca sebagai `nip`. Flux ini dipakai
  `Pengguna::cariDenganNikAtauNip()` pada Fase 2.
- **Reset kata sandi lewat surel yang terdaftar** (surel dicari dari NIK/NIP yang
  diketik). `MAIL_MAILER=log` untuk mode demo; `password_reset_tokens` tetap dipakai.
- **`LayananAudit::catat(...)` dibuat** (`app/Services/LayananAudit.php`) dan dipakai
  untuk seluruh aksi penting. Saat ini dipakai pada seeder log audit dan pada
  pencatatan penguncian; Fase 2 sampai Fase 8 wajib memakainya.
- **Akun demo:** `pemohon@demo.satuduka.id` (NIK `9999990101900001`),
  `verifikator@demo.satuduka.id` (NIP `999999991234567890`),
  `pejabat@demo.satuduka.id` (NIP `999999992345678901`); kata sandi semuanya
  `password123`.

### Masalah Terbuka (Fase 1)

- `FORCE ROW LEVEL SECURITY` belum diaktifkan; saat ini RLS hanya bergantung pada
  kenyataan bahwa role `postgres` adalah pemilik tabel sehingga RLS diabaikan untuk
  role tersebut. Pengaman tambahan di level database dikerjakan di Fase 9.
- Optimistic locking untuk rekonsiliasi anomali belum ada: kolom `diperbarui_pada`
  kini sudah tersedia pada `anomali_dokumen` dan `laporan_kematian`, tetapi pemeriksaan
  versi masih harus dirancang pada Fase 7.
- Jumlah data SIAK dummy masih 15 catatan dan belum mencakup NIK pelapor; diperbanyak
  pada Fase 6.
- Batas nomor urut harian `NNNN` adalah 9999 per hari; bila someday terlampaui perlu
  nomor dengan panjang lebih (format `SD-YYYYMMDD-NNNN` tetap berlaku).

## Fase 2 - Autentikasi (Registrasi, Login, Dashboard Per Peran)

- **Status:** Selesai (2026-10-20). `php artisan test` hijau: **54 passed (229 assertions)**;
  `php artisan migrate:fresh --seed` sukses di Supabase (termasuk migration baru
  `2026_10_20_000010_tambah_remember_token_ke_pengguna`); kolom `pengguna.remember_token`
  terverifikasi ada dan RLS tetap aktif pada seluruh tabel skema `public`.

### Yang dibangun

- `RegistrasiController` (use case 5.2.1): formulir NIK + alamat surel + kata sandi +
  konfirmasi; NIK diverifikasi pada data kependudukan (`App\Support\DataSIAK`); nama lengkap
  diambil dari SIAK; hanya peran `pemohon` yang bisa mendaftar sendiri.
- `LoginController` (use case 5.2.2): satu formulir "NIK / NIP" untuk semua peran
  (`Pengguna::cariDenganNikAtauNip`), opsi "Ingat Saya", penghitungan gagal via
  `LayananPenguncian` (kunci 15 menit setelah 5 gagal berturut-turut), keluar (logout).
- `AturUlangKataSandiController`: NIK/NIP → tautan dikirim ke **surel terdaftar** akun
  (`MAIL_MAILER=log` untuk demo) → formulir kata sandi baru dari tautan surel. Surel
  berbahasa Indonesia via `App\Notifications\TautanAturUlangKataSandi`.
- `DashboardController` + 3 kerangka dashboard (pemohon/verifikator/pejabat) dengan
  navigasi placeholder sesuai fase berikutnya; middleware peran `App\Http\Middleware\PastikanPeran`
  (alias `peran:pemohon` dll.) terdaftar di `bootstrap/app.php` beserta pengalihan tamu →
  `route('masuk')` dan pengguna terautentikasi → `route('dashboard')`.
- Aksi `registrasi`, `masuk`, `masuk_gagal`, `percobaan_masuk_saat_terkunci`, `keluar`,
  `minta_atur_ulang_kata_sandi(_gagal)`, `atur_ulang_kata_sandi` dicatat ke `log_audit`
  lewat `LayananAudit::catat(...)`.
- `config/auth.php`: provider/broker dinamai `pengguna` (menggantikan `users` bawaan).
- View: `autentikasi/{masuk,daftar,lupa_kata_sandi,atur_ulang_kata_sandi}.blade.php` mengikuti
  mockup gambar 7.1/7.2 (kartu terpusat, ikon mata tampilkan kata sandi, tautan silang).
- Gherkin `features/login.feature` (10 skenario) dan `features/register.feature` (9 skenario)
  dari Bab VI, dipetakan ke `tests/Feature/Autentikasi/{MasukTest,DaftarTest,
  AturUlangKataSandiTest,DashboardTest}.php` (Behat–Mink tetap diganti Pest, keputusan Fase 0).

### Keputusan Teknis & Penyimpangan Fase 2

- **NFR-02 tidak menyebut jumlah percobaan gagal** (hanya "terlalu banyak percobaan login
  gagal" + kunci 15 menit; TTE pada NFR-03 = 3 kali). Dipilih **5 percobaan** untuk login
  (`LayananPenguncian::MAKS_GAGAL_LOGIN = 5`); jumlah ini keputusan implementasi, bukan
  angka SRS. TTE tetap 3 sesuai NFR-03 (dipakai pada Fase 8).
- **Pesan kegagalan login generik** "NIK/NIP atau kata sandi salah" untuk identitas tak
  dikenal maupun kata sandi salah. Use case 5.2.2 alur 4.d membedakan "NIK/NIP tidak
  ditemukan" dan "Kata sandi tidak cocok", tetapi user story 6.2.1 memakai satu pesan umum;
  pesan umum dipilih agar tidak membocorkan keberadaan akun.
- **Kolom surel wajib** saat registrasi (keputusan pemilik proyek no. 1); pesan baru
  "Format alamat surel tidak valid" / "Surel sudah terdaftar" tidak ada di SRS.
- **Nama lengkap diambil dari data SIAK** berdasarkan NIK (formulir registrasi tidak punya
  kolom nama). Konsekuensi: NIK pendaftar harus ada di `database/data/siak_dummy.json`
  (mis. `9999990101900115`); NIK akun demo pemohon `9999990101900001` TIDAK ada di SIAK
  karena akun seed melewati verifikasi registrasi. Fase 6 menambah NIK pelapor ke SIAK dummy.
- **`DataSIAK` dipakai langsung** di controller registrasi; Fase 6 mengalihkannya ke
  `SIAKService` sesuai arsitektur BCE.
- **Kolom `pengguna.remember_token`** ditambahkan lewat migration baru
  (`2026_10_20_000010`) sebagai IMPROVISASI: tidak ada di PDM, tetapi dibutuhkan Laravel
  untuk fitur "Ingat Saya" (use case 5.2.2 alur 4.b "Menyimpan sesi pengguna pada perangkat").
  Tercatat di `docs/DATABASE.md`.
- **Label kolom login "NIK / NIP"** (mockup menulis "Nomor Induk Kependudukan") karena satu
  formulir melayani NIK dan NIP; branding UI memakai SATUDUKA (mockup memakai "SKD").
- **Alur reset kata sandi menyimpang dari user story 6.2.1**: setelah "Kirim", sistem
  mengirim tautan ke surel terdaftar (bukan menampilkan formulir kata sandi baru langsung),
  sesuai keputusan pemilik proyek. Pesan akhir tetap persis SRS: "Kata sandi berhasil
  direset. Silakan login."
- **Pesan "Kata sandi tidak cocok"** (use case 5.2.1) dipakai untuk konfirmasi tidak cocok;
  user story 6.2.2 punya varian kalimat lain, dipilih yang dari use case.
- Gotcha pengujian: `assertSessionHasErrors()` mengonsumsi flash galat sehingga GET
  berikutnya tidak lagi menampilkan pesan; untuk asersi tampilan gunakan
  `followRedirects($this->post(...))` setelah GET awal (agar `back()` menunjuk halaman form).

### Masalah Terbuka (Fase 2)

- Penghitungan gagal login dikunci per identitas, belum per kombinasi identitas+IP;
  penguatan rate limit dikerjakan pada Fase 9.
- Pesan "NIK/NIP tidak ditemukan" pada formulir lupa kata sandi membuka risiko enumerasi
  akun; diterima untuk demo, dicatat untuk peninjauan Fase 9.
- Halaman 403 bawaan Laravel masih berbahasa Inggris; kustomisasi halaman galat masuk
  Fase 9 (penguatan).
