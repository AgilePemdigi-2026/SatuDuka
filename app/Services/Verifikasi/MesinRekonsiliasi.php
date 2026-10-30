<?php

namespace App\Services\Verifikasi;

use App\Models\AnalisisAi;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\ReferensiWilayah;
use App\Services\LayananAudit;
use App\Support\DataSIAK;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Mesin rekonsiliasi: menyatukan NIK, berkas, nama, SIAK, dan temuan AI.
 *
 * Mesin hanya mendeteksi anomali dan merekomendasikan tindakan. Mesin tidak
 * mengubah status pengajuan dan tidak menulis ke `laporan_kematian`.
 * Kegagalan layanan eksternal menghasilkan anomali `LAYANAN_TIDAK_TERSEDIA`
 * (info) agar verifikasi tetap dapat dilanjutkan secara manual.
 */
class MesinRekonsiliasi
{
    public const KODE_PENGAJUAN_GANDA = 'PENGAJUAN_GANDA';

    public const KODE_SIAK_SUDAH_MENINGGAL = 'SIAK_SUDAH_MENINGGAL';

    public const KODE_KASUS_KHUSUS = 'KASUS_KHUSUS';

    public const KODE_NIK_TIDAK_VALID = 'NIK_TIDAK_VALID';

    public const KODE_NIK_TIDAK_DITEMUKAN = 'NIK_TIDAK_DITEMUKAN';

    public const KODE_JENIS_KELAMIN_TIDAK_COCOK = 'JENIS_KELAMIN_TIDAK_COCOK';

    public const KODE_TANGGAL_TIDAK_KONSISTEN = 'TANGGAL_TIDAK_KONSISTEN';

    public const KODE_NAMA_TIDAK_COCOK = 'NAMA_TIDAK_COCOK';

    public const KODE_TERLAMBAT_TANPA_SPTJM = 'TERLAMBAT_TANPA_SPTJM';

    public const KODE_BERKAS_TIDAK_LENGKAP = 'BERKAS_TIDAK_LENGKAP';

    public const KODE_DOKUMEN_TIDAK_TERBACA = 'DOKUMEN_TIDAK_TERBACA';

    public const KODE_NAMA_BEDA_TIPIS = 'NAMA_BEDA_TIPIS';

    public const KODE_ALAMAT_DI_LUAR_WILAYAH = 'ALAMAT_DI_LUAR_WILAYAH';

    public const KODE_LAYANAN_TIDAK_TERSEDIA = 'LAYANAN_TIDAK_TERSEDIA';

    /**
     * Pemanggil data SIAK; null berarti memakai DataSIAK::cariNik.
     *
     * @var callable(string): array<string, string>|null
     */
    private $pencariSiak;

    public function __construct(
        private readonly LayananValidasiNik $validasiNik,
        private readonly LayananAturanBerkas $aturanBerkas,
        private readonly LayananPencocokNama $pencocokNama,
        ?callable $pencariSiak = null,
    ) {
        $this->pencariSiak = $pencariSiak;
    }

    public function analisis(LaporanKematian $laporan): HasilAnalisis
    {
        $anomali = $this->dariAturanBerkas($laporan);
        $layananTidakTersedia = false;

        [$siak, $siakGagal] = $this->ambilSiak($laporan);

        if ($siakGagal) {
            $layananTidakTersedia = true;
            $anomali[] = $this->buatAnomali(
                self::KODE_LAYANAN_TIDAK_TERSEDIA,
                'Data SIAK tidak dapat diakses; verifikasi dilanjutkan secara manual.',
            );
        }

        $anomali = array_merge($anomali, $this->dariValidasiNik($laporan, $siak));
        $anomali = array_merge($anomali, $this->dariDataSiak($laporan, $siak, $siakGagal));

        [$anomaliAi, $aiGagal] = $this->dariAnalisisAi($laporan);

        if ($aiGagal) {
            $layananTidakTersedia = true;
        }

        $anomali = array_merge($anomali, $anomaliAi);
        $anomali = $this->urutkanDanUnikkan($anomali);

        $utama = $anomali === []
            ? (string) config('verifikasi.rekomendasi_utama_bersih', 'Siap disetujui')
            : $anomali[0]->rekomendasi;

        return new HasilAnalisis(
            anomali: $anomali,
            rekomendasiUtama: $utama,
            layananTidakTersedia: $layananTidakTersedia,
        );
    }

