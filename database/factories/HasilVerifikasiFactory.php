<?php

namespace Database\Factories;

use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HasilVerifikasi>
 */
class HasilVerifikasiFactory extends Factory
{
    /**
     * @var class-string<HasilVerifikasi>
     */
    protected $model = HasilVerifikasi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_laporan' => LaporanKematian::factory(),
            'id_pengguna' => Pengguna::factory()->verifikator(),
            'status_verifikasi' => HasilVerifikasi::STATUS_DITERIMA,
            'catatan' => 'Berkas lengkap dan data sesuai dengan data kependudukan.',
            'deteksi_otomatis' => null,
            'alasan_override' => null,
            'tanggal_verifikasi' => now(),
            'dibuat_pada' => now(),
            'diperbarui_pada' => now(),
        ];
    }

    /**
     * Hasil verifikasi dengan status tertentu.
     */
    public function denganStatus(string $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_verifikasi' => $status,
        ]);
    }
}
