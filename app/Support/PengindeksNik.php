<?php

namespace App\Support;

use LogicException;

/**
 * Blind index NIK.
 *
 * NIK disimpan terenkripsi (cast `encrypted`), sehingga tidak bisa dicari
 * langsung. Pencarian memakai HMAC-SHA256 atas NIK yang sudah dinormalisasi.
 * Kunci HMAC SELALU terpisah dari `APP_KEY`:
 *
 * - produksi: wajib diisi lewat `SATUDUKA_NIK_HASH_KEY`; bila kosong aplikasi
 *   dan migrasi gagal dengan pesan jelas (bukan diam-diam memakai APP_KEY);
 * - local/testing: boleh diturunkan sebagai sub-kunci dari `APP_KEY` agar
 *   pengembang tidak perlu menyiapkan kunci tambahan.
 *
 * Kunci tidak pernah dicetak ke output perintah, log, atau pesan galat.
 */
final class PengindeksNik
{
    /**
     * Panjang NIK yang sah.
     */
    public const PANJANG = 16;

    /**
     * Lingkungan yang boleh memakai kunci turunan APP_KEY.
     *
     * @var list<string>
     */
    public const LINGKUNGAN_TANPA_KUNCI_EKSPLISIT = ['local', 'testing'];

    /**
     * Konteks HMAC untuk menurunkan sub-kunci dari APP_KEY.
     */
    public const KONTEKS_TURUNAN = 'satuduka-nik-blind-index-v1';

    /**
     * Pesan galat ketika kunci hash belum diisi pada lingkungan produksi.
     */
    public const PESAN_KUNCI_KOSONG = 'SATUDUKA_NIK_HASH_KEY wajib diisi pada lingkungan produksi. '
        .'Isi kunci acak yang panjang (mis. hasil `php artisan key:generate --show`) di berkas .env '
        .'sebelum menjalankan aplikasi atau migrasi.';

    /**
     * Ambil kunci HMAC yang berlaku.
     *
     * @throws LogicException bila kosong di luar local/testing
     */
    public static function kunci(): string
    {
        $eksplisit = trim((string) config('satuduka.nik_hash_key', ''));

        if ($eksplisit !== '') {
            return $eksplisit;
        }

        $lingkungan = (string) app()->environment();

        if (! in_array($lingkungan, self::LINGKUNGAN_TANPA_KUNCI_EKSPLISIT, true)) {
            throw new LogicException(self::PESAN_KUNCI_KOSONG);
        }

        $appKey = (string) config('app.key', '');

        if ($appKey === '') {
            throw new LogicException(
                'APP_KEY belum diisi sehingga kunci blind index NIK tidak dapat diturunkan.'
            );
        }

        return hash_hmac('sha256', self::KONTEKS_TURUNAN, $appKey);
    }

    /**
     * Ambil hanya digit dari sebuah NIK (spasi, tanda hubung, dan titik dibuang).
     *
     * Pemilihan aturan: pencarian dan login MENORMALKAN pemisah, sedangkan
     * penyimpanan tetap mensyaratkan 16 digit angka lewat validasi formulir.
     */
    public static function angka(?string $nik): string
    {
        if ($nik === null) {
            return '';
        }

        return preg_replace('/\D+/', '', $nik) ?? '';
    }

    /**
     * Apakah nilai ini berupa NIK 16 digit (setelah dinormalisasi).
     */
    public static function sah(?string $nik): bool
    {
        return strlen(self::angka($nik)) === self::PANJANG;
    }

    /**
     * Hash blind index sebuah NIK; null bila NIK kosong atau tidak 16 digit.
     */
    public static function hash(?string $nik): ?string
    {
        $angka = self::angka($nik);

        if (strlen($angka) !== self::PANJANG) {
            return null;
        }

        return hash_hmac('sha256', $angka, self::kunci());
    }

    /**
     * Apakah dua NIK menunjuk orang yang sama (dibandingkan lewat hash).
     */
    public static function sama(?string $kiri, ?string $kanan): bool
    {
        $hashKiri = self::hash($kiri);
        $hashKanan = self::hash($kanan);

        if ($hashKiri === null || $hashKanan === null) {
            return false;
        }

        return hash_equals($hashKiri, $hashKanan);
    }
}
