<?php

namespace Database\Factories;

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BerkasPendukung>
 */
class BerkasPendukungFactory extends Factory
{
    /**
     * @var class-string<BerkasPendukung>
     */
    protected $model = BerkasPendukung::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(BerkasPendukung::DAFTAR_JENIS);

        $ekstensi = fake()->randomElement(['pdf', 'jpg', 'png']);
        $namaFile = Str::slug($jenis).'.'.$ekstensi;

        // Path unik per berkas (meniru unggahan nyata) agar isi dummy tidak
        // pernah berbagi antar laporan dengan data yang berbeda.
        return [
            'id_laporan' => LaporanKematian::factory(),
            'jenis_dokumen' => $jenis,
            'nama_file' => $namaFile,
            'path_file' => 'berkas/laporan/'.Str::random(12).'/'.$namaFile,
            'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
            'tanggal_unggah' => now(),
        ];
    }
}
