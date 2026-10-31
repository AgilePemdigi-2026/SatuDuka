<?php

namespace App\Services\Dokumen;

use App\Models\DokumenResmi;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pembuat nomor dokumen resmi.
 *
 * Format: `SKD-YYYY-NNNNNN` mengikuti contoh SRS `SKD-2026-000124`.
 *
 * Aman terhadap balapan: pada PostgreSQL dipakai `pg_advisory_xact_lock` per
 * tahun, sedangkan pada SQLite transaksi dan indeks unik `no_dokumen` menjadi
 * penjamin; bila penulis lain lebih dulu memakai nomor yang sama, transaksi
 * diulang dengan nomor terbaru.
 */
final class LayananNomorDokumen
{
    /**
     * Jumlah digit nomor urut.
     */
    public const PANJANG_URUTAN = 6;

    /**
     * Kunci advisory lock dokumen (berbeda dari nomor pengajuan).
     */
    private const KUNCI_ADVISORY = 91273646;

    /**
     * Jumlah maksimal percobaan saat nomor bentrok pada SQLite.
     */
    private const MAKS_PERCOBAAN = 3;

    /**
     * Nomor kandidat berikutnya untuk tahun tertentu.
     */
    public static function berikutnya(?Carbon $tanggal = null): string
    {
        $tanggal ??= now();

        $prefix = 'SKD-'.$tanggal->format('Y').'-';

        $terakhir = DokumenResmi::query()
            ->where('no_dokumen', 'like', $prefix.'%')
            ->orderByDesc('no_dokumen')
            ->value('no_dokumen');

        $urutan = $terakhir !== null
            ? ((int) substr($terakhir, -self::PANJANG_URUTAN)) + 1
            : 1;

        return $prefix.str_pad((string) $urutan, self::PANJANG_URUTAN, '0', STR_PAD_LEFT);
    }

    /**
     * Jalankan callback dengan nomor dokumen yang dijamin unik.
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
                    $prefix = 'SKD-'.$tanggal->format('Y').'-';

                    if (DB::connection()->getDriverName() === 'pgsql') {
                        DB::select('select pg_advisory_xact_lock(?, ?)', [
                            self::KUNCI_ADVISORY,
                            (int) $tanggal->format('Y'),
                        ]);
                    }

                    $terakhir = DokumenResmi::query()
                        ->where('no_dokumen', 'like', $prefix.'%')
                        ->orderByDesc('no_dokumen')
                        ->value('no_dokumen');

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
     * Apakah pelanggaran unik berasal dari kolom nomor dokumen.
     */
    private static function bentrokNomor(UniqueConstraintViolationException $exception): bool
    {
        return in_array('no_dokumen', $exception->columns, true)
            || str_contains($exception->getMessage(), 'no_dokumen');
    }
}
