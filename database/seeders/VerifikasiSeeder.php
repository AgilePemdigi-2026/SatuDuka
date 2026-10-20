<?php

namespace Database\Seeders;

use App\Models\AnomaliDokumen;
use App\Models\DokumenResmi;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use Illuminate\Database\Seeder;

/**
 * Data dummy hasil verifikasi, anomali dokumen, dan dokumen resmi.
 *
 * - 3 hasil verifikasi
 * - 2 anomali dokumen (1 Terbuka, 1 Selesai)
 * - 2 dokumen resmi (1 Menunggu, 1 Selesai)
 */
class VerifikasiSeeder extends Seeder
{
    public function run(): void
    {
        $verifikator = Pengguna::where('peran', Pengguna::PERAN_VERIFIKATOR)->firstOrFail();
        $pejabat = Pengguna::where('peran', Pengguna::PERAN_PEJABAT)->firstOrFail();

        // Tiga laporan yang sudah masuk tahap verifikasi.
        $laporanTerverifikasi = LaporanKematian::query()
            ->whereIn('status_pengajuan', [
                LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
                LaporanKematian::STATUS_SELESAI,
                LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            ])
            ->orderBy('id_laporan')
            ->limit(3)
            ->get();

        $hasilVerifikasi = $laporanTerverifikasi->map(function (LaporanKematian $laporan, int $index) use ($verifikator): HasilVerifikasi {
            return HasilVerifikasi::factory()->create([
                'id_laporan' => $laporan->id_laporan,
                'id_pengguna' => $verifikator->id_pengguna,
                'status_verifikasi' => match ($index) {
                    0 => HasilVerifikasi::STATUS_DITERIMA,
                    1 => HasilVerifikasi::STATUS_ANOMALI,
                    default => HasilVerifikasi::STATUS_PERLU_REVISI,
                },
            ]);
        });

        // Dua anomali dokumen: satu terbuka dan satu sudah selesai direkonsiliasi.
        AnomaliDokumen::factory()->create([
            'id_verifikasi' => $hasilVerifikasi[1]->id_verifikasi,
            'jenis_anomali' => AnomaliDokumen::JENIS_NIK_GANDA,
            'deskripsi' => 'NIK almarhum tercatat ganda pada data master kependudukan.',
            'status_rekonsiliasi' => AnomaliDokumen::STATUS_TERBUKA,
            'tanggal_rekonsiliasi' => null,
        ]);

        AnomaliDokumen::factory()->selesai()->create([
            'id_verifikasi' => $hasilVerifikasi[2]->id_verifikasi,
            'jenis_anomali' => AnomaliDokumen::JENIS_TANGGAL_KEMATIAN,
            'deskripsi' => 'Tanggal kematian pada surat keterangan berbeda satu hari dengan data master.',
            'status_rekonsiliasi' => AnomaliDokumen::STATUS_SELESAI,
            'tanggal_rekonsiliasi' => now(),
        ]);

        // Dua dokumen resmi pada dua laporan berbeda (relasi 1:1).
        $laporanUntukDokumen = LaporanKematian::query()
            ->whereIn('status_pengajuan', [
                LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
                LaporanKematian::STATUS_SELESAI,
            ])
            ->orderBy('id_laporan')
            ->limit(2)
            ->get();

        DokumenResmi::factory()->create([
            'id_laporan' => $laporanUntukDokumen[0]->id_laporan,
            'id_pengguna' => null,
            'no_dokumen' => null,
            'tanggal_terbit' => null,
            'status_ttd' => DokumenResmi::STATUS_MENUNGGU,
            'file_dokumen' => null,
            'alasan_pengembalian' => null,
        ]);

        DokumenResmi::factory()->selesai()->create([
            'id_laporan' => $laporanUntukDokumen[1]->id_laporan,
            'id_pengguna' => $pejabat->id_pengguna,
        ]);
    }
}
