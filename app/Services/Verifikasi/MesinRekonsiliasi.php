<?php

namespace App\Services\Verifikasi;

use App\Models\AnalisisAi;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Services\LayananAudit;
use App\Support\NikTanggalLahir;
use Illuminate\Support\Str;

/**
 * Mesin rekonsiliasi: menyatukan NIK, berkas, dan temuan AI.
 *
 * Mesin hanya mendeteksi anomali dan merekomendasikan tindakan. Mesin tidak
 * mengubah status pengajuan dan tidak menulis ke `laporan_kematian`.
 * Pembanding data kependudukan (SIAK) tidak lagi dipakai: yang dibandingkan
 * hanyalah isian pemohon dengan hasil ekstraksi berkas yang diunggah.
 * Kegagalan layanan AI menghasilkan anomali `LAYANAN_TIDAK_TERSEDIA` (info)
 * agar verifikasi tetap dapat dilanjutkan secara manual.
 */
class MesinRekonsiliasi
{
    public const KODE_PENGAJUAN_GANDA = 'PENGAJUAN_GANDA';

    public const KODE_KASUS_KHUSUS = 'KASUS_KHUSUS';

    public const KODE_NIK_TIDAK_VALID = 'NIK_TIDAK_VALID';

    public const KODE_JENIS_KELAMIN_TIDAK_COCOK = 'JENIS_KELAMIN_TIDAK_COCOK';

    public const KODE_TANGGAL_TIDAK_KONSISTEN = 'TANGGAL_TIDAK_KONSISTEN';

    public const KODE_NAMA_TIDAK_COCOK = 'NAMA_TIDAK_COCOK';

    public const KODE_TERLAMBAT_TANPA_SPTJM = 'TERLAMBAT_TANPA_SPTJM';

    public const KODE_BERKAS_TIDAK_LENGKAP = 'BERKAS_TIDAK_LENGKAP';

    public const KODE_DOKUMEN_TIDAK_TERBACA = 'DOKUMEN_TIDAK_TERBACA';

    public const KODE_NAMA_BEDA_TIPIS = 'NAMA_BEDA_TIPIS';

    public const KODE_LAYANAN_TIDAK_TERSEDIA = 'LAYANAN_TIDAK_TERSEDIA';

    public function __construct(
        private readonly LayananValidasiNik $validasiNik,
        private readonly LayananAturanBerkas $aturanBerkas,
        private readonly LayananPencocokNama $pencocokNama,
    ) {}

    public function analisis(LaporanKematian $laporan, bool $catatAudit = true): HasilAnalisis
    {
        $anomali = $this->dariAturanBerkas($laporan);

        $anomali = array_merge($anomali, $this->dariValidasiNik($laporan));

        [$anomaliAi, $aiGagal] = $this->dariAnalisisAi($laporan, $catatAudit);

        $anomali = array_merge($anomali, $anomaliAi);
        $anomali = $this->urutkanDanUnikkan($anomali);

        $utama = $anomali === []
            ? (string) config('verifikasi.rekomendasi_utama_bersih', 'Siap disetujui')
            : $anomali[0]->rekomendasi;

        return new HasilAnalisis(
            anomali: $anomali,
            rekomendasiUtama: $utama,
            layananTidakTersedia: $aiGagal,
        );
    }

    /**
     * Kunci tindakan yang disarankan berdasarkan anomali berkeparahan tertinggi.
     */
    public function tindakanDisarankan(HasilAnalisis $hasil): string
    {
        if ($hasil->anomali === []) {
            return (string) config('verifikasi.tindakan_bersih', 'setujui');
        }

        $peta = (array) config('verifikasi.tindakan_anomali', []);

        return (string) ($peta[$hasil->anomali[0]->kode] ?? 'minta_perbaikan');
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
     * Memeriksa struktur NIK dan konsistensinya dengan isian pemohon.
     *
     * Tanggal lahir diturunkan dari segmen NIK (DDMMYY dengan kode jenis
     * kelamin), bukan dari data kependudukan, sehingga pemeriksaan usia tetap
     * berjalan tanpa layanan eksternal.
     *
     * @return list<AnomaliVerifikasi>
     */
    private function dariValidasiNik(LaporanKematian $laporan): array
    {
        $hasil = $this->validasiNik->periksa(
            nik: trim((string) $laporan->nik_almarhum),
            jenisKelamin: $laporan->jenis_kelamin,
            tanggalLahir: NikTanggalLahir::dariNik((string) $laporan->nik_almarhum),
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
     * Membaca temuan AI yang sudah tersimpan tanpa memanggil layanan AI lagi.
     *
     * @return array{0: list<AnomaliVerifikasi>, 1: bool}
     */
    private function dariAnalisisAi(LaporanKematian $laporan, bool $catatAudit = true): array
    {
        try {
            $catatan = AnalisisAi::query()
                ->where('id_laporan', $laporan->id_laporan)
                ->orderBy('id_analisis')
                ->get();
        } catch (\Throwable $kesalahan) {
            if ($catatAudit) {
                $this->catatLayananGagal(LogAudit::LAYANAN_AI, $laporan, $kesalahan->getMessage());
            }

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

                $kunci = $temuan['kunci'] ?? null;

                // Nama dibandingkan dengan hasil ekstraksi dokumen, bukan data
                // kependudukan. Kemiripan menentukan beda tipis atau tidak cocok.
                if ($kunci === 'nama_almarhum') {
                    $anomali[] = $this->anomaliNama(
                        (string) ($temuan['nilai_pemohon'] ?? ''),
                        (string) ($temuan['nilai_ekstraksi'] ?? ''),
                    );

                    continue;
                }

                $kode = match ($kunci) {
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

            if ($catatAudit) {
                $this->catatLayananGagal(
                    LogAudit::LAYANAN_AI,
                    $laporan,
                    'Analisis AI sebelumnya gagal atau tidak tersedia.',
                );
            }
        }

        return [$anomali, $layananGagal];
    }

    /**
     * Menilai nama isian pemohon terhadap nama hasil ekstraksi dokumen.
     */
    private function anomaliNama(string $namaPemohon, string $namaEkstraksi): AnomaliVerifikasi
    {
        if (trim($namaPemohon) === '' || trim($namaEkstraksi) === '') {
            return $this->buatAnomali(
                self::KODE_NAMA_TIDAK_COCOK,
                'Nama pada pengajuan tidak cocok dengan nama pada dokumen yang diunggah.',
            );
        }

        $cocok = $this->pencocokNama->cocokkan($namaPemohon, $namaEkstraksi);

        if ($cocok->kategori === HasilPencocokanNama::KATEGORI_BEDA_TIPIS) {
            return $this->buatAnomali(
                self::KODE_NAMA_BEDA_TIPIS,
                'Nama pada pengajuan sedikit berbeda dengan nama pada dokumen yang diunggah.',
            );
        }

        return $this->buatAnomali(
            self::KODE_NAMA_TIDAK_COCOK,
            'Nama pada pengajuan tidak cocok dengan nama pada dokumen yang diunggah.',
        );
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
