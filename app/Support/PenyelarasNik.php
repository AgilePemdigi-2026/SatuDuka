<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Penyelaras data NIK lama: mengisi blind index dan mengenkripsi nilai mentah.
 *
 * Dipakai oleh migrasi backfill dan perintah `nik:enkripsi-ulang`. Setiap
 * potongan (chunk) berjalan dalam satu transaksi, sehingga kegagalan di tengah
 * tidak meninggalkan campuran baris yang tidak konsisten: potongan yang gagal
 * dibatalkan seluruhnya dan dapat dijalankan ulang. Proses ini idempoten.
 *
 * Kelas ini TIDAK pernah mencetak nilai NIK maupun kunci ke output.
 */
final class PenyelarasNik
{
    /**
     * Jumlah baris per transaksi.
     */
    public const UKURAN_CUKUP = 500;

    /**
     * Tabel dan kolom NIK yang diselaraskan.
     *
     * @var array<string, array{kunci: string, kolom: string}>
     */
    public const TABEL = [
        'pengguna' => ['kunci' => 'id_pengguna', 'kolom' => 'nik'],
        'laporan_kematian' => ['kunci' => 'id_laporan', 'kolom' => 'nik_almarhum'],
    ];

    /**
     * Jalankan penyelarasan.
     *
     * @param  bool  $hanyaHash  hanya hitung ulang hash (untuk rotasi kunci hash)
     * @param  bool  $enkripsiUlang  paksa enkripsi ulang nilai yang sudah terenkripsi
     * @param  callable(string, int|string): void|null  $sebelumBaris  kait uji kegagalan
     * @return array<string, int> jumlah baris yang diubah per tabel
     */
    public static function jalankan(
        bool $hanyaHash = false,
        bool $enkripsiUlang = false,
        ?callable $sebelumBaris = null,
    ): array {
        // Gagal lebih awal dengan pesan jelas bila kunci wajib belum diisi.
        PengindeksNik::kunci();

        $ringkasan = [];

        foreach (self::TABEL as $tabel => $info) {
            $ringkasan[$tabel] = self::selaraskanTabel($tabel, $info, $hanyaHash, $enkripsiUlang, $sebelumBaris);
        }

        $ringkasan['analisis_ai'] = self::selaraskanAnalisis($sebelumBaris);

        return $ringkasan;
    }

    /**
     * Kembalikan NIK ke bentuk plaintext (dipakai `down()` migrasi).
     *
     * Nilai pada `analisis_ai.hasil` TIDAK dapat dipulihkan: backfill hanya
     * menyimpan versi termasker, sehingga NIK utuh memang tidak pernah
     * disimpan lagi setelah migrasi.
     *
     * @return array<string, int>
     */
    public static function kembalikan(): array
    {
        $ringkasan = [];

        foreach (self::TABEL as $tabel => $info) {
            $ringkasan[$tabel] = 0;

            DB::table($tabel)
                ->select($info['kunci'], $info['kolom'])
                ->orderBy($info['kunci'])
                ->chunkById(self::UKURAN_CUKUP, function ($baris) use ($tabel, $info, &$ringkasan): void {
                    DB::transaction(function () use ($baris, $tabel, $info, &$ringkasan): void {
                        foreach ($baris as $r) {
                            $mentah = $r->{$info['kolom']};
                            [$plain, $terenkripsi] = self::uraikan($mentah);

                            if (! $terenkripsi) {
                                continue;
                            }

                            DB::table($tabel)
                                ->where($info['kunci'], $r->{$info['kunci']})
                                ->update([
                                    $info['kolom'] => $plain,
                                    $info['kolom'].'_hash' => null,
                                ]);

                            $ringkasan[$tabel]++;
                        }
                    });
                }, $info['kunci']);
        }

        $ringkasan['analisis_ai'] = 0;

        return $ringkasan;
    }

