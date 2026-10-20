<?php

namespace Database\Factories;

use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Support\DataSIAK;
use App\Support\NikDummy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanKematian>
 */
class LaporanKematianFactory extends Factory
{
    /**
     * @var class-string<LaporanKematian>
     */
    protected $model = LaporanKematian::class;

    /**
     * Penghitung nomor pengajuan agar tetap unik.
     */
    private static int $urutanPengajuan = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Data almarhum diambil dari data dummy SIAK agar verifikasi silang
        // pada fase berikutnya selalu menemukan data acuan.
        $dataSIAK = DataSIAK::acak();

        return [
            'id_pengguna' => Pengguna::factory()->pemohon(),
            'no_pengajuan' => self::nomorPengajuanBerikutnya(),
            'nama_pelapor' => fake()->name(),
            'hubungan_pelapor' => fake()->randomElement(['Anak', 'Istri', 'Suami', 'Orang Tua', 'Saudara', 'Keponakan']),
            'nama_almarhum' => $dataSIAK['nama_lengkap'] ?? fake()->name(),
            'nik_almarhum' => $dataSIAK['nik'] ?? NikDummy::berikutnya(),
            'tanggal_kematian' => fake()->dateTimeBetween('-2 years', '-1 day')->format('Y-m-d'),
            'tempat_kematian' => fake()->randomElement([
                'RS Sardjito Yogyakarta',
                'RS Bethesda Yogyakarta',
                'RS Umum Dr. Fatehi Rohman',
                'RS Charitas Yogyakarta',
            ]),
            'sebab_kematian' => fake()->randomElement([
                'Penyakit Jantung Koroner',
                'Stroke',
                'Gagal Ginjal',
                'Diabetes Mellitus',
                'Kecelakaan Lalu Lintas',
            ]),
            'status_pengajuan' => LaporanKematian::STATUS_DRAF,
            'tanggal_pengajuan' => null,
            'catatan_revisi' => null,
            'perlu_verifikasi_manual' => false,
        ];
    }

    /**
     * Pengajuan dengan status tertentu.
     */
    public function denganStatus(string $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_pengajuan' => $status,
            'tanggal_pengajuan' => $status === LaporanKematian::STATUS_DRAF ? null : now(),
        ]);
    }

    /**
     * Pengajuan yang sudah dikirim (memiliki tanggal pengajuan).
     */
    public function terkirim(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            'tanggal_pengajuan' => now(),
        ]);
    }

    /**
     * Pengajuan yang ditolak dan membutuhkan revisi.
     */
    public function butuhRevisi(?string $catatan = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status_pengajuan' => LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
            'tanggal_pengajuan' => now(),
            'catatan_revisi' => $catatan ?? 'Surat keterangan kematian kurang jelas, mohon unggah ulang.',
        ]);
    }

    /**
     * Nomor pengajuan berikutnya dengan format SD-YYYYMMDD-NNNN.
     */
    public static function nomorPengajuanBerikutnya(): string
    {
        self::$urutanPengajuan++;

        return sprintf('SD-%s-%04d', now()->format('Ymd'), self::$urutanPengajuan);
    }
}
