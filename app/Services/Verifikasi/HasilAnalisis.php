<?php

namespace App\Services\Verifikasi;

/**
 * Hasil lengkap analisis mesin rekonsiliasi untuk satu laporan.
 *
 * Daftar anomali selalu diurutkan dari keparahan tertinggi. Hasil ini dapat
 * disimpan sebagai snapshot pada `hasil_verifikasi.deteksi_otomatis`.
 */
final readonly class HasilAnalisis
{
    /**
     * @param  list<AnomaliVerifikasi>  $anomali
     */
    public function __construct(
        public array $anomali,
        public string $rekomendasiUtama,
        public bool $layananTidakTersedia = false,
    ) {}

    /**
     * @return list<string>
     */
    public function kodeAnomali(): array
    {
        return array_map(
            static fn (AnomaliVerifikasi $anomali): string => $anomali->kode,
            $this->anomali,
        );
    }

    public function punyaKode(string $kode): bool
    {
        return in_array($kode, $this->kodeAnomali(), true);
    }

    public function bersih(): bool
    {
        return $this->anomali === [];
    }

    /**
     * Snapshot siap simpan untuk pelabelan data pelatihan model.
     *
     * @return array{
     *     versi: int,
     *     dibuat_pada: string,
     *     rekomendasi_utama: string,
     *     layanan_tidak_tersedia: bool,
     *     anomali: list<array{kode: string, keparahan: string, pesan: string, rekomendasi: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'versi' => 1,
            'dibuat_pada' => now()->toIso8601String(),
            'rekomendasi_utama' => $this->rekomendasiUtama,
            'layanan_tidak_tersedia' => $this->layananTidakTersedia,
            'anomali' => array_map(
                static fn (AnomaliVerifikasi $anomali): array => $anomali->toArray(),
                $this->anomali,
            ),
        ];
    }
}
