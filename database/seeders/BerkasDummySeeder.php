<?php

namespace Database\Seeders;

use App\Services\Dokumen\LayananBerkasContoh;
use Illuminate\Database\Seeder;

/**
 * Mengisi berkas pendukung hasil seeder dengan isi dokumen dummy yang nyata
 * (PDF teks atau gambar OCR) di disk privat agar analisis AI dapat diuji
 * tanpa unggahan manual.
 */
class BerkasDummySeeder extends Seeder
{
    /**
     * Jalankan seeder.
     */
    public function run(): void
    {
        $layanan = app(LayananBerkasContoh::class);
        $hasil = $layanan->isiSemuaYangKurang();

        $pesan = 'Berkas dummy dibuat: '.$hasil['dibuat']
            .'; sudah ada: '.$hasil['lewati']
            .'; gagal: '.$hasil['gagal'].'.';

        // Properti $command null pada beberapa harness pemanggil seeder.
        $this->command?->info($pesan);
    }
}
