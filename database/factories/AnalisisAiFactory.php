<?php

namespace Database\Factories;

use App\Models\AnalisisAi;
use App\Models\LaporanKematian;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AnalisisAi>
 */
class AnalisisAiFactory extends Factory
{
    /**
     * @var class-string<AnalisisAi>
     */
    protected $model = AnalisisAi::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_laporan' => LaporanKematian::factory(),
            'id_berkas' => null,
            'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
            'hasil' => ['berhasil' => true],
            'skor' => 0.9,
            'model' => 'mock-ai-v1',
            'status' => AnalisisAi::STATUS_BERHASIL,
            'hash_berkas' => hash('sha256', Str::random(40)),
            'dibuat_pada' => now(),
        ];
    }
}
