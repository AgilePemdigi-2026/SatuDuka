<?php

namespace App\Services\Verifikasi;

/**
 * Satu pelanggaran aturan NIK.
 *
 * Mesin aturan hanya mendeteksi dan merekomendasikan; keparahan dipakai
 * untuk menentukan tindakan verifikasi berikutnya.
 */
final readonly class PelanggaranNik
{
    public const KEPARAHAN_FATAL = 'fatal';

    public const KEPARAHAN_PERINGATAN = 'peringatan';

    public const KEPARAHAN_INFO = 'info';

    public function __construct(
        public string $kode,
        public string $keparahan,
        public string $pesan,
    ) {}
}
