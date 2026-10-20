<?php

namespace Database\Factories;

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DokumenResmi>
 */
class DokumenResmiFactory extends Factory
{
    /**
     * @var class-string<DokumenResmi>
     */
    protected $model = DokumenResmi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_laporan' => LaporanKematian::factory(),
            'id_pengguna' => null,
            'no_dokumen' => null,
            'tanggal_terbit' => null,
            'status_ttd' => DokumenResmi::STATUS_MENUNGGU,
            'file_dokumen' => null,
            'alasan_pengembalian' => null,
            'dibuat_pada' => now(),
            'diperbarui_pada' => now(),
        ];
    }

    /**
     * Dokumen resmi yang sudah ditandatangani dan diterbitkan.
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_ttd' => DokumenResmi::STATUS_SELESAI,
            'no_dokumen' => 'AK-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'tanggal_terbit' => now(),
            'file_dokumen' => 'dokumen/akta-kematian.pdf',
        ]);
    }
}
