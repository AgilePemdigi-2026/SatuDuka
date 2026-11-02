<?php

namespace App\Services\Verifikasi;

/**
 * Satu anomali aturan berkas dan batas waktu pengajuan.
 */
final readonly class AnomaliAturanBerkas
{
    public const KEPARAHAN_FATAL = 'fatal';

    public const KEPARAHAN_PERINGATAN = 'peringatan';

    public const KEPARAHAN_INFO = 'info';

    public function __construct(
        public string $kode,
        public string $keparahan,
        public string $pesan,
        public string $rekomendasi,
    ) {}
}
