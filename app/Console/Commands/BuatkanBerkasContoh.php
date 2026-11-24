<?php

namespace App\Console\Commands;

use App\Models\BerkasPendukung;
use App\Services\Dokumen\LayananBerkasContoh;
use Illuminate\Console\Command;

/**
 * Mengisi berkas pendukung yang belum punya isi di disk dengan dokumen dummy
 * (PDF teks / gambar OCR) sehingga alur analisis AI dapat diuji nyata.
 */
class BuatkanBerkasContoh extends Command
{
    /**
     * Nama dan opsi perintah.
     *
     * @var string
     */
    protected $signature = 'berkas:contoh
        {--semua : Buat ulang isi seluruh berkas, bukan hanya yang belum ada}
        {--laporan= : Batasi pada id_laporan tertentu}
        {--dry-run : Tampilkan jumlah tanpa menulis berkas}';

    /**
     * Deskripsi perintah.
     *
     * @var string
     */
    protected $description = 'Buat isi dokumen dummy untuk berkas pendukung yang belum ada di disk';

    /**
     * Jalankan perintah.
     */
    public function handle(LayananBerkasContoh $layanan): int
    {
        if ($this->option('dry-run')) {
            $rencana = $layanan->isiSemuaYangKurang(
                $this->pilihLaporan(),
            );

            $this->table(
                ['Dibuat', 'Lewati (sudah ada)', 'Gagal'],
                [[$rencana['dibuat'], $rencana['lewati'], $rencana['gagal']]],
            );

            return self::SUCCESS;
        }

        if ($this->option('semua')) {
            $jumlah = 0;
            $kueri = BerkasPendukung::query();

            if ($idLaporan = $this->pilihLaporan()) {
                $kueri->where('id_laporan', $idLaporan);
            }

            foreach ($kueri->orderBy('id_berkas')->get() as $berkas) {
                if ($layanan->isi($berkas, paksa: true)) {
                    $jumlah++;
                }
            }

            $this->info('Isi '.$jumlah.' berkas dibuat ulang.');

            return self::SUCCESS;
        }

        $hasil = $layanan->isiSemuaYangKurang($this->pilihLaporan());

        $this->info('Berkas contoh baru: '.$hasil['dibuat']
            .'; sudah ada: '.$hasil['lewati']
            .'; gagal: '.$hasil['gagal'].'.');

        return self::SUCCESS;
    }

    private function pilihLaporan(): ?int
    {
        $nilai = trim((string) $this->option('laporan'));

        return $nilai === '' ? null : (int) $nilai;
    }
}
