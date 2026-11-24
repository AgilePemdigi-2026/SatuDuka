<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pusat notifikasi pengguna: catatan perubahan status pengajuan yang
     * dikirim sistem (revisi, anomali, dokumen terbit) untuk pemantauan.
     */
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->bigIncrements('id_notifikasi');
            $table->unsignedBigInteger('id_pengguna');
            $table->unsignedBigInteger('id_laporan')->nullable();
            $table->string('jenis', 30)->default('info');
            $table->string('judul', 100);
            $table->text('pesan');
            $table->boolean('sudah_dibaca')->default(false)->index();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_pengguna')
                ->references('id_pengguna')
                ->on('pengguna')
                ->cascadeOnDelete();

            $table->foreign('id_laporan')
                ->references('id_laporan')
                ->on('laporan_kematian')
                ->cascadeOnDelete();

            $table->index(['id_pengguna', 'sudah_dibaca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
