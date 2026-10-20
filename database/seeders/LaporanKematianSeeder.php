<?php

namespace Database\Seeders;

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use Illuminate\Database\Seeder;

/**
 * 10 laporan kematian dummy yang tersebar pada seluruh status pengajuan,
 * masing-masing dilampiri 3 sampai 4 berkas pendukung.
 */
class LaporanKematianSeeder extends Seeder
{
    /**
     * Sebaran status pengajuan untuk 10 laporan.
     *
     * @var list<string>
     */
    private const SEBARAN_STATUS = [
        LaporanKematian::STATUS_DRAF,
        LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
        LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
        LaporanKematian::STATUS_SELESAI,
        LaporanKematian::STATUS_DIKEMBALIKAN,
        LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
        LaporanKematian::STATUS_SELESAI,
        LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
    ];

    public function run(): void
    {
        $pemohonDemo = Pengguna::where('email', 'pemohon@demo.satuduka.id')->firstOrFail();

        $pemohonLain = Pengguna::where('peran', Pengguna::PERAN_PEMOHON)
            ->where('email', '!=', 'pemohon@demo.satuduka.id')
            ->orderBy('id_pengguna')
            ->get()
            ->values();

        foreach (self::SEBARAN_STATUS as $index => $status) {
            // Enam pengajuan pertama milik pemohon demo, sisanya dibagi ke
            // dua pemohon lainnya.
            $pemohon = $index < 6
                ? $pemohonDemo
                : $pemohonLain[($index - 6) % max($pemohonLain->count(), 1)];

            $laporan = LaporanKematian::factory()->denganStatus($status)->create([
                'id_pengguna' => $pemohon->id_pengguna,
            ]);

            // 3 sampai 4 berkas pendukung per laporan.
            $jumlahBerkas = 3 + ($index % 2);

            for ($i = 0; $i < $jumlahBerkas; $i++) {
                BerkasPendukung::factory()->create(['id_laporan' => $laporan->id_laporan]);
            }
        }
    }
}
