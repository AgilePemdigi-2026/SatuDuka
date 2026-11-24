<?php

namespace App\Services\Ai;

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;

/**
 * Memilih berkas pendukung yang layak dianalisis AI pada satu permintaan.
 *
 * Aturan (issue #12):
 * - hanya jenis dokumen yang dipakai aturan verifikasi yang dianalisis;
 * - berkas yang analisis terakhirnya berhasil dilewati, sedangkan berkas
 *   berstatus gagal atau tertunda tetap boleh dianalisis lagi;
 * - paling banyak tiga berkas per permintaan agar waktu eksekusi tidak
 *   melewati batas PHP ketika layanan AI lambat.
 *
 * Layanan ini hanya memilih; status pengajuan tidak diubah dan keputusan
 * tetap berada pada pemohon atau verifikator.
 */
class PemilihBerkasAnalisis
{
    /**
     * Jumlah berkas maksimum yang dianalisis dalam satu permintaan.
     */
    public const BATAS_PER_PERMINTAAN = 3;

    /* Alasan sebuah berkas masuk ke salah satu kelompok pemilihan. */
    public const ALASAN_RELEVAN = 'relevan';

    public const ALASAN_SUDAH_DIPERIKSA = 'sudah_diperiksa';

    public const ALASAN_TIDAK_RELEVAN = 'tidak_relevan';

    public const ALASAN_BATAS_PERMINTAAN = 'batas_per_permintaan';

    /**
     * Kelompokkan berkas berstatus unggahan berhasil milik sebuah laporan.
     *
     * @return array{
     *     akan_diperiksa: list<array{berkas: BerkasPendukung, alasan: string}>,
     *     belum_diperiksa: list<array{berkas: BerkasPendukung, alasan: string}>,
     *     dilewati: list<array{berkas: BerkasPendukung, alasan: string}>
     * }
     */
    public function pilih(LaporanKematian $laporan): array
    {
        $relevan = $this->jenisRelevan();

        $hasil = [
            'akan_diperiksa' => [],
            'belum_diperiksa' => [],
            'dilewati' => [],
        ];

        // Urutan tetap berdasarkan id berkas agar sisa berkas pada permintaan
        // berikutnya selalu sama dengan yang belum diperiksa sebelumnya.
        $daftarBerkas = $laporan->berkasPendukung()
            ->where('status_unggahan', BerkasPendukung::STATUS_BERHASIL)
            ->orderBy('id_berkas')
            ->get();

        foreach ($daftarBerkas as $berkas) {
            if (! in_array($berkas->jenis_dokumen, $relevan, true)) {
                $hasil['dilewati'][] = [
                    'berkas' => $berkas,
                    'alasan' => self::ALASAN_TIDAK_RELEVAN,
                ];

                continue;
            }

            if ($this->sudahBerhasil($berkas)) {
                $hasil['dilewati'][] = [
                    'berkas' => $berkas,
                    'alasan' => self::ALASAN_SUDAH_DIPERIKSA,
                ];

                continue;
            }

            if (count($hasil['akan_diperiksa']) >= self::BATAS_PER_PERMINTAAN) {
                $hasil['belum_diperiksa'][] = [
                    'berkas' => $berkas,
                    'alasan' => self::ALASAN_BATAS_PERMINTAAN,
                ];

                continue;
            }

            $hasil['akan_diperiksa'][] = [
                'berkas' => $berkas,
                'alasan' => self::ALASAN_RELEVAN,
            ];
        }

        return $hasil;
    }

    /**
     * Jenis dokumen yang benar-benar dipakai aturan verifikasi.
     *
     * Sumbernya sama dengan yang dibaca LayananAturanBerkas dan pemeriksaan
     * kelengkapan: bukti kematian, berkas wajib lainnya, berkas bersyarat
     * (keterlambatan dan kasus khusus), serta berkas saksi bila diwajibkan.
     * `verifikasi.berkas_opsional` sengaja tidak dipakai karena hanya untuk
     * tampilan, bukan aturan.
     *
     * @return list<string>
     */
    public function jenisRelevan(): array
    {
        $jenis = array_merge(
            (array) config('verifikasi.berkas_wajib.bukti_kematian.jenis', []),
            (array) config('verifikasi.berkas_wajib.lainnya', []),
            (array) config('verifikasi.berkas_bersyarat.keterlambatan', []),
            (array) config('verifikasi.berkas_bersyarat.jenis_kasus_khusus', []),
        );

        if ((bool) config('verifikasi.wajib_saksi', false)) {
            $jenis[] = BerkasPendukung::JENIS_KTP_SAKSI_1;
            $jenis[] = BerkasPendukung::JENIS_KTP_SAKSI_2;
        }

        return array_values(array_unique($jenis));
    }

    /**
     * Berkas dianggap selesai bila analisis terakhirnya berhasil.
     *
     * Analisis berstatus gagal atau tertunda bukan kegagalan permanen, jadi
     * berkas tersebut tetap layak dianalisis ulang.
     */
    public function sudahBerhasil(BerkasPendukung $berkas): bool
    {
        $terakhir = $berkas->analisisAi()
            ->orderByDesc('id_analisis')
            ->first();

        return $terakhir !== null && $terakhir->status === AnalisisAi::STATUS_BERHASIL;
    }
}