    /**
     * Menyimpan snapshot hasil analisis dan alasan override ke hasil verifikasi.
     */
    public function simpanKeputusan(
        HasilVerifikasi $hasilVerifikasi,
        HasilAnalisis $analisis,
        ?string $alasanOverride = null,
    ): HasilVerifikasi {
        $hasilVerifikasi->update([
            'deteksi_otomatis' => $analisis->toArray(),
            'alasan_override' => $alasanOverride,
        ]);

        return $hasilVerifikasi->refresh();
    }

    /**
     * @return list<AnomaliVerifikasi>
     */
    private function dariAturanBerkas(LaporanKematian $laporan): array
    {
        $anomali = [];

        foreach ($this->aturanBerkas->periksa($laporan) as $temuan) {
            $anomali[] = $this->buatAnomali($temuan->kode, $temuan->pesan);
        }

        return $anomali;
    }

    /**
     * @return array{0: array<string, string>|null, 1: bool}
     */
    private function ambilSiak(LaporanKematian $laporan): array
    {
        $nik = trim((string) $laporan->nik_almarhum);

        if ($nik === '') {
            return [null, false];
        }

        try {
            $siak = $this->pencariSiak !== null
                ? ($this->pencariSiak)($nik)
                : DataSIAK::cariNik($nik);
        } catch (\Throwable $kesalahan) {
            $this->catatLayananGagal(LogAudit::LAYANAN_SIAK, $laporan, $kesalahan->getMessage());

            return [null, true];
        }

        return [is_array($siak) ? $siak : null, false];
    }

    /**
     * @param  array<string, string>|null  $siak
     * @return list<AnomaliVerifikasi>
     */
    private function dariValidasiNik(LaporanKematian $laporan, ?array $siak): array
    {
        $hasil = $this->validasiNik->periksa(
            nik: trim((string) $laporan->nik_almarhum),
            jenisKelamin: $siak['jenis_kelamin'] ?? null,
            tanggalLahir: $this->tanggalLahirSiak($siak),
            tanggalKematian: $laporan->tanggal_kematian,
        );

        $anomali = [];
        $pesanNik = [];

        foreach ($hasil->pelanggaran as $pelanggaran) {
            if ($pelanggaran->kode === LayananValidasiNik::KODE_NIK_JENIS_KELAMIN_TIDAK_COCOK) {
                $anomali[] = $this->buatAnomali(self::KODE_JENIS_KELAMIN_TIDAK_COCOK, $pelanggaran->pesan);
            } elseif ($pelanggaran->kode === LayananValidasiNik::KODE_TANGGAL_TIDAK_KONSISTEN) {
                $anomali[] = $this->buatAnomali(self::KODE_TANGGAL_TIDAK_KONSISTEN, $pelanggaran->pesan);
            } else {
                $pesanNik[] = $pelanggaran->pesan;
            }
        }

        if ($pesanNik !== []) {
            $anomali[] = $this->buatAnomali(
                self::KODE_NIK_TIDAK_VALID,
                implode(' ', array_unique($pesanNik)),
            );
        }

        return $anomali;
    }

