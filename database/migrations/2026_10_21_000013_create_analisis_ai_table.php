<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan hasil analisis dokumen berbantuan AI.
     *
     * Analisis hanya menjadi bahan pertimbangan; tabel ini tidak menyimpan
     * keputusan verifikasi dan tidak mengubah status pengajuan.
     */
    public function up(): void
    {
        Schema::create('analisis_ai', function (Blueprint $table) {
            $table->bigIncrements('id_analisis');
            $table->unsignedBigInteger('id_laporan');
            $table->unsignedBigInteger('id_berkas')->nullable();
            $table->string('jenis', 20);
            $table->jsonb('hasil')->nullable();
            $table->decimal('skor', 5, 4)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('status', 20);
            $table->string('hash_berkas', 64)->nullable();
            $table->timestamp('dibuat_pada')->nullable();

            $table->foreign('id_laporan')
                ->references('id_laporan')
                ->on('laporan_kematian')
                ->cascadeOnDelete();

            $table->foreign('id_berkas')
                ->references('id_berkas')
                ->on('berkas_pendukung')
                ->nullOnDelete();

            $table->index('jenis');
            $table->index('status');
            $table->index('hash_berkas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_ai');
    }
};
