<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use App\Support\NikDummy;
use Illuminate\Database\Seeder;

/**
 * Akun demo SATUDUKA.
 *
 * Seluruh kredensial di bawah hanya untuk keperluan pengembangan dan demo.
 */
class PenggunaSeeder extends Seeder
{
    /**
     * Kata sandi seluruh akun demo.
     */
    public const KATA_SANDI_DEMO = 'password123';

    /**
     * NIP dummy (kode wilayah fiktif 99999999) untuk petugas.
     */
    private const NIP_VERIFIKATOR = '999999991234567890';

    private const NIP_PEJABAT = '999999992345678901';

    public function run(): void
    {
        Pengguna::factory()->pemohon()->create([
            'nama_lengkap' => 'Pemohon Demo',
            'nik' => NikDummy::denganUrutan(1),
            'email' => 'pemohon@demo.satuduka.id',
            'no_telepon' => '081234567890',
            'kata_sandi' => self::KATA_SANDI_DEMO,
        ]);

        Pengguna::factory()->verifikator()->create([
            'nama_lengkap' => 'Verifikator Demo',
            'nip' => self::NIP_VERIFIKATOR,
            'email' => 'verifikator@demo.satuduka.id',
            'no_telepon' => '081234567891',
            'kata_sandi' => self::KATA_SANDI_DEMO,
        ]);

        Pengguna::factory()->pejabat()->create([
            'nama_lengkap' => 'Pejabat Demo',
            'nip' => self::NIP_PEJABAT,
            'email' => 'pejabat@demo.satuduka.id',
            'no_telepon' => '081234567892',
            'kata_sandi' => self::KATA_SANDI_DEMO,
        ]);

        // Dua pemohon tambahan (bukan akun demo) agar data pengajuan tidak
        // seluruhnya milik satu akun.
        Pengguna::factory()->pemohon()->count(2)->create([
            'kata_sandi' => self::KATA_SANDI_DEMO,
        ]);
    }
}
