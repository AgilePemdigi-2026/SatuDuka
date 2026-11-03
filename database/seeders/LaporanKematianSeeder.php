<?php

namespace Database\Seeders;

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Support\DataSIAK;
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

        // Nama almarhum diambil berurutan dari data SIAK agar setiap laporan
        // memiliki nama berbeda dan tidak ada nama yang kembar.
        $dataSiak = DataSIAK::semua();

        foreach (self::SEBARAN_STATUS as $index => $status) {
            // Enam pengajuan pertama milik pemohon demo, sisanya dibagi ke
            // dua pemohon lainnya.
            $pemohon = $index < 6
                ? $pemohonDemo
                : $pemohonLain[($index - 6) % max($pemohonLain->count(), 1)];

            $atribut = ['id_pengguna' => $pemohon->id_pengguna];

            if (isset($dataSiak[$index])) {
                $atribut['nama_almarhum'] = $dataSiak[$index]['nama_lengkap'];
                $atribut['nik_almarhum'] = $dataSiak[$index]['nik'];
            }

            // Nomor pengajuan baru terbit saat pengajuan dikirim, bukan pada draf.
            if ($status === LaporanKematian::STATUS_DRAF) {
                $atribut['no_pengajuan'] = null;
            }

            if ($status === LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI) {
                $atribut['catatan_revisi'] = $index === 2
                    ? 'KTP pelapor tidak terbaca. Silakan unggah ulang dengan kualitas gambar yang lebih jelas.'
                    : 'Kartu Keluarga belum menampilkan data yang diperlukan. Silakan unggah ulang berkas yang sesuai.';
            } elseif ($status === LaporanKematian::STATUS_DIKEMBALIKAN) {
                $atribut['catatan_revisi'] = 'Dokumen dikembalikan oleh pejabat karena perlu perbaikan format atau kelengkapan data.';
            }

            $laporan = LaporanKematian::factory()->denganStatus($status)->create($atribut);

            // 3 sampai 4 berkas pendukung per laporan.
            $jumlahBerkas = 3 + ($index % 2);

            for ($i = 0; $i < $jumlahBerkas; $i++) {
                BerkasPendukung::factory()->create(['id_laporan' => $laporan->id_laporan]);
            }
        }

        $dataSkenarioLengkap = $dataSiak[count(self::SEBARAN_STATUS)] ?? DataSIAK::acak();
        $laporanLengkap = LaporanKematian::factory()
            ->denganStatus(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
            ->create([
                'id_pengguna' => $pemohonDemo->id_pengguna,
                'nama_pelapor' => 'Pemohon Demo - Skenario Berkas Lengkap',
                'nama_almarhum' => $dataSkenarioLengkap['nama_lengkap'] ?? 'Data Demo',
                'nik_almarhum' => $dataSkenarioLengkap['nik'] ?? '9999990101900999',
            ]);

        foreach (BerkasPendukung::DAFTAR_JENIS as $jenisDokumen) {
            BerkasPendukung::factory()->create([
                'id_laporan' => $laporanLengkap->id_laporan,
                'jenis_dokumen' => $jenisDokumen,
                'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
            ]);
        }
    }
}