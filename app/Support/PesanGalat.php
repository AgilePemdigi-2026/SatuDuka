<?php

namespace App\Support;

use Illuminate\Support\Str;
use Throwable;

/**
 * Ringkasan pesan galat yang aman dicatat ke audit atau log.
 *
 * Nama kelas pengecualian dipertahankan untuk penelusuran, sedangkan pesan
 * dipotong panjangnya dan dimasker agar NIK 16 digit tidak pernah tersimpan
 * utuh.
 */
final class PesanGalat
{
    /**
     * Batas panjang pesan yang disimpan.
     */
    public const BATAS_PANJANG = 500;

    /**
     * @return array{kelas: string, pesan: string}
     */
    public static function ringkas(Throwable $galat): array
    {
        $pesan = Str::limit(trim($galat->getMessage()), self::BATAS_PANJANG, '…');

        return [
            'kelas' => class_basename($galat),
            'pesan' => MaskerNik::terapkan($pesan),
        ];
    }
}
