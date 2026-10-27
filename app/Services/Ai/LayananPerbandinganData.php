<?php

namespace App\Services\Ai;

use App\Models\LaporanKematian;
use App\Support\DataSIAK;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Membandingkan isian pemohon dengan hasil ekstraksi AI dan data SIAK.
 *
 * Perbandingan hanya menghasilkan saran. Status pengajuan tidak diubah.
 */
class LayananPerbandinganData
{
    /**
     * Kolom yang dibandingkan beserta labelnya.
     *
     * @var array<string, string>
     */
    private const KOLOM = [
        'nama_almarhum' => 'Nama Almarhum/Almarhumah',
        'nik' => 'NIK',
        'tanggal_kematian' => 'Tanggal Kematian',
        'tempat_kematian' => 'Tempat Kematian',
    ];

    /**
     * @param  array{kolom?: array<string, array{nilai: string|null, keyakinan: float}>}  $ekstraksi
     * @return array{
     *     kolom: list<array<string, mixed>>,
     *     jumlah_beda: int,
     *     ada_beda: bool,
     *     skor: float
     * }
     */
    public function bandingkan(LaporanKematian $laporan, array $ekstraksi): array
    {
        $siak = $laporan->nik_almarhum !== null ? DataSIAK::cariNik($laporan->nik_almarhum) : null;
        $hasilEkstraksi = $ekstraksi['kolom'] ?? [];

        $baris = [];
        $jumlahBeda = 0;
        $totalKeyakinan = 0.0;
        $jumlahKeyakinan = 0;

        foreach (self::KOLOM as $kunci => $label) {
            $nilaiPemohon = $this->nilaiPemohon($laporan, $kunci);
            $nilaiEkstraksi = $hasilEkstraksi[$kunci]['nilai'] ?? null;
            $keyakinan = (float) ($hasilEkstraksi[$kunci]['keyakinan'] ?? 0);
            $nilaiSiak = $this->nilaiSiak($siak, $kunci);

            $catatan = [];
            $status = 'cocok';

            if ($nilaiEkstraksi === null || trim((string) $nilaiEkstraksi) === '') {
                $status = 'tidak terbaca';
            } else {
                if (! $this->sama($kunci, $nilaiPemohon, $nilaiEkstraksi)) {
                    $status = 'beda';
                    $catatan[] = 'Hasil ekstraksi berbeda dengan isian pemohon.';
                }

                if ($nilaiSiak !== null && ! $this->sama($kunci, $nilaiPemohon, $nilaiSiak)) {
                    $status = 'beda';
                    $catatan[] = 'Data SIAK berbeda dengan isian pemohon.';
                }
            }

            if ($status === 'beda') {
                $jumlahBeda++;
            }

            if ($nilaiEkstraksi !== null && trim((string) $nilaiEkstraksi) !== '') {
                $totalKeyakinan += max(0.0, min(1.0, $keyakinan));
                $jumlahKeyakinan++;
            }

            $baris[] = [
                'kunci' => $kunci,
                'label' => $label,
                'nilai_pemohon' => $this->tampilkan($kunci, $nilaiPemohon),
                'nilai_ekstraksi' => $this->tampilkan($kunci, $nilaiEkstraksi),
                'nilai_siak' => $this->tampilkan($kunci, $nilaiSiak),
                'status' => $status,
                'keyakinan' => round(max(0.0, min(1.0, $keyakinan)), 4),
                'catatan' => $catatan,
            ];
        }

        return [
            'kolom' => $baris,
            'jumlah_beda' => $jumlahBeda,
            'ada_beda' => $jumlahBeda > 0,
            'skor' => $jumlahKeyakinan === 0 ? 0.0 : round($totalKeyakinan / $jumlahKeyakinan, 4),
        ];
    }

    private function nilaiPemohon(LaporanKematian $laporan, string $kunci): ?string
    {
        return match ($kunci) {
            'nama_almarhum' => $laporan->nama_almarhum,
            'nik' => $laporan->nik_almarhum,
            'tanggal_kematian' => $laporan->tanggal_kematian?->toDateString(),
            'tempat_kematian' => $laporan->tempat_kematian,
            default => null,
        };
    }

    /**
     * SIAK hanya memuat nama dan NIK pada data dummy ini.
     *
     * @param  array<string, string>|null  $siak
     */
    private function nilaiSiak(?array $siak, string $kunci): ?string
    {
        if ($siak === null) {
            return null;
        }

        return match ($kunci) {
            'nama_almarhum' => $siak['nama_lengkap'] ?? null,
            'nik' => $siak['nik'] ?? null,
            default => null,
        };
    }

    private function sama(string $kunci, ?string $kiri, ?string $kanan): bool
    {
        $kiri = $this->normalisasi($kunci, $kiri);
        $kanan = $this->normalisasi($kunci, $kanan);

        return $kiri !== '' && $kiri === $kanan;
    }

    private function normalisasi(string $kunci, ?string $nilai): string
    {
        if ($nilai === null) {
            return '';
        }

        if ($kunci === 'tanggal_kematian') {
            return $this->normalisasiTanggal($nilai);
        }

        if ($kunci === 'nik') {
            return preg_replace('/\D+/', '', $nilai) ?? '';
        }

        return Str::of($nilai)
            ->lower()
            ->replaceMatches('/[^\pL\pN]+/u', ' ')
            ->squish()
            ->toString();
    }

    private function normalisasiTanggal(string $nilai): string
    {
        $teks = trim($nilai);

        if ($teks === '') {
            return '';
        }

        $bulan = [
            'januari' => 'January',
            'februari' => 'February',
            'maret' => 'March',
            'april' => 'April',
            'mei' => 'May',
            'juni' => 'June',
            'juli' => 'July',
            'agustus' => 'August',
            'september' => 'September',
            'oktober' => 'October',
            'november' => 'November',
            'desember' => 'December',
        ];

        $teks = str_ireplace(array_keys($bulan), array_values($bulan), $teks);

        try {
            return Carbon::parse($teks)->toDateString();
        } catch (\Throwable) {
            return Str::lower($teks);
        }
    }

    private function tampilkan(string $kunci, ?string $nilai): ?string
    {
        if ($nilai === null || trim($nilai) === '') {
            return null;
        }

        if ($kunci === 'tanggal_kematian') {
            $normal = $this->normalisasiTanggal($nilai);

            try {
                return Carbon::parse($normal)->translatedFormat('j F Y');
            } catch (\Throwable) {
                return $nilai;
            }
        }

        return $nilai;
    }
}
