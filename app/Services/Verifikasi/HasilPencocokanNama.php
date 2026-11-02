<?php

namespace App\Services\Verifikasi;

/**
 * Hasil pencocokan dua nama.
 */
final readonly class HasilPencocokanNama
{
    public const KATEGORI_PERSIS = 'PERSIS';

    public const KATEGORI_BEDA_TIPIS = 'BEDA_TIPIS';

    public const KATEGORI_TIDAK_COCOK = 'TIDAK_COCOK';

    public function __construct(
        public string $kategori,
        public float $skor,
        public string $namaPertamaTernormalisasi,
        public string $namaKeduaTernormalisasi,
    ) {}
}
