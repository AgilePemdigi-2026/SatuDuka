<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Layanan AI tidak dapat dihubungi atau tidak merespons tepat waktu.
 *
 * Kondisi ini tidak boleh menolak pengajuan secara otomatis. Status HTTP dan
 * penyebab ringkas disimpan terpisah dari pesan agar dapat dicatat pada log
 * tanpa membocorkan isi dokumen, NIK, atau kunci API.
 */
class AiTidakTersedia extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ?int $statusHttp = null,
        public readonly ?string $penyebab = null,
    ) {
        parent::__construct($message);
    }
}