    /**
     * @param  array<string, string>|null  $siak
     * @return list<AnomaliVerifikasi>
     */
    private function dariDataSiak(LaporanKematian $laporan, ?array $siak, bool $siakGagal): array
    {
        if ($siak === null) {
            if ($siakGagal) {
                return [];
            }

            return [$this->buatAnomali(
                self::KODE_NIK_TIDAK_DITEMUKAN,
                'NIK almarhum tidak ditemukan pada data SIAK.',
            )];
        }

        $anomali = [];

        if (DataSIAK::sudahMeninggal($siak)) {
            $anomali[] = $this->buatAnomali(
                self::KODE_SIAK_SUDAH_MENINGGAL,
                'Status kependudukan pada SIAK sudah menandai penduduk sebagai meninggal.',
            );
        }

        $jenisKelaminLaporan = $this->normalisasiJenisKelamin($laporan->jenis_kelamin);
        $jenisKelaminSiak = $this->normalisasiJenisKelamin($siak['jenis_kelamin'] ?? null);

        if ($jenisKelaminLaporan !== null
            && $jenisKelaminSiak !== null
            && $jenisKelaminLaporan !== $jenisKelaminSiak) {
            $anomali[] = $this->buatAnomali(
                self::KODE_JENIS_KELAMIN_TIDAK_COCOK,
                'Jenis kelamin pada pengajuan tidak cocok dengan data SIAK.',
            );
        }

        if ($this->alamatDiLuarWilayah($siak)) {
            $anomali[] = $this->buatAnomali(
                self::KODE_ALAMAT_DI_LUAR_WILAYAH,
                'Alamat SIAK tidak memuat nama kecamatan Kota Yogyakarta.',
            );
        }

        $namaSiak = $siak['nama_lengkap'] ?? null;

        if (! blank($namaSiak)) {
            $cocok = $this->pencocokNama->cocokkan((string) $laporan->nama_almarhum, $namaSiak);

            if ($cocok->kategori === HasilPencocokanNama::KATEGORI_TIDAK_COCOK) {
                $anomali[] = $this->buatAnomali(
                    self::KODE_NAMA_TIDAK_COCOK,
                    'Nama pada pengajuan tidak cocok dengan nama pada SIAK.',
                );
            } elseif ($cocok->kategori === HasilPencocokanNama::KATEGORI_BEDA_TIPIS) {
                $anomali[] = $this->buatAnomali(
                    self::KODE_NAMA_BEDA_TIPIS,
                    'Nama pada pengajuan sedikit berbeda dengan nama pada SIAK.',
                );
            }
        }

        return $anomali;
    }

    /**
     * Membaca temuan AI yang sudah tersimpan tanpa memanggil layanan AI lagi.
     *
     * @return array{0: list<AnomaliVerifikasi>, 1: bool}
     */
    private function dariAnalisisAi(LaporanKematian $laporan): array
    {
        try {
            $catatan = AnalisisAi::query()
                ->where('id_laporan', $laporan->id_laporan)
                ->orderBy('id_analisis')
                ->get();
        } catch (\Throwable $kesalahan) {
            $this->catatLayananGagal(LogAudit::LAYANAN_AI, $laporan, $kesalahan->getMessage());

            return [[
                $this->buatAnomali(
                    self::KODE_LAYANAN_TIDAK_TERSEDIA,
                    'Layanan AI tidak dapat diakses; verifikasi dilanjutkan secara manual.',
                ),
            ], true];
        }

        $anomali = [];
        $dokumenGagal = false;
        $layananGagal = false;

        foreach ($catatan as $baris) {
            if ($baris->status === AnalisisAi::STATUS_GAGAL) {
                $layananGagal = true;

                if ($baris->jenis !== AnalisisAi::JENIS_RINGKASAN) {
                    $dokumenGagal = true;
                }

                continue;
            }

            if ($baris->jenis !== AnalisisAi::JENIS_PERBANDINGAN) {
                continue;
            }

            $kolom = $baris->hasil['kolom'] ?? null;

            if (! is_array($kolom)) {
                continue;
            }

            foreach ($kolom as $temuan) {
                if (! is_array($temuan)) {
                    continue;
                }

                $status = $temuan['status'] ?? null;

                if ($status === 'tidak terbaca') {
                    $anomali[] = $this->buatAnomali(
                        self::KODE_DOKUMEN_TIDAK_TERBACA,
                        'Sebagian isi dokumen tidak terbaca oleh AI; verifikator perlu membaca manual.',
                    );

                    continue;
                }

                if ($status !== 'beda') {
                    continue;
                }

                $kode = match ($temuan['kunci'] ?? null) {
                    'nama_almarhum' => self::KODE_NAMA_TIDAK_COCOK,
                    'nik' => self::KODE_NIK_TIDAK_VALID,
                    'tanggal_kematian' => self::KODE_TANGGAL_TIDAK_KONSISTEN,
                    default => null,
                };

                if ($kode !== null) {
                    $anomali[] = $this->buatAnomali(
                        $kode,
                        'Hasil ekstraksi AI berbeda dengan isian pemohon pada '
                            .($temuan['label'] ?? 'salah satu kolom').'.',
                    );
                }
            }
        }

        if ($dokumenGagal) {
            $anomali[] = $this->buatAnomali(
                self::KODE_DOKUMEN_TIDAK_TERBACA,
                'Dokumen tidak berhasil dibaca oleh AI; verifikator perlu membaca manual.',
            );
        }

        if ($layananGagal) {
            $anomali[] = $this->buatAnomali(
                self::KODE_LAYANAN_TIDAK_TERSEDIA,
                'Analisis AI tidak tersedia; verifikasi dilanjutkan secara manual.',
            );

            $this->catatLayananGagal(
                LogAudit::LAYANAN_AI,
                $laporan,
                'Analisis AI sebelumnya gagal atau tidak tersedia.',
            );
        }

        return [$anomali, $layananGagal];
    }

