<?php

namespace App\Services;

use App\Models\LaporanKematian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pembuat nomor pengajuan laporan kematian.
 *
 * Format: `SD-YYYYMMDD-NNNN` (keputusan pemilik proyek, tanpa kode wilayah).
 *
 * Nomor dibuat aman terhadap race condition: pada PostgreSQL dipakai
 * `pg_advisory_xact_lock` sehingga hanya satu transaksi yang menghitung nomor
 * untuk hari yang sama, dan indeks unik `no_pengajuan` menjadi penjamin
 * terakhir. Pada SQLite (pengujian) penomoran memakai nilai terbesar yang ada
 * karena SQLite hanya mengizinkan satu penulis pada satu waktu.
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
    }
}
