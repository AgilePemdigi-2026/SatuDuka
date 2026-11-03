<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penguatan Fase 9: trigger append-only `log_audit` dan indeks kinerja.
 *
 * Trigger append-only:
 * - DELETE selalu ditolak;
 * - UPDATE ditolak, KECUALI pembaruan yang hanya mengosongkan `id_pengguna`
 *   akibat foreign key `ON DELETE SET NULL` ketika akun pengguna dihapus.
 *   Pengecualian ini mencegah penghapusan pengguna gagal diam-diam: baris
 *   audit tetap ada, hanya relasi pelakunya yang menjadi NULL.
 *
 * PostgreSQL memakai fungsi + trigger, SQLite memakai trigger dengan klausa
 * WHEN yang setara agar perilaku pengujian sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION satuduka_log_audit_append_only()
                RETURNS trigger AS $$
                BEGIN
                    IF TG_OP = 'DELETE' THEN
                        RAISE EXCEPTION 'log_audit bersifat append-only: penghapusan ditolak.';
                    END IF;

                    -- Satu-satunya UPDATE yang diizinkan: foreign key
                    -- ON DELETE SET NULL mengosongkan id_pengguna saat akun
                    -- pengguna dihapus, tanpa mengubah isi audit lainnya.
                    IF NEW.id_pengguna IS NOT NULL
                       OR OLD.id_pengguna IS NULL
                       OR NEW.id_log IS DISTINCT FROM OLD.id_log
                       OR NEW.aksi IS DISTINCT FROM OLD.aksi
                       OR NEW.objek IS DISTINCT FROM OLD.objek
                       OR NEW.id_objek IS DISTINCT FROM OLD.id_objek
                       OR NEW.detail IS DISTINCT FROM OLD.detail
                       OR NEW.layanan_eksternal IS DISTINCT FROM OLD.layanan_eksternal
                       OR NEW.hasil IS DISTINCT FROM OLD.hasil
                       OR NEW.dibuat_pada IS DISTINCT FROM OLD.dibuat_pada
                    THEN
                        RAISE EXCEPTION 'log_audit bersifat append-only: perubahan ditolak.';
                    END IF;

                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS log_audit_append_only ON log_audit;

                CREATE TRIGGER log_audit_append_only
                BEFORE UPDATE OR DELETE ON log_audit
                FOR EACH ROW EXECUTE FUNCTION satuduka_log_audit_append_only();
            SQL);
        }

        if ($driver === 'sqlite') {
            DB::unprepared(<<<'SQL'
                DROP TRIGGER IF EXISTS log_audit_tolak_update;
                DROP TRIGGER IF EXISTS log_audit_tolak_delete;

                CREATE TRIGGER log_audit_tolak_delete
                BEFORE DELETE ON log_audit
                FOR EACH ROW
                BEGIN
                    SELECT RAISE(ABORT, 'log_audit bersifat append-only: penghapusan ditolak.');
                END;

                CREATE TRIGGER log_audit_tolak_update
                BEFORE UPDATE ON log_audit
                FOR EACH ROW
                WHEN NOT (
                    NEW.id_pengguna IS NULL
                    AND OLD.id_pengguna IS NOT NULL
                    AND NEW.id_log IS OLD.id_log
                    AND NEW.aksi IS OLD.aksi
                    AND NEW.objek IS OLD.objek
                    AND NEW.id_objek IS OLD.id_objek
                    AND NEW.detail IS OLD.detail
                    AND NEW.layanan_eksternal IS OLD.layanan_eksternal
                    AND NEW.hasil IS OLD.hasil
                    AND NEW.dibuat_pada IS OLD.dibuat_pada
                )
                BEGIN
                    SELECT RAISE(ABORT, 'log_audit bersifat append-only: perubahan ditolak.');
                END;
            SQL);
        }

        // Indeks untuk kolom yang sering dicari/disaring dan foreign key.
        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->index(['id_pengguna', 'status_pengajuan'], 'laporan_pengguna_status_idx');
            $table->index(['status_pengajuan', 'tanggal_pengajuan'], 'laporan_status_tanggal_idx');
        });

        Schema::table('berkas_pendukung', function (Blueprint $table) {
            $table->index(['id_laporan', 'status_unggahan'], 'berkas_laporan_status_idx');
        });

        Schema::table('hasil_verifikasi', function (Blueprint $table) {
            $table->index('id_laporan', 'verifikasi_laporan_idx');
            $table->index(['id_laporan', 'tanggal_verifikasi'], 'verifikasi_laporan_tanggal_idx');
        });

        Schema::table('anomali_dokumen', function (Blueprint $table) {
            $table->index('id_verifikasi', 'anomali_verifikasi_idx');
        });

        Schema::table('analisis_ai', function (Blueprint $table) {
            $table->index('id_laporan', 'analisis_laporan_idx');
        });

        Schema::table('dokumen_resmi', function (Blueprint $table) {
            $table->index('id_pengguna', 'dokumen_pengguna_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_resmi', function (Blueprint $table) {
            $table->dropIndex('dokumen_pengguna_idx');
        });

        Schema::table('analisis_ai', function (Blueprint $table) {
            $table->dropIndex('analisis_laporan_idx');
        });

        Schema::table('anomali_dokumen', function (Blueprint $table) {
            $table->dropIndex('anomali_verifikasi_idx');
        });

        Schema::table('hasil_verifikasi', function (Blueprint $table) {
            $table->dropIndex('verifikasi_laporan_tanggal_idx');
            $table->dropIndex('verifikasi_laporan_idx');
        });

        Schema::table('berkas_pendukung', function (Blueprint $table) {
            $table->dropIndex('berkas_laporan_status_idx');
        });

        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->dropIndex('laporan_status_tanggal_idx');
            $table->dropIndex('laporan_pengguna_status_idx');
        });

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS log_audit_append_only ON log_audit;');
            DB::unprepared('DROP FUNCTION IF EXISTS satuduka_log_audit_append_only();');
        }

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS log_audit_tolak_update;');
            DB::unprepared('DROP TRIGGER IF EXISTS log_audit_tolak_delete;');
        }
    }
};
