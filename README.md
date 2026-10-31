# SKD (Sistem Kependudukan Digital) — SATUDUKA

SKD/SATUDUKA adalah aplikasi web untuk digitalisasi pencatatan dan administrasi kematian di Kota Yogyakarta. Aplikasi membantu pemohon mengajukan laporan, verifikator memeriksa berkas, dan pejabat menerbitkan dokumen.

## Fungsi utama

- **Pemohon** — registrasi/login, membuat dan memperbaiki pengajuan laporan kematian, mengunggah dokumen, memantau status, dan mengunduh dokumen bila sudah terbit.
- **Verifikator** — memeriksa berkas dan data, menjalankan analisis AI sebagai saran, mencatat anomali, merekonsiliasi data, serta meminta perbaikan atau meneruskan pengajuan.
- **Pejabat** — menandatangani dokumen dengan PIN/OTP dan tanda tangan visual, serta menerbitkan atau mengembalikan dokumen.

## Stack

- Laravel (PHP 8.3) dengan arsitektur MVC dan Blade
- Tailwind CSS v4 melalui Vite
- PostgreSQL (Supabase) untuk aplikasi; SQLite untuk pengujian
- Dompdf untuk dokumen PDF
- Layanan eksternal SIAK, faskes, TTE, notifikasi, dan AI memakai antarmuka dengan implementasi mock

Ekstensi PHP **GD** wajib aktif (dipakai memproses gambar tanda tangan).

## Menjalankan aplikasi

1. `composer install`
2. `npm install`
3. Salin `.env.example` menjadi `.env`
4. Sesuaikan koneksi database pada `.env`
5. `php artisan key:generate`
6. `php artisan migrate --seed`
7. `npm run dev`
8. `php artisan serve`

Untuk pengembangan cepat tanpa PostgreSQL: `composer db:sqlite`.

## Akun demo

Dibuat oleh seeder (`php artisan migrate --seed`). Kata sandi seluruh akun demo ada di
`database/seeders/PenggunaSeeder.php` (jangan menyalinnya ke dokumentasi publik).

| Peran | Identitas masuk |
|---|---|
| Pemohon | NIK/email akun pemohon demo |
| Verifikator | NIP akun verifikator demo |
| Pejabat | NIP akun pejabat demo |

PIN TTE demo juga dibaca dari seeder/konfigurasi (`SATUDUKA_TTE_PIN_DUMMY`), tidak ditulis di
README ini.

## Pengujian

```bash
php artisan test          # seluruh tes
vendor/bin/pint --test    # gaya kode
```

Pengujian memakai SQLite in-memory sehingga tidak menyentuh database pengembangan.

## Perintah pemeliharaan

```bash
php artisan aturan:evaluasi              # evaluasi mesin aturan + docs/EVALUASI_ATURAN.md
php artisan dokumen:terbitkan --semua    # pulihkan PDF dokumen resmi yang belum ada
php artisan berkas:bersihkan --dry-run   # daftar berkas yatim
```

## Evaluasi aturan verifikasi

`php artisan aturan:evaluasi` menjalankan `MesinRekonsiliasi` pada 62 kasus fiktif berlabel di
`database/data/kasus_uji_aturan.json`, menghitung presisi dan recall per kode anomali, membuat
matriks kebingungan kategori nama, dan menulis hasilnya ke `docs/EVALUASI_ATURAN.md`. Dataset
kasus uji dibuat dari rancangan aturan, bukan dari data kependudukan nyata. Ambang nama di
`config/verifikasi.php` dievaluasi dengan 39 pasangan di `database/data/pasangan_nama_uji.json`;
ambang saat ini dipertahankan sampai ada konfirmasi Dukcapil.

## Batas simulasi

- **SIAK** hanya membaca data dummy `database/data/siak_dummy.json`; bukan data kependudukan nyata.
- **AI** memakai `MockAIService` secara default; hasil hanya saran dan tidak mengubah status.
- **TTE** memakai `MockTTEService` dengan PIN dummy pada lokal/pengujian; driver mock ditolak pada
  produksi. Tanda tangan bersifat simulasi, bukan TTE tersertifikasi BSrE.
- **Dokumen** yang dihasilkan adalah simulasi akademik, bukan akta resmi, dan tidak memiliki
  kekuatan hukum.
- Status pengajuan hanya berubah lewat aksi pengguna; mesin aturan tidak menolak otomatis.

## Keamanan dan audit

- Kata sandi di-hash; PIN/OTP dan tanda tangan tidak disimpan pada log, audit, atau sesi.
- Login dikunci 5 kali gagal (15 menit); TTE dikunci 3 kali gagal (15 menit).
- Rute sensitif dibatasi laju (`config/satuduka.php`, dapat dilonggarkan untuk demo lewat
  `SATUDUKA_RATE_LIMIT_LONGGAR=true`).
- `log_audit` bersifat append-only: model menolak update/delete dan PostgreSQL memakai trigger.
- Berkas unggahan disimpan di disk privat dan hanya disajikan lewat controller ber-otorisasi.
- Audit otorisasi rute: `docs/AUDIT_OTORISASI.md`.

## Struktur singkat

- `app/Services/` — logika domain: `LayananAudit`, `LayananNomorPengajuan`, `LayananPenguncian`,
  `Dokumen/` (PDF, nomor dokumen, tanda tangan visual), `Tte/`, serta `Ai/`.
- `config/verifikasi.php` — ambang batas, daftar berkas wajib/opsional, dan bobot aturan verifikasi.
- `config/satuduka.php` — identitas instansi, driver TTE, batas laju rute sensitif.
