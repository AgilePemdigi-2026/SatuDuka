<?php

namespace App\Support;

/**
 * Pembuat NIK dummy untuk data contoh.
 *
 * Seluruh data SATUDUKA adalah data contoh. Agar tidak mungkin
 * bentrok dengan NIK warga asli, NIK dummy memakai kode wilayah fiktif `999999`
 * (kode provinsi/kabupaten/kecamatan yang tidak pernah dipakai). Dengan susunan
 * NIK 16 digit: 6 digit wilayah + 2 digit tanggal + 2 digit bulan + 2 digit tahun
 * + 4 digit urutan.
 */
final class NikDummy
{
    /**
     * Kode wilayah fiktif (tidak pernah dipakai di Indonesia).
     */
    public const WILAYAH_FIKTIF = '999999';

    /**
     * Penghitung nomor urut agar NIK dummy selalu unik.
     */
    private static int $urutan = 0;

    /**
     * Buat NIK dummy berikutnya.
     *
     * @param  int  $tanggal  Tanggal lahir (01-31).
     * @param  int  $bulan  Bulan lahir (01-12).
     * @param  int  $tahun  Tahun lahir, ditulis dua digit terakhir.
     */
    public static function berikutnya(int $tanggal = 1, int $bulan = 1, int $tahun = 1990): string
    {
        self::$urutan++;

        return self::denganUrutan(self::$urutan, $tanggal, $bulan, $tahun);
    }

    /**
     * Buat NIK dummy dengan nomor urut tertentu.
     */
    public static function denganUrutan(int $urutan, int $tanggal = 1, int $bulan = 1, int $tahun = 1990): string
    {
        return sprintf(
            '%s%02d%02d%02d%04d',
            self::WILAYAH_FIKTIF,
            $tanggal,
            $bulan,
            $tahun % 100,
            $urutan % 10000,
        );
    }

    /**
     * Reset penghitung nomor urut (dipakai pengujian otomatis).
     */
    public static function reset(): void
    {
        self::$urutan = 0;
    }
}
