<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mengaktifkan Row Level Security (RLS) pada seluruh tabel aplikasi di skema `public`.
 *
 * Latar belakang: Data API Supabase (anon key) dapat membaca seluruh tabel di skema
 * `public` bila RLS tidak aktif. Karena tabel aplikasi memuat data sensitif (NIK),
 * RLS diaktifkan TANPA policy sehingga role anon/authenticated tidak mendapat akses apa pun.
 * Aplikasi Laravel sendiri memakai role pemilik tabel (`postgres`), sehingga RLS tidak
 * mengganggu query aplikasi.
 *
 * Migration ini dibungkus pemeriksaan driver agar aman dijalankan pada SQLite
 * (dipakai oleh pengujian otomatis) tanpa error.
 */
return new class extends Migration
{
    /**
     * Tabel aplikasi yang dilindungi RLS.
     *
     * @var list<string>
     */
    private array $tabelAplikasi = [
        'pengguna',
        'laporan_kematian',
        'berkas_pendukung',
        'hasil_verifikasi',
        'anomali_dokumen',
        'dokumen_resmi',
        'log_audit',
    ];

    /**
     * Tabel bawaan Laravel yang juga berada di skema `public` dan perlu dilindungi.
     *
     * @var list<string>
     */
    private array $tabelBawaan = [
        'sessions',
        'password_reset_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function up(): void
    {
        if (! $this->driverPostgres()) {
            return;
        }

        foreach (array_merge($this->tabelAplikasi, $this->tabelBawaan) as $tabel) {
            DB::statement(sprintf('ALTER TABLE IF EXISTS %s ENABLE ROW LEVEL SECURITY', $tabel));
        }
    }

    public function down(): void
    {
        if (! $this->driverPostgres()) {
            return;
        }

        foreach (array_merge($this->tabelAplikasi, $this->tabelBawaan) as $tabel) {
            DB::statement(sprintf('ALTER TABLE IF EXISTS %s DISABLE ROW LEVEL SECURITY', $tabel));
        }
    }

    /**
     * Hanya PostgreSQL yang mengenal Row Level Security.
     */
    private function driverPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }
};
