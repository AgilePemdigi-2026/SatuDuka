<?php

namespace App\Console\Commands;

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use App\Services\Dokumen\LayananDokumenResmi;
use Illuminate\Console\Command;

/**
 * Jalur pemulihan penerbitan PDF Kutipan Akta Kematian.
 *
 * Dipakai ketika penandatanganan berhasil tetapi pembuatan berkas gagal, atau
 * ketika rujukan berkas ada di basis data tetapi berkasnya hilang dari disk.
 */
class TerbitkanDokumen extends Command
{
    /**
     * Nama dan opsi perintah.
     *
     * @var string
     */
    protected $signature = 'dokumen:terbitkan
        {--laporan= : ID laporan tertentu yang dokumennya akan diterbitkan}
        {--semua : Terbitkan semua dokumen Selesai yang belum memiliki berkas}';

    /**
     * Deskripsi perintah.
     *
     * @var string
     */
    protected $description = 'Menerbitkan ulang berkas PDF dokumen resmi yang belum tersedia';

    public function handle(LayananDokumenResmi $layanan): int
    {
        $idLaporan = $this->option('laporan');
        $semua = (bool) $this->option('semua');

        if ($idLaporan === null && ! $semua) {
            $this->error('Tentukan --laporan=ID atau --semua.');

            return self::FAILURE;
        }

        $query = DokumenResmi::query()
            ->with('laporanKematian')
            ->where('status_ttd', DokumenResmi::STATUS_SELESAI)
            ->whereHas('laporanKematian', function ($subquery): void {
                $subquery->where('status_pengajuan', LaporanKematian::STATUS_SELESAI);
            });

        if ($idLaporan !== null) {
            $query->where('id_laporan', (int) $idLaporan);
        }

        $daftar = $query->orderBy('id_dokumen')->get();

        if ($daftar->isEmpty()) {
            $this->info('Tidak ada dokumen yang perlu diterbitkan.');

            return self::SUCCESS;
        }

        $berhasil = 0;
        $gagal = 0;
        $dibersihkan = 0;

        foreach ($daftar as $dokumen) {
            if ($layanan->bersihkanBerkasHilang($dokumen)) {
                $dibersihkan++;
                $this->line('Berkas hilang dibersihkan: dokumen #'.$dokumen->id_dokumen.'.');
            }

            if ($layanan->terbitkan($dokumen)) {
                $berhasil++;
                $this->line('Dokumen #'.$dokumen->id_dokumen.' siap: '.($dokumen->fresh()->no_dokumen ?? '-'));

                continue;
            }

            $gagal++;
            $this->warn('Dokumen #'.$dokumen->id_dokumen.' gagal diterbitkan; periksa log audit.');
        }

        $this->info("Selesai: {$berhasil} berhasil, {$gagal} gagal, {$dibersihkan} rujukan berkas hilang dibersihkan.");

        return $gagal === 0 ? self::SUCCESS : self::FAILURE;
    }
}
