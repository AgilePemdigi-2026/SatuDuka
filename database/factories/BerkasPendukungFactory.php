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

        $namaFile = Str::slug($jenis).'.'.fake()->randomElement(['pdf', 'jpg', 'png']);

        return [
            'id_laporan' => LaporanKematian::factory(),
            'jenis_dokumen' => $jenis,
            'nama_file' => $namaFile,
            'path_file' => 'berkas/laporan/'.Str::slug($jenis).'/'.$namaFile,
            'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
            'tanggal_unggah' => now(),
        ];
    }
}
