<?php

namespace App\Services\Ai;

use App\Models\BerkasPendukung;
use App\Support\DataSIAK;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Implementasi mock yang deterministik untuk pengembangan dan pengujian.
 *
 * Kasus dipilih dari nama berkas:
 * - mengandung `tidak-terbaca`, `rusak`, atau `buram` -> kolom tidak terbaca;
 * - mengandung `beda`, `tidak-cocok`, atau `selisih` -> nilai sengaja berbeda;
 * - selain itu memakai data SIAK/laporan sebagai hasil pembacaan.
 */
class MockAIService implements AIService
{
    public function ekstrakSuratKematian(BerkasPendukung $berkas): array
    {
        $namaBerkas = Str::lower((string) $berkas->nama_file);
        $laporan = $berkas->laporanKematian;

        $dasar = $this->dataDasar($laporan?->nama_almarhum, $laporan?->nik_almarhum, $laporan?->tanggal_kematian, $laporan?->tempat_kematian);

        if (Str::contains($namaBerkas, ['tidak-terbaca', 'rusak', 'buram'])) {
            return $this->hasil([
                'nama_almarhum' => [null, 0.0],
                'nik' => [null, 0.0],
                'tanggal_kematian' => [null, 0.0],
                'tempat_kematian' => [null, 0.0],
            ], 'Dokumen tidak terbaca oleh layanan AI.');
        }

        if (Str::contains($namaBerkas, ['beda', 'tidak-cocok', 'selisih'])) {
            return $this->hasil([
                'nama_almarhum' => [$dasar['nama_almarhum'].' Berbeda', 0.91],
                'nik' => [$this->nikBerbeda($dasar['nik']), 0.93],
                'tanggal_kematian' => [$this->tanggalBerbeda($dasar['tanggal_kematian']), 0.90],
                'tempat_kematian' => ['Tempat Berbeda', 0.89],
            ]);
        }

        return $this->hasil([
            'nama_almarhum' => [$dasar['nama_almarhum'], 0.98],
            'nik' => [$dasar['nik'], 0.99],
            'tanggal_kematian' => [$dasar['tanggal_kematian'], 0.96],
            'tempat_kematian' => [$dasar['tempat_kematian'], 0.95],
        ]);
    }

    public function namaModel(): string
    {
        return 'mock-ai-v1';
    }

    /**
     * @param  array<string, array{0: string|null, 1: float}>  $kolom
     * @return array{berhasil: bool, kolom: array<string, array{nilai: string|null, keyakinan: float}>, skor: float, pesan: string|null}
     */
    private function hasil(array $kolom, ?string $pesan = null): array
    {
        $hasil = [];
        $total = 0.0;
        $jumlah = 0;

        foreach ($kolom as $kunci => [$nilai, $keyakinan]) {
            $hasil[$kunci] = [
                'nilai' => $nilai,
                'keyakinan' => round((float) $keyakinan, 4),
            ];

            if ($nilai !== null) {
                $total += (float) $keyakinan;
                $jumlah++;
            }
        }

        return [
            'berhasil' => true,
            'kolom' => $hasil,
            'skor' => $jumlah === 0 ? 0.0 : round($total / $jumlah, 4),
            'pesan' => $pesan,
        ];
    }

    /**
     * Data acuan: utamakan catatan SIAK bila NIK ditemukan.
     *
     * @return array{nama_almarhum: string, nik: string, tanggal_kematian: string|null, tempat_kematian: string}
     */
    private function dataDasar(?string $nama, ?string $nik, mixed $tanggal, ?string $tempat): array
    {
        $siak = $nik !== null && $nik !== '' ? DataSIAK::cariNik($nik) : null;

        return [
            'nama_almarhum' => $siak['nama_lengkap'] ?? $nama ?? '',
            'nik' => $siak['nik'] ?? $nik ?? '',
            'tanggal_kematian' => $tanggal instanceof \DateTimeInterface
                ? Carbon::instance($tanggal)->toDateString()
                : ($tanggal !== null ? (string) $tanggal : null),
            'tempat_kematian' => $tempat ?? '',
        ];
    }

    private function nikBerbeda(string $nik): string
    {
        if ($nik === '') {
            return '9999999999999999';
        }

        $angka = preg_replace('/\D+/', '', $nik) ?? '';
        $angka = str_pad(substr($angka, 0, 16), 16, '0');

        return substr($angka, 0, 15).(((int) $angka[15] + 1) % 10);
    }

    private function tanggalBerbeda(?string $tanggal): ?string
    {
        if ($tanggal === null || $tanggal === '') {
            return null;
        }

        try {
            return Carbon::parse($tanggal)->addDay()->toDateString();
        } catch (\Throwable) {
            return $tanggal;
        }
    }
}
