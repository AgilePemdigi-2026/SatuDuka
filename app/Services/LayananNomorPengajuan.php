<?php

namespace App\Services;

use App\Models\LaporanKematian;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pembuat nomor pengajuan laporan kematian.
 *
 * Format: `SD-YYYYMMDD-NNNN`, tanpa kode wilayah.
 *
 * Nomor dibuat aman terhadap race condition: pada PostgreSQL dipakai
 * `pg_advisory_xact_lock` sehingga hanya satu transaksi yang menghitung nomor
 * untuk hari yang sama. Pada SQLite, transaksi dan indeks unik `no_pengajuan`
 * menjadi penjamin; bila penulis lain lebih dulu memakai nomor yang sama,
 * transaksi diulang dengan nomor terbaru.
 */
final class LayananNomorPengajuan
{
    /**
     * Pola nomor pengajuan.
     */
    public const POLA = 'SD-ymd-NNNN';

    /**
     * Jumlah digit nomor urut.
     */
    public const PANJANG_URUTAN = 4;

    /**
     * Kunci advisory lock per hari (berbeda per proyek agar tidak bentrok).
     */
    private const KUNCI_ADVISORY = 91273645;

    /**
     * Jumlah maksimal percobaan saat nomor bentrok pada SQLite.
     */
    private const MAKS_PERCOBAAN = 3;

    /**
     * Nomor berikutnya (kandidat) untuk tanggal tertentu.
     */
    public static function berikutnya(?Carbon $tanggal = null): string
    {
        $tanggal ??= now();

        $prefix = 'SD-'.$tanggal->format('Ymd').'-';

        $terakhir = LaporanKematian::query()
            ->where('no_pengajuan', 'like', $prefix.'%')
            ->orderByDesc('no_pengajuan')
            ->value('no_pengajuan');

        $urutan = $terakhir !== null
            ? ((int) substr($terakhir, -self::PANJANG_URUTAN)) + 1
            : 1;

        return $prefix.str_pad((string) $urutan, self::PANJANG_URUTAN, '0', STR_PAD_LEFT);
    }

    /**
     * Buat data pengajuan dengan nomor pengajuan yang dijamin unik.
     *
     * Callback menerima nomor pengajuan yang telah disiapkan dan harus
     * mengembalikan model yang disimpan.
     *
     * @template T
     *
     * @param  callable(string): T  $pembuat
     * @return T
     */
    public static function buat(callable $pembuat): mixed
    {
        $percobaan = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($pembuat) {
                    $tanggal = now();
                    $prefix = 'SD-'.$tanggal->format('Ymd').'-';

                    if (DB::connection()->getDriverName() === 'pgsql') {
                        // Mengunci transaksi lain yang menghitung nomor untuk hari yang sama.
                        DB::select('select pg_advisory_xact_lock(?, ?)', [self::KUNCI_ADVISORY, (int) $tanggal->format('Ymd')]);
                    }

                    $terakhir = LaporanKematian::query()
                        ->where('no_pengajuan', 'like', $prefix.'%')
                        ->orderByDesc('no_pengajuan')
                        ->value('no_pengajuan');

                    $urutan = $terakhir !== null
                        ? ((int) substr($terakhir, -self::PANJANG_URUTAN)) + 1
                        : 1;

                    $nomor = $prefix.str_pad((string) $urutan, self::PANJANG_URUTAN, '0', STR_PAD_LEFT);

                    return $pembuat($nomor);
                });
            } catch (UniqueConstraintViolationException $exception) {
                if (DB::connection()->getDriverName() === 'pgsql'
                    || ! self::bentrokNomor($exception)
                    || ++$percobaan >= self::MAKS_PERCOBAAN) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Apakah pelanggaran unik berasal dari kolom nomor pengajuan.
     */
    private static function bentrokNomor(UniqueConstraintViolationException $exception): bool
    {
        return in_array('no_pengajuan', $exception->columns, true)
            || str_contains($exception->getMessage(), 'no_pengajuan');
    }
}
