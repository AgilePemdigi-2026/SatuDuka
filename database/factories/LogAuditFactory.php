<?php

namespace Database\Factories;

use App\Models\LogAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogAudit>
 */
class LogAuditFactory extends Factory
{
    /**
     * @var class-string<LogAudit>
     */
    protected $model = LogAudit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pengguna' => null,
            'aksi' => fake()->randomElement([
                'menyimpan_laporan',
                'mengunggah_berkas',
                'memverifikasi_berkas',
                'menerbitkan_dokumen',
                'memeriksa_layanan_eksternal',
            ]),
            'objek' => null,
            'id_objek' => null,
            'detail' => null,
            'layanan_eksternal' => null,
            'hasil' => null,
        ];
    }
}
