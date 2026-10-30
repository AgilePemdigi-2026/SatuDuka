<?php

namespace App\Services\Verifikasi;

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use Illuminate\Support\Carbon;

/**
 * Memeriksa kelengkapan berkas, batas 30 hari, jenis kasus, dan pengajuan ganda.
 *
 * Layanan ini hanya mendeteksi anomali. Status laporan tidak diubah dan
 * keputusan tetap berada pada verifikator atau pemohon.
 */
class LayananAturanBerkas
{
    public const KODE_BERKAS_TIDAK_LENGKAP = 'BERKAS_TIDAK_LENGKAP';

    public const KODE_TERLAMBAT_TANPA_SPTJM = 'TERLAMBAT_TANPA_SPTJM';

    public const KODE_TANGGAL_TIDAK_KONSISTEN = 'TANGGAL_TIDAK_KONSISTEN';

    public const KODE_KASUS_KHUSUS = 'KASUS_KHUSUS';

    public const KODE_PENGAJUAN_GANDA = 'PENGAJUAN_GANDA';

    /**
     * Status pengajuan lain yang dianggap masih aktif atau sudah selesai.
     *
     * Draf belum dianggap pengajuan, sedangkan status yang sudah ditolak atau
     * dikembalikan tidak menghalangi pengajuan baru.
     *
     * @var list<string>
     */
    public const STATUS_PENGAJUAN_GANDA = [
        LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        LaporanKematian::STATUS_DALAM_REKONSILIASI,
        LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
        LaporanKematian::STATUS_MENUNGGU_PERBAIKAN,
        LaporanKematian::STATUS_SELESAI,
    ];

    /**
     * @return list<AnomaliAturanBerkas>
     */
    public function periksa(LaporanKematian $laporan): array
    {
        $anomali = [];

        $terunggah = $laporan->berkasPendukung()
            ->where('status_unggahan', BerkasPendukung::STATUS_BERHASIL)
            ->pluck('jenis_dokumen')
            ->unique()
            ->values()
            ->all();

        $kurang = BerkasPendukung::jenisYangKurang(
            $terunggah,
            false,
            LaporanKematian::JENIS_KASUS_NORMAL,
        );

        if ((bool) config('verifikasi.wajib_saksi', false)) {
            foreach ([
                BerkasPendukung::JENIS_KTP_SAKSI_1,
                BerkasPendukung::JENIS_KTP_SAKSI_2,
            ] as $jenisSaksi) {
                if (! in_array($jenisSaksi, $terunggah, true)) {
                    $kurang[] = $jenisSaksi;
                }
            }
        }

        $jenisKasus = $laporan->jenis_kasus ?? LaporanKematian::JENIS_KASUS_NORMAL;
        $kasusKhusus = $jenisKasus !== LaporanKematian::JENIS_KASUS_NORMAL;

        if ($kasusKhusus) {
            foreach ((array) config('verifikasi.berkas_bersyarat.jenis_kasus_khusus', []) as $jenis) {
                if (! in_array($jenis, $terunggah, true)) {
                    $kurang[] = $jenis;
                }
            }
        }

        if ($kurang !== []) {
            $anomali[] = new AnomaliAturanBerkas(
                self::KODE_BERKAS_TIDAK_LENGKAP,
                AnomaliAturanBerkas::KEPARAHAN_FATAL,
                'Berkas wajib belum lengkap: '.implode(', ', array_unique($kurang)).'.',
                'Unggah seluruh berkas yang kurang sebelum pengajuan dikirim.',
            );
        }

        $batasHari = (int) config('verifikasi.batas_hari_pelaporan', 30);

        if ($laporan->tanggal_kematian !== null) {
            $kematian = Carbon::parse($laporan->tanggal_kematian)->startOfDay();
            $pengajuan = Carbon::parse($laporan->tanggal_pengajuan ?? now())->startOfDay();

            if ($kematian->greaterThan($pengajuan)) {
                $anomali[] = new AnomaliAturanBerkas(
                    self::KODE_TANGGAL_TIDAK_KONSISTEN,
                    AnomaliAturanBerkas::KEPARAHAN_FATAL,
                    'Tanggal kematian tidak boleh setelah tanggal pengajuan.',
                    'Periksa kembali tanggal kematian dan tanggal pengajuan.',
                );
            } else {
                $selisihHari = (int) $kematian->diffInDays($pengajuan, false);

                if ($selisihHari > $batasHari) {
                    $berkasKeterlambatan = (array) config('verifikasi.berkas_bersyarat.keterlambatan', []);
                    $punyaBerkasKeterlambatan = array_intersect($berkasKeterlambatan, $terunggah) !== [];

                    if (! $punyaBerkasKeterlambatan) {
                        $anomali[] = new AnomaliAturanBerkas(
                            self::KODE_TERLAMBAT_TANPA_SPTJM,
                            AnomaliAturanBerkas::KEPARAHAN_PERINGATAN,
                            'Pengajuan dikirim '.$selisihHari.' hari setelah kematian, melebihi batas '
                                .$batasHari.' hari, tanpa SPTJM Kebenaran Data Kematian.',
                            'Lengkapi SPTJM atau lanjutkan verifikasi manual oleh verifikator.',
                        );
                    }
                }
            }
        }

        if ($kasusKhusus) {
            $anomali[] = new AnomaliAturanBerkas(
                self::KODE_KASUS_KHUSUS,
                AnomaliAturanBerkas::KEPARAHAN_INFO,
                'Jenis kasus '.$jenisKasus.' memerlukan jalur verifikasi manual.',
                'Teruskan pengajuan ke verifikator untuk verifikasi khusus.',
            );
        }

        if (! blank($laporan->nik_almarhum)
            && $this->adaPengajuanGanda($laporan->nik_almarhum, (int) $laporan->id_laporan)) {
            $anomali[] = new AnomaliAturanBerkas(
                self::KODE_PENGAJUAN_GANDA,
                AnomaliAturanBerkas::KEPARAHAN_FATAL,
                'NIK almarhum sudah ada pada pengajuan lain yang masih aktif atau sudah selesai.',
                'Periksa pengajuan sebelumnya; satu almarhum hanya boleh memiliki satu pengajuan aktif.',
            );
        }

        return $anomali;
    }

    public function adaPengajuanGanda(string $nik, ?int $kecualiIdLaporan = null): bool
    {
        $nik = trim($nik);

        if ($nik === '') {
            return false;
        }

        return LaporanKematian::query()
            ->where('nik_almarhum', $nik)
            ->when(
                $kecualiIdLaporan !== null,
                fn ($kueri) => $kueri->where('id_laporan', '!=', $kecualiIdLaporan),
            )
            ->whereIn('status_pengajuan', self::STATUS_PENGAJUAN_GANDA)
            ->exists();
    }
}
