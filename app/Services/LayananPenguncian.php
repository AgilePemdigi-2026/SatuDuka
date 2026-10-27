<?php

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Cache;

/**
 * Penghitung dan kunci akses sementara (15 menit).
 *
 * Penghitung kegagalan login dan kredensial TTE **tidak** disimpan pada tabel,
 * melainkan pada cache dengan masa berlaku 15 menit (sesuai NFR-02 dan NFR-03).
 * Setiap penguncian tetap dicatat pada `log_audit`
 * melalui {@see LayananAudit} sehingga kejadiannya dapat ditelusuri.
 *
 * Kredensial TTE (PIN/OTP) sendiri tidak pernah disimpan di mana pun.
 */
final class LayananPenguncian
{
    /**
     * Masa berlaku kunci dalam menit (NFR-02 dan NFR-03).
     */
    public const MENIT_KUNCI = 15;

    /**
     * Jumlah kegagalan yang memicu kunci untuk kredensial TTE (NFR-03).
     */
    public const MAKS_GAGAL_TTE = 3;

    /**
     * Jumlah kegagalan yang memicu kunci untuk login (NFR-02).
     */
    public const MAKS_GAGAL_LOGIN = 5;

    /**
     * Prefiks kunci cache.
     */
    private const AWALAN_CACHE = 'satuduka:penguncian:';

    /**
     * Kunci cache penghitung kegagalan.
     */
    private static function kunciPenghitung(string $kunci): string
    {
        return self::AWALAN_CACHE.'gagal:'.$kunci;
    }

    /**
     * Kunci cache penanda penguncian.
     */
    private static function kunciKunci(string $kunci): string
    {
        return self::AWALAN_CACHE.'kunci:'.$kunci;
    }

    /**
     * Jumlah kegagalan yang tercatat untuk sebuah kunci.
     */
    public static function jumlahGagal(string $kunci): int
    {
        return (int) Cache::get(self::kunciPenghitung($kunci), 0);
    }

    /**
     * Catat satu kegagalan dan kembalikan jumlah kegagalan terkumpul.
     */
    public static function catatGagal(string $kunci): int
    {
        $jumlah = self::jumlahGagal($kunci) + 1;

        Cache::put(self::kunciPenghitung($kunci), $jumlah, now()->addMinutes(self::MENIT_KUNCI));

        return $jumlah;
    }

    /**
     * Apakah sebuah kunci sedang terkunci.
     */
    public static function terkunci(string $kunci): bool
    {
        return Cache::has(self::kunciKunci($kunci));
    }

    /**
     * Sisa waktu kunci dalam detik (0 bila tidak terkunci).
     */
    public static function sisaKunci(string $kunci): int
    {
        $kedaluwarsa = Cache::get(self::kunciKunci($kunci));

        if ($kedaluwarsa === null) {
            return 0;
        }

        return max(0, now()->diffInSeconds($kedaluwarsa, false));
    }

    /**
     * Kunci sebuah kunci akses selama 15 menit dan catat kejadiannya ke log audit.
     */
    public static function kunci(string $kunci, ?Pengguna $pengguna = null, int $menit = self::MENIT_KUNCI): void
    {
        Cache::put(
            self::kunciKunci($kunci),
            now()->addMinutes($menit),
            now()->addMinutes($menit),
        );

        LayananAudit::catat(
            aksi: 'mengunci_akses',
            objek: 'penguncian',
            detail: [
                'kunci' => $kunci,
                'menit' => $menit,
                'jumlah_gagal' => self::jumlahGagal($kunci),
            ],
            pengguna: $pengguna,
        );
    }

    /**
     * Buka kunci dan reset penghitung kegagalan (berhasil login atau TTE).
     */
    public static function buka(string $kunci): void
    {
        Cache::forget(self::kunciPenghitung($kunci));
        Cache::forget(self::kunciKunci($kunci));
    }

    /**
     * Catat kegagalan dan kunci otomatis bila jumlah kegagalan mencapai batas.
     *
     * @return int Jumlah kegagalan terkumpul.
     */
    public static function catatGagalDanKunciBilaPerlu(
        string $kunci,
        int $maksGagal,
        ?Pengguna $pengguna = null,
    ): int {
        $jumlah = self::catatGagal($kunci);

        if ($jumlah >= $maksGagal && ! self::terkunci($kunci)) {
            self::kunci($kunci, $pengguna);
        }

        return $jumlah;
    }
}
