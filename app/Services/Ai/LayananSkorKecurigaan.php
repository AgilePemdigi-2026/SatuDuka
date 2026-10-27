<?php

namespace App\Services\Ai;

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Support\DataSIAK;
use Illuminate\Support\Str;

/**
 * Menghitung skor kecurigaan 0-100 dengan aturan deterministik.
 *
 * Skor hanya dipakai untuk memprioritaskan pemeriksaan manual. Skor tidak
 * pernah mengubah status pengajuan dan tidak mengambil keputusan verifikasi.
 *
 * Bobot aturan (total dibatasi 100):
 * - NIK tidak cocok dengan dokumen/SIAK: 30
 * - Nama tidak cocok dengan dokumen/SIAK: 20
 * - Tanggal kematian tidak cocok dengan dokumen: 15
 * - Tempat kematian tidak cocok dengan dokumen: 10
 * - Sebagian kolom dokumen tidak terbaca: 10
 * - Berkas wajib kurang: 10
 * - Selisih tanggal kematian-pengajuan lebih dari 30 hari: 15
 * - Tanggal kematian setelah tanggal pengajuan: 20
 * - NIK tidak ditemukan pada data SIAK: 20
 */
class LayananSkorKecurigaan
{
    public const BOBOT_NIK_TIDAK_COCOK = 30;

    public const BOBOT_NAMA_TIDAK_COCOK = 20;

    public const BOBOT_TANGGAL_TIDAK_COCOK = 15;

    public const BOBOT_TEMPAT_TIDAK_COCOK = 10;

    public const BOBOT_DOKUMEN_TIDAK_TERBACA = 10;

    public const BOBOT_BERKAS_KURANG = 10;

    public const BOBOT_SELISIH_TANGGAL_JANGGAL = 15;

    public const BOBOT_TANGGAL_TIDAK_WAJAR = 20;

    public const BOBOT_NIK_TIDAK_DITEMUKAN = 20;

    public const AMBANG_SEDANG = 30;

    public const AMBANG_TINGGI = 60;

    public const BATAS_SELISIH_HARI = 30;

    /**
     * @param  array{kolom?: list<array<string, mixed>>}|null  $perbandingan
     * @return array{
     *     skor: int,
     *     alasan: list<array{kode: string, bobot: int, keterangan: string}>,
     *     kategori: string
     * }
     */
    public function hitung(LaporanKematian $laporan, ?array $perbandingan = null): array
    {
        $alasan = [];
        $skor = 0;

        $tambah = function (string $kode, int $bobot, string $keterangan) use (&$alasan, &$skor): void {
            $alasan[] = [
                'kode' => $kode,
                'bobot' => $bobot,
                'keterangan' => $keterangan,
            ];
            $skor += $bobot;
        };

        $laporan->loadMissing('berkasPendukung');

        $jenisAda = $laporan->berkasPendukung->pluck('jenis_dokumen')->all();
        $kurang = array_values(array_diff(BerkasPendukung::DAFTAR_JENIS, $jenisAda));

        if ($kurang !== []) {
            $tambah(
                'BERKAS_KURANG',
                self::BOBOT_BERKAS_KURANG,
                'Berkas wajib belum lengkap: '.implode(', ', $kurang).'.',
            );
        }

        $siak = $laporan->nik_almarhum !== null ? DataSIAK::cariNik($laporan->nik_almarhum) : null;

        if ($siak === null) {
            $tambah(
                'NIK_TIDAK_DITEMUKAN',
                self::BOBOT_NIK_TIDAK_DITEMUKAN,
                'NIK pada pengajuan tidak ditemukan pada data SIAK.',
            );
        }

        if ($perbandingan !== null) {
            $this->nilaiPerbandingan($perbandingan, $tambah);
        } elseif ($siak !== null) {
            if (! $this->samaNik($laporan->nik_almarhum, $siak['nik'] ?? null)) {
                $tambah(
                    'NIK_TIDAK_COCOK',
                    self::BOBOT_NIK_TIDAK_COCOK,
                    'NIK tidak cocok dengan data SIAK.',
                );
            }

            if (! $this->samaNama($laporan->nama_almarhum, $siak['nama_lengkap'] ?? null)) {
                $tambah(
                    'NAMA_TIDAK_COCOK',
                    self::BOBOT_NAMA_TIDAK_COCOK,
                    'Nama almarhum tidak cocok dengan data SIAK.',
                );
            }
        }

        $this->nilaiSelisihTanggal($laporan, $tambah);

        $skor = max(0, min(100, $skor));

        return [
            'skor' => $skor,
            'alasan' => $alasan,
            'kategori' => self::kategori($skor),
        ];
    }

