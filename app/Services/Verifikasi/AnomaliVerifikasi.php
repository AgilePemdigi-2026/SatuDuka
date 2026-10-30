<?php

namespace App\Services\Verifikasi;

/**
 * Satu anomali yang ditemukan mesin rekonsiliasi.
 *
 * Mesin hanya mendeteksi dan merekomendasikan tindakan. Anomali tidak pernah
 * mengubah status pengajuan secara otomatis.
 */
final readonly class AnomaliVerifikasi
{
    public const KEPARAHAN_FATAL = 'fatal';

    public const KEPARAHAN_TINGGI = 'tinggi';

    public const KEPARAHAN_SEDANG = 'sedang';

    public const KEPARAHAN_RENDAH = 'rendah';

    public const KEPARAHAN_INFO = 'info';

    public function __construct(
        public string $kode,
        public string $keparahan,
        public string $pesan,
        public string $rekomendasi,
    ) {}

    /**
     * @return array{kode: string, keparahan: string, pesan: string, rekomendasi: string}
     */
    public function toArray(): array
    {
        return [
            'kode' => $this->kode,
            'keparahan' => $this->keparahan,
            'pesan' => $this->pesan,
            'rekomendasi' => $this->rekomendasi,
        ];
    }
}
