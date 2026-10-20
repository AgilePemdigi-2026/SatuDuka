<?php

namespace App\Support;

/**
 * Pembaca data dummy SIAK (sumber data master kependudukan versi mock).
 *
 * Data SIAK TIDAK disimpan pada tabel aplikasi, melainkan pada berkas
 * `database/data/siak_dummy.json` sesuai docs/DATABASE.md. Service mock SIAK
 * pada fase berikutnya akan memakai kelas ini.
 */
final class DataSIAK
{
    /**
     * Lokasi berkas data dummy SIAK relatif terhadap akar proyek.
     */
    public const LOKASI = 'database/data/siak_dummy.json';

    /**
     * Memuat seluruh data dummy SIAK.
     *
     * @return list<array<string, string>>
     */
    public static function semua(): array
    {
        $jalur = base_path('database/data/siak_dummy.json');

        if (! is_file($jalur)) {
            return [];
        }

        $isi = file_get_contents($jalur);

        if ($isi === false) {
            return [];
        }

        $data = json_decode($isi, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Mencari satu catatan SIAK berdasarkan NIK.
     *
     * @return array<string, string>|null
     */
    public static function cariNik(string $nik): ?array
    {
        foreach (self::semua() as $data) {
            if (($data['nik'] ?? null) === $nik) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Mengambil satu catatan SIAK secara acak.
     *
     * @return array<string, string>
     */
    public static function acak(): array
    {
        $data = self::semua();

        return $data === [] ? [] : $data[array_rand($data)];
    }
}