    /**
     * Laporan jumlah baris untuk `--dry-run`. Aman dijalankan sebelum migrasi
     * karena kolom hash diperiksa keberadaannya lebih dulu.
     *
     * @return array<string, array<string, int>>
     */
    public static function dryRun(): array
    {
        PengindeksNik::kunci();

        $ringkasan = [];

        foreach (self::TABEL as $tabel => $info) {
            $kolomHash = $info['kolom'].'_hash';
            $punyaHash = Schema::hasColumn($tabel, $kolomHash);

            $ringkasan[$tabel] = [
                'terisi' => 0,
                'belum_terenkripsi' => 0,
                'hash_perlu_diperbarui' => 0,
            ];

            DB::table($tabel)
                ->select(array_filter([$info['kunci'], $info['kolom'], $punyaHash ? $kolomHash : null]))
                ->orderBy($info['kunci'])
                ->chunkById(self::UKURAN_CUKUP, function ($baris) use ($info, $kolomHash, $punyaHash, $tabel, &$ringkasan): void {
                    foreach ($baris as $r) {
                        $mentah = $r->{$info['kolom']};

                        if ($mentah === null || $mentah === '') {
                            continue;
                        }

                        $ringkasan[$tabel]['terisi']++;

                        [$plain, $terenkripsi] = self::uraikan($mentah);

                        if (PengindeksNik::sah($plain) && ! $terenkripsi) {
                            $ringkasan[$tabel]['belum_terenkripsi']++;
                        }

                        if ($punyaHash && PengindeksNik::hash($plain) !== ($r->{$kolomHash} ?? null)) {
                            $ringkasan[$tabel]['hash_perlu_diperbarui']++;
                        }
                    }
                }, $info['kunci']);
        }

        $ringkasan['analisis_ai'] = [
            'terisi' => 0,
            'belum_terenkripsi' => 0,
            'hash_perlu_diperbarui' => 0,
        ];

        if (Schema::hasTable('analisis_ai')) {
            DB::table('analisis_ai')
                ->select('id_analisis', 'jenis', 'hasil')
                ->orderBy('id_analisis')
                ->chunkById(self::UKURAN_CUKUP, function ($baris) use (&$ringkasan): void {
                    foreach ($baris as $r) {
                        $hasil = json_decode((string) $r->hasil, true);

                        if (! is_array($hasil)) {
                            continue;
                        }

                        $ringkasan['analisis_ai']['terisi']++;

                        if (NikAnalisis::samarkan((string) $r->jenis, $hasil) !== $hasil) {
                            $ringkasan['analisis_ai']['belum_terenkripsi']++;
                        }
                    }
                }, 'id_analisis');
        }

        return $ringkasan;
    }

    /**
     * @param  array{kunci: string, kolom: string}  $info
     */
    private static function selaraskanTabel(
        string $tabel,
        array $info,
        bool $hanyaHash,
        bool $enkripsiUlang,
        ?callable $sebelumBaris,
    ): int {
        $jumlah = 0;
        $kolomHash = $info['kolom'].'_hash';

        DB::table($tabel)
            ->select($info['kunci'], $info['kolom'], $kolomHash)
            ->orderBy($info['kunci'])
            ->chunkById(self::UKURAN_CUKUP, function ($baris) use ($tabel, $info, $kolomHash, $hanyaHash, $enkripsiUlang, $sebelumBaris, &$jumlah): void {
                DB::transaction(function () use ($baris, $tabel, $info, $kolomHash, $hanyaHash, $enkripsiUlang, $sebelumBaris, &$jumlah): void {
                    foreach ($baris as $r) {
                        if ($sebelumBaris !== null) {
                            $sebelumBaris($tabel, $r->{$info['kunci']});
                        }

                        $mentah = $r->{$info['kolom']};
                        [$plain, $terenkripsi] = self::uraikan($mentah);

                        $hash = PengindeksNik::hash($plain);
                        $nilaiBaru = $mentah;

                        if (! $hanyaHash && PengindeksNik::sah($plain) && (! $terenkripsi || $enkripsiUlang)) {
                            $nilaiBaru = Crypt::encryptString($plain);
                        }

                        if ($nilaiBaru === $mentah && $hash === ($r->{$kolomHash} ?? null)) {
                            continue;
                        }

                        DB::table($tabel)
                            ->where($info['kunci'], $r->{$info['kunci']})
                            ->update([
                                $info['kolom'] => $nilaiBaru,
                                $kolomHash => $hash,
                            ]);

                        $jumlah++;
                    }
                });
            }, $info['kunci']);

        return $jumlah;
    }

    /**
     * Samarkan NIK pada hasil analisis AI lama.
     */
    private static function selaraskanAnalisis(?callable $sebelumBaris): int
    {
        if (! Schema::hasTable('analisis_ai')) {
            return 0;
        }

        $jumlah = 0;

        DB::table('analisis_ai')
            ->select('id_analisis', 'jenis', 'hasil')
            ->orderBy('id_analisis')
            ->chunkById(self::UKURAN_CUKUP, function ($baris) use ($sebelumBaris, &$jumlah): void {
                DB::transaction(function () use ($baris, $sebelumBaris, &$jumlah): void {
                    foreach ($baris as $r) {
                        if ($sebelumBaris !== null) {
                            $sebelumBaris('analisis_ai', $r->id_analisis);
                        }

                        $hasil = json_decode((string) $r->hasil, true);

                        if (! is_array($hasil)) {
                            continue;
                        }

                        $baru = NikAnalisis::samarkan((string) $r->jenis, $hasil);

                        if ($baru === $hasil) {
                            continue;
                        }

                        DB::table('analisis_ai')
                            ->where('id_analisis', $r->id_analisis)
                            ->update([
                                'hasil' => json_encode($baru, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            ]);

                        $jumlah++;
                    }
                });
            }, 'id_analisis');

        return $jumlah;
    }

    /**
     * Pisahkan ciphertext dan plaintext tanpa membocorkan nilai.
     *
     * @return array{0: string, 1: bool} [plaintext, sudah terenkripsi]
     */
    private static function uraikan(mixed $mentah): array
    {
        if (! is_string($mentah) || $mentah === '') {
            return [(string) ($mentah ?? ''), false];
        }

        try {
            return [Crypt::decryptString($mentah), true];
        } catch (DecryptException|Throwable) {
            return [$mentah, false];
        }
    }
}
