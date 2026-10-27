<?php

namespace Database\Seeders;

use App\Models\ReferensiWilayah;
use Illuminate\Database\Seeder;

/**
 * Mengisi referensi kecamatan Kota Yogyakarta dari data Open Data Kota
 * Yogyakarta. Berkas CSV dibaca dari docs, dengan data cadangan agar seeder
 * tetap dapat dijalankan ketika dokumentasi tidak disertakan saat deploy.
 */
class ReferensiWilayahSeeder extends Seeder
{
    /**
     * Data cadangan yang sama dengan docs/data_kecamatan_2020.csv.
     *
     * @var list<array{0: string, 1: string, 2: int}>
     */
    private const CADANGAN = [
        ['347101', 'Tegalrejo', 129],
        ['347102', 'Jetis', 130],
        ['347103', 'Gondokusuman', 174],
        ['347104', 'Danurejan', 110],
        ['347105', 'Gedongtengen', 91],
        ['347106', 'Ngampilan', 88],
        ['347107', 'Wirobrajan', 103],
        ['347108', 'Mantrijeron', 127],
        ['347109', 'Kraton', 99],
        ['347110', 'Gondomanan', 68],
        ['347111', 'Pakualaman', 46],
        ['347112', 'Mergangsan', 153],
        ['347113', 'Umbulharjo', 246],
        ['347114', 'Kotagede', 138],
    ];

    public function run(): void
    {
        foreach ($this->bacaData() as [$kode, $nama, $jumlah]) {
            ReferensiWilayah::query()->updateOrCreate(
                ['kode_kecamatan' => $kode],
                [
                    'nama_kecamatan' => $nama,
                    'jumlah_kematian_2020' => $jumlah,
                ],
            );
        }
    }

    /**
     * Membaca CSV resmi, lalu memakai data cadangan bila berkas tidak tersedia.
     *
     * @return list<array{0: string, 1: string, 2: int}>
     */
    private function bacaData(): array
    {
        $jalur = base_path('docs/data_kecamatan_2020.csv');

        if (! is_file($jalur)) {
            return self::CADANGAN;
        }

        $berkas = fopen($jalur, 'r');

        if ($berkas === false) {
            return self::CADANGAN;
        }

        fgetcsv($berkas);

        $hasil = [];

        while (($baris = fgetcsv($berkas)) !== false) {
            if (count($baris) < 3 || ! ctype_digit(trim((string) $baris[0]))) {
                continue;
            }

            $hasil[] = [
                trim((string) $baris[0]),
                trim((string) $baris[1]),
                (int) $baris[2],
            ];
        }

        fclose($berkas);

        return $hasil !== [] ? $hasil : self::CADANGAN;
    }
}
