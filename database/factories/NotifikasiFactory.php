<?php

namespace Database\Factories;

use App\Models\Notifikasi;
use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notifikasi>
 */
class NotifikasiFactory extends Factory
{
    /**
     * @var class-string<Notifikasi>
     */
    protected $model = Notifikasi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pengguna' => Pengguna::factory(),
            'jenis' => Notifikasi::JENIS_INFO,
            'judul' => $this->faker->randomElement([
                'Pengajuan Diterima',
                'Berkas Disetujui',
                'Pengajuan Perlu Diperbaikan',
                'Dokumen Resmi Terbit',
            ]),
            'pesan' => 'Notifikasi dummy SATUDUKA untuk pengujian pusat notifikasi.',
            'sudah_dibaca' => false,
            'dibuat_pada' => now(),
        ];
    }

    /**
     * Notifikasi yang sudah dibaca.
     */
    public function dibaca(): static
    {
        return $this->state(fn (array $atribut) => [
            'sudah_dibaca' => true,
        ]);
    }
}
