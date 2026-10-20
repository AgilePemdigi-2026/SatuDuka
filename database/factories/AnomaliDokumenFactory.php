<?php

namespace Database\Factories;

use App\Models\AnomaliDokumen;
use App\Models\HasilVerifikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnomaliDokumen>
 */
class AnomaliDokumenFactory extends Factory
{
    /**
     * @var class-string<AnomaliDokumen>
     */
    protected $model = AnomaliDokumen::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_verifikasi' => HasilVerifikasi::factory(),
            'jenis_anomali' => fake()->randomElement([
                AnomaliDokumen::JENIS_NIK_GANDA,
                AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
                AnomaliDokumen::JENIS_TANGGAL_KEMATIAN,
                AnomaliDokumen::JENIS_TEMPAT_KEMATIAN,
                AnomaliDokumen::JENIS_DOKUMEN_TIDAK_TERBACA,
            ]),
            'deskripsi' => 'Data pada berkas tidak sama dengan data master kependudukan.',
            'status_rekonsiliasi' => AnomaliDokumen::STATUS_TERBUKA,
            'tanggal_rekonsiliasi' => null,
        ];
    }

    /**
     * Anomali yang sudah selesai direkonsiliasi.
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_rekonsiliasi' => AnomaliDokumen::STATUS_SELESAI,
            'tanggal_rekonsiliasi' => now(),
        ]);
    }
}
