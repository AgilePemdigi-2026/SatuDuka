<?php

namespace App\Support;

/**
 * Penyamaran NIK untuk teks yang dikirim ke layanan eksternal.
 *
 * Empat digit terakhir NIK diganti tanda bintang. Dokumen sumber tetap dikirim
 * apa adanya karena NIK di dalam dokumen merupakan data yang akan dibaca.
 */
final class MaskerNik
{
    /**
     * Masker satu NIK 16 digit.
     */
    public static function masker(?string $nik): ?string
    {
        if ($nik === null) {
            return null;
        }

        $angka = preg_replace('/\D+/', '', $nik) ?? '';

        if (strlen($angka) !== 16) {
            return $nik;
        }

        return substr($angka, 0, 12).'****';
    }

    /**
     * Masker setiap NIK 16 digit yang muncul pada teks bebas.
     */
    public static function terapkan(string $teks): string
    {
        return preg_replace('/\b(\d{12})(\d{4})\b/', '$1****', $teks) ?? $teks;
    }
}
