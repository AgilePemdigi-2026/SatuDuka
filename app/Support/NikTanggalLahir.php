<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Menurunkan tanggal lahir dari NIK tanpa data kependudukan (SIAK).
 *
 * NIK memuat DDMMYY dengan kode jenis kelamin: tanggal di atas 40 berarti
 * perempuan. Tahun empat digit TIDAK PERNAH disimpulkan dari NIK; karena NIK
 * hanya memuat dua digit tahun, abad dipilih dari kandidat 1900-an dan 2000-an
 * yang paling muda namun tetap tidak melewati tanggal acuan (tanggal kematian
 * atau hari ini).
 */
final class NikTanggalLahir
{
    public static function dariNik(string $nik, ?CarbonInterface $acuan = null): ?Carbon
    {
        $nik = preg_replace('/\D+/', '', $nik) ?? '';

        if (strlen($nik) !== 16) {
            return null;
        }

        $tanggalMentah = (int) substr($nik, 6, 2);
        $bulan = (int) substr($nik, 8, 2);
        $tahunDuaDigit = (int) substr($nik, 10, 2);

        if ($tanggalMentah > 40) {
            $tanggalMentah -= 40;
        }

        $batas = $acuan ?? Carbon::today();
        $kandidat = [];

        foreach ([2000, 1900] as $abad) {
            $tahun = $abad + $tahunDuaDigit;

            if (! checkdate($bulan, $tanggalMentah, $tahun)) {
                continue;
            }

            $tanggal = Carbon::create($tahun, $bulan, $tanggalMentah)->startOfDay();

            if ($tanggal->lessThanOrEqualTo($batas)) {
                $kandidat[] = $tanggal;
            }
        }

        if ($kandidat === []) {
            return null;
        }

        usort($kandidat, static fn (Carbon $kiri, Carbon $kanan): int => $kanan->timestamp <=> $kiri->timestamp);

        return $kandidat[0];
    }

    /**
     * Jenis kelamin menurut segmen NIK: 'L' atau 'P', null bila NIK tidak sah.
     */
    public static function jenisKelamin(string $nik): ?string
    {
        $nik = preg_replace('/\D+/', '', $nik) ?? '';

        if (strlen($nik) !== 16) {
            return null;
        }

        return (int) substr($nik, 6, 2) > 40 ? 'P' : 'L';
    }
}
