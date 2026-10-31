# SKD (Sistem Kependudukan Digital)

SKD adalah aplikasi web untuk digitalisasi pencatatan dan administrasi kematian di Kota Yogyakarta. Aplikasi membantu pemohon mengajukan laporan, verifikator memeriksa berkas, dan pejabat menerbitkan dokumen.

## Fungsi utama

- **Pemohon** — membuat dan memperbaiki pengajuan laporan kematian, mengunggah dokumen, memantau status, serta melihat dokumen hasil.
- **Verifikator** — memeriksa berkas dan data, menjalankan analisis AI sebagai saran, mencatat anomali, serta meminta perbaikan atau meneruskan pengajuan.
- **Pejabat** — menandatangani dan menerbitkan dokumen kematian secara elektronik.

## Stack

- Laravel (PHP) dengan arsitektur MVC dan Blade
- Tailwind CSS v4 melalui Vite
- PostgreSQL untuk aplikasi; SQLite untuk pengujian
- Dompdf untuk dokumen PDF
- Layanan eksternal SIAK, faskes, TTE, notifikasi, dan AI memakai antarmuka dengan implementasi mock

## Menjalankan aplikasi

1. `composer install`
2. `npm install`
3. Salin `.env.example` menjadi `.env`
4. Sesuaikan koneksi database pada `.env`
5. `php artisan key:generate`
6. `php artisan migrate --seed`
7. `npm run dev`
8. `php artisan serve`

## Pengujian

```bash
php artisan test
```

## Evaluasi aturan verifikasi

```bash
php artisan aturan:evaluasi
```

Perintah ini menjalankan `MesinRekonsiliasi` pada 62 kasus fiktif berlabel di
`database/data/kasus_uji_aturan.json`, menghitung presisi dan recall per kode anomali,
membuat matriks kebingungan kategori nama, dan menulis hasilnya ke
`docs/EVALUASI_ATURAN.md`. Dataset kasus uji dibuat dari rancangan aturan, bukan dari data
kependudukan nyata. Ambang nama di `config/verifikasi.php` dievaluasi dengan 39 pasangan di
`database/data/pasangan_nama_uji.json`; ambang saat ini dipertahankan sampai ada konfirmasi
Dukcapil.

## Akun demo

Akun berikut tersedia setelah menjalankan `php artisan migrate --seed`.

| Peran | Email | Kata sandi |
|---|---|---|
| Pemohon | `pemohon@demo.satuduka.id` | `password123` |
| Verifikator | `verifikator@demo.satuduka.id` | `password123` |
| Pejabat | `pejabat@demo.satuduka.id` | `password123` |

## Struktur singkat

- `app/Services/` — logika domain di luar controller: `LayananAudit`, `LayananNomorPengajuan`, `LayananPenguncian`, serta `Ai/` (`AIService`, `MockAIService`, `OpenAiService`, `LayananAnalisisAi`, `LayananPerbandinganData`).
- `config/verifikasi.php` — ambang batas, daftar berkas wajib/opsional, dan bobot aturan verifikasi yang dapat diubah tanpa menyentuh kode aturan.
