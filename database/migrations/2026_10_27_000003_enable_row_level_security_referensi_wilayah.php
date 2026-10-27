<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mengaktifkan Row Level Security pada tabel referensi_wilayah.
     *
     * Tanpa policy, role anon/authenticated Supabase tidak dapat membaca tabel.
     * Laravel memakai role pemilik tabel sehingga tidak terpengaruh RLS.
     * Migration ini hanya berjalan pada PostgreSQL.
     */
    public function up(): void
    {
        if (! $this->driverPostgres()) {
            return;
        }

        DB::statement('ALTER TABLE IF EXISTS referensi_wilayah ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        if (! $this->driverPostgres()) {
            return;
        }

        DB::statement('ALTER TABLE IF EXISTS referensi_wilayah DISABLE ROW LEVEL SECURITY');
    }

    private function driverPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }
};
