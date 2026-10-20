<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel log audit: jejak audit append-only (NFR-06).
     *
     * Tabel ini tidak memakai created_at/updated_at; hanya memiliki kolom
     * `dibuat_pada` yang diisi otomatis oleh model LogAudit.
     */
    public function up(): void
    {
        Schema::create('log_audit', function (Blueprint $table) {
            $table->bigIncrements('id_log');
            $table->unsignedBigInteger('id_pengguna')->nullable();
            $table->string('aksi', 100);
            $table->string('objek', 50)->nullable();
            $table->unsignedBigInteger('id_objek')->nullable();
            // jsonb pada PostgreSQL, otomatis menjadi text pada SQLite
            // (Laravel menurunkan tipe jsonb ke text bila driver tidak mendukung).
            $table->jsonb('detail')->nullable();
            $table->string('layanan_eksternal', 30)->nullable();
            $table->string('hasil', 20)->nullable();
            $table->timestamp('dibuat_pada')->nullable();

            $table->foreign('id_pengguna')
                ->references('id_pengguna')
                ->on('pengguna')
                ->nullOnDelete();

            $table->index('aksi');
            $table->index('objek');
            $table->index('layanan_eksternal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_audit');
    }
};
