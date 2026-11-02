<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama SATUDUKA: seluruh data adalah data dummy.
 *
 * Sengaja TIDAK memakai `WithoutModelEvents`: event `saving` pada model
 * `Pengguna` dan `LaporanKematian` wajib berjalan agar blind index NIK
 * (`nik_hash` / `nik_almarhum_hash`) terisi untuk data contoh.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ReferensiWilayahSeeder::class,
            PenggunaSeeder::class,
            LaporanKematianSeeder::class,
            VerifikasiSeeder::class,
            LogAuditSeeder::class,
        ]);
    }
}