    public static function kategori(int $skor): string
    {
        return match (true) {
            $skor >= self::AMBANG_TINGGI => 'tinggi',
            $skor >= self::AMBANG_SEDANG => 'sedang',
            default => 'rendah',
        };
    }

    /**
     * @param  array{kolom?: list<array<string, mixed>>}  $perbandingan
     * @param  callable(string, int, string): void  $tambah
     */
    private function nilaiPerbandingan(array $perbandingan, callable $tambah): void
    {
        $kolom = collect($perbandingan['kolom'] ?? [])->keyBy('kunci');

        $aturan = [
            'nik' => [
                'NIK_TIDAK_COCOK',
                self::BOBOT_NIK_TIDAK_COCOK,
                'NIK tidak cocok dengan dokumen atau data SIAK.',
            ],
            'nama_almarhum' => [
                'NAMA_TIDAK_COCOK',
                self::BOBOT_NAMA_TIDAK_COCOK,
                'Nama almarhum tidak cocok dengan dokumen atau data SIAK.',
            ],
            'tanggal_kematian' => [
                'TANGGAL_TIDAK_COCOK',
                self::BOBOT_TANGGAL_TIDAK_COCOK,
                'Tanggal kematian pada dokumen berbeda dengan isian.',
            ],
            'tempat_kematian' => [
                'TEMPAT_TIDAK_COCOK',
                self::BOBOT_TEMPAT_TIDAK_COCOK,
                'Tempat kematian pada dokumen berbeda dengan isian.',
            ],
        ];

        foreach ($aturan as $kunci => [$kode, $bobot, $keterangan]) {
            if (($kolom[$kunci]['status'] ?? null) === 'beda') {
                $tambah($kode, $bobot, $keterangan);
            }
        }

        $tidakTerbaca = $kolom->contains(
            fn (array $baris): bool => ($baris['status'] ?? null) === 'tidak terbaca',
        );

        if ($tidakTerbaca) {
            $tambah(
                'DOKUMEN_TIDAK_TERBACA',
                self::BOBOT_DOKUMEN_TIDAK_TERBACA,
                'Sebagian kolom dokumen tidak terbaca oleh layanan AI.',
            );
        }
    }

    /**
     * @param  callable(string, int, string): void  $tambah
     */
    private function nilaiSelisihTanggal(LaporanKematian $laporan, callable $tambah): void
    {
        if ($laporan->tanggal_kematian === null || $laporan->tanggal_pengajuan === null) {
            return;
        }

        $hari = (int) round(
            ($laporan->tanggal_pengajuan->getTimestamp() - $laporan->tanggal_kematian->getTimestamp()) / 86400,
        );

        if ($hari < 0) {
            $tambah(
                'TANGGAL_KEMATIAN_MASA_DEPAN',
                self::BOBOT_TANGGAL_TIDAK_WAJAR,
                'Tanggal kematian berada setelah tanggal pengajuan.',
            );

            return;
        }

        if ($hari > self::BATAS_SELISIH_HARI) {
            $tambah(
                'SELISIH_TANGGAL_JANGGAL',
                self::BOBOT_SELISIH_TANGGAL_JANGGAL,
                'Selisih tanggal kematian dan tanggal pengajuan lebih dari 30 hari.',
            );
        }
    }

    private function samaNik(?string $kiri, ?string $kanan): bool
    {
        $kiri = preg_replace('/\D+/', '', (string) $kiri) ?? '';
        $kanan = preg_replace('/\D+/', '', (string) $kanan) ?? '';

        return $kiri !== '' && $kiri === $kanan;
    }

    private function samaNama(?string $kiri, ?string $kanan): bool
    {
        $kiri = $this->normalisasiTeks($kiri);
        $kanan = $this->normalisasiTeks($kanan);

        return $kiri !== '' && $kiri === $kanan;
    }

    private function normalisasiTeks(?string $nilai): string
    {
        return Str::of((string) $nilai)
            ->lower()
            ->replaceMatches('/[^\pL\pN]+/u', ' ')
            ->squish()
            ->toString();
    }
}