    /**
     * @param  array<string, string>|null  $siak
     */
    private function tanggalLahirSiak(?array $siak): ?Carbon
    {
        $tanggal = $siak['tanggal_lahir'] ?? null;

        if (blank($tanggal)) {
            return null;
        }

        try {
            return Carbon::parse($tanggal);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, string>  $siak
     */
    private function alamatDiLuarWilayah(array $siak): bool
    {
        $alamat = Str::lower((string) ($siak['alamat'] ?? ''));

        $daftar = ReferensiWilayah::query()->pluck('nama_kecamatan')
            ->filter(static fn (string $nama): bool => trim($nama) !== '');

        if ($daftar->isEmpty()) {
            return false;
        }

        foreach ($daftar as $namaKecamatan) {
            if (str_contains($alamat, Str::lower((string) $namaKecamatan))) {
                return false;
            }
        }

        return true;
    }

    private function normalisasiJenisKelamin(?string $jenisKelamin): ?string
    {
        if ($jenisKelamin === null) {
            return null;
        }

        return match (strtoupper(trim($jenisKelamin))) {
            'L', 'LAKI-LAKI', 'LAKI LAKI', 'PRIA', 'MALE' => 'L',
            'P', 'PEREMPUAN', 'WANITA', 'FEMALE' => 'P',
            default => null,
        };
    }

    /**
     * @param  list<AnomaliVerifikasi>  $anomali
     * @return list<AnomaliVerifikasi>
     */
    private function urutkanDanUnikkan(array $anomali): array
    {
        $unik = [];

        foreach ($anomali as $temuan) {
            $unik[$temuan->kode] ??= $temuan;
        }

        $urutan = array_flip((array) config('verifikasi.urutan_keparahan', [
            AnomaliVerifikasi::KEPARAHAN_FATAL,
            AnomaliVerifikasi::KEPARAHAN_TINGGI,
            AnomaliVerifikasi::KEPARAHAN_SEDANG,
            AnomaliVerifikasi::KEPARAHAN_RENDAH,
            AnomaliVerifikasi::KEPARAHAN_INFO,
        ]));

        $hasil = array_values($unik);

        usort($hasil, static function (AnomaliVerifikasi $kiri, AnomaliVerifikasi $kanan) use ($urutan): int {
            $bobotKiri = $urutan[$kiri->keparahan] ?? PHP_INT_MAX;
            $bobotKanan = $urutan[$kanan->keparahan] ?? PHP_INT_MAX;

            return $bobotKiri <=> $bobotKanan;
        });

        return $hasil;
    }

    private function buatAnomali(string $kode, string $pesan): AnomaliVerifikasi
    {
        $taksonomi = (array) config('verifikasi.taksonomi_anomali.'.$kode, []);

        return new AnomaliVerifikasi(
            kode: $kode,
            keparahan: (string) ($taksonomi['keparahan'] ?? AnomaliVerifikasi::KEPARAHAN_INFO),
            pesan: $pesan,
            rekomendasi: (string) ($taksonomi['rekomendasi'] ?? 'Lanjutkan verifikasi manual.'),
        );
    }

    private function catatLayananGagal(string $layanan, LaporanKematian $laporan, string $keterangan): void
    {
        LayananAudit::catat(
            aksi: 'memeriksa_layanan_eksternal',
            objek: 'laporan_kematian',
            idObjek: $laporan->id_laporan,
            detail: [
                'layanan' => $layanan,
                'keterangan' => Str::limit($keterangan, 500),
            ],
            layananEksternal: $layanan,
            hasil: LogAudit::HASIL_GAGAL,
        );
    }
}
