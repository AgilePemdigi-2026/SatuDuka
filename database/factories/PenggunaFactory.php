<?php

namespace Database\Factories;

use App\Models\Pengguna;
use App\Support\NikDummy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengguna>
 */
class PenggunaFactory extends Factory
{
    /**
     * @var class-string<Pengguna>
     */
    protected $model = Pengguna::class;

    /**
     * Kata sandi seluruh data contoh.
     */
    public const KATA_SANDI_CONTOH = 'password123';

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_lengkap' => fake()->name(),
            'nik' => NikDummy::berikutnya(),
            'nip' => null,
            'email' => fake()->unique()->safeEmail(),
            'kata_sandi' => self::KATA_SANDI_CONTOH,
            'no_telepon' => fake()->numerify('08##########'),
            'peran' => Pengguna::PERAN_PEMOHON,
        ];
    }

    /**
     * Pengguna berperan pemohon (memiliki NIK, tanpa NIP).
     */
    public function pemohon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'nik' => NikDummy::berikutnya(),
            'nip' => null,
            'peran' => Pengguna::PERAN_PEMOHON,
        ]);
    }

    /**
     * Pengguna berperan verifikator (memiliki NIP, tanpa NIK).
     */
    public function verifikator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'nik' => null,
            'nip' => '99999999'.fake()->numerify('##########'),
            'peran' => Pengguna::PERAN_VERIFIKATOR,
        ]);
    }

    /**
     * Pengguna berperan pejabat penanda tangan (memiliki NIP, tanpa NIK).
     */
    public function pejabat(): static
    {
        return $this->state(fn (array $attributes): array => [
            'nik' => null,
            'nip' => '99999999'.fake()->numerify('##########'),
            'peran' => Pengguna::PERAN_PEJABAT,
        ]);
    }
}
