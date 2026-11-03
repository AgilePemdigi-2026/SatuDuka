<?php

use App\Support\PenyelarasNik;
use Illuminate\Database\Migrations\Migration;

/**
 * Langkah 2 enkripsi NIK: isi blind index dan enkripsi nilai lama.
 *
 * Proses berjalan per potongan (chunk) dalam transaksi dan idempoten, sehingga
 * aman dijalankan ulang. Bila kunci hash wajib belum diisi pada produksi,
 * migrasi gagal dengan pesan jelas sebelum menyentuh data.
 *
 * Catatan `down()`: nilai NIK pada `pengguna` dan `laporan_kematian` dapat
 * dikembalikan ke plaintext, tetapi NIK utuh pada `analisis_ai.hasil` TIDAK
 * dapat dipulihkan karena backfill hanya menyimpan hash dan versi termasker.
 * Setelah `down()`, kolom hash dikosongkan; jalankan ulang migrasi ini untuk
 * mengisinya kembali.
 */
return new class extends Migration
{
    public function up(): void
    {
        PenyelarasNik::jalankan();
    }

    public function down(): void
    {
        PenyelarasNik::kembalikan();
    }
};
