<?php

namespace App\Support;

/**
 * Penjaga pengaktifan verifikasi NIK demo (awalan 99).
 *
 * NIK demo hanya untuk demonstrasi lokal. Pada `APP_ENV=production` fitur ini
 * TIDAK boleh aktif secara otomatis; hanya boleh dinyalakan bila operator
 * menyalakan flag eksplisit `SATUDUKA_IZINKAN_MOCK_DEMO=true` (mis. demo
 * terkontrol pada Fase 10). Bila diminta aktif di produksi tanpa flag itu,
 * keputusannya diturunkan menjadi nonaktif dan penanda `diabaikan` dipakai
 * untuk mencatat satu peringatan.
 */
final class PenjagaNikDemo
{
    /**
     * Tentukan apakah NIK demo boleh aktif.
     *
     * @return array{aktif: bool, diabaikan: bool}
     *                                             `aktif` = nilai akhir yang boleh dipakai; `diabaikan` = true bila
     *                                             permintaan aktif di produksi ditolak karena flag demo tidak ada.
     */
    public static function putuskan(string $lingkungan, bool $diminta, bool $izinkanMock): array
    {
        if ($lingkungan === 'production' && $diminta && ! $izinkanMock) {
            return ['aktif' => false, 'diabaikan' => true];
        }

        return ['aktif' => $diminta, 'diabaikan' => false];
    }
}
