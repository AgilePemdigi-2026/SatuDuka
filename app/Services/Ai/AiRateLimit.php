<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Response;

/**
 * Penyedia AI membalas batas laju (429) atau sedang sibuk (503).
 *
 * Kondisi ini BUKAN kegagalan analisis: berkas tidak boleh ditandai gagal
 * permanen. Pemanggil (LayananAnalisisAi) boleh mencoba ulang dalam jumlah
 * terbatas, menghormati header `Retry-After` bila ada, lalu menandai hasil
 * sebagai tertunda agar dapat dicoba lagi nanti.
 *
 * Warisan `AiTidakTersedia` dipertahankan agar seluruh penanganan lama tetap
 * berlaku (tidak menolak pengajuan, lanjut manual), sedangkan `penyebab`
 * tetap `http` supaya kontrak pencatatan log tidak berubah. Nilai
 * `retryAfter` hanya berisi detik tunggu, bukan isi respons.
 */
class AiRateLimit extends AiTidakTersedia
{
    /**
     * Status HTTP yang dianggap "sibuk", bukan gagal.
     *
     * @var list<int>
     */
    public const STATUS_SIBUK = [429, 503];

    /**
     * Detik tunggu yang diminta penyedia, bila ada.
     */
    public readonly ?int $retryAfter;

    public function __construct(
        string $message = 'Layanan AI sedang sibuk.',
        ?int $statusHttp = null,
        ?int $retryAfter = null,
    ) {
        parent::__construct($message, $statusHttp, 'http');

        $this->retryAfter = $retryAfter;
    }

    /**
     * Buat pengecualian bila respons menandakan layanan sibuk.
     *
     * Mengembalikan null untuk status lain agar pemanggil dapat melanjutkan
     * penanganan kegagalan biasa.
     */
    public static function dari(Response $respon): ?self
    {
        $status = $respon->status();

        if (! in_array($status, self::STATUS_SIBUK, true)) {
            return null;
        }

        return new self(
            message: 'Layanan AI sedang sibuk.',
            statusHttp: $status,
            retryAfter: self::detikTunggu($respon),
        );
    }

    /**
     * Baca `Retry-After` (detik atau tanggal HTTP) tanpa menyentuh isi respons.
     */
    private static function detikTunggu(Response $respon): ?int
    {
        $nilai = trim((string) $respon->header('Retry-After'));

        if ($nilai === '') {
            return null;
        }

        if (ctype_digit($nilai)) {
            return (int) $nilai;
        }

        $waktu = strtotime($nilai);

        if ($waktu === false) {
            return null;
        }

        return max(0, $waktu - time());
    }
}
