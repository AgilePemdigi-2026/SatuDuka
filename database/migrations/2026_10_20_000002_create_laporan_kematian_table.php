<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel laporan kematian: entitas inti pengajuan yang dibuat oleh pemohon.
     */
    public function up(): void
    {
        Schema::create('laporan_kematian', function (Blueprint $table) {
            $table->bigIncrements('id_laporan');
            $table->unsignedBigInteger('id_pengguna');
            $table->string('no_pengajuan', 30)->unique();
            $table->string('nama_pelapor', 100);
            $table->string('hubungan_pelapor', 50);
            $table->string('nama_almarhum', 100);
            $table->string('nik_almarhum', 16);
            $table->date('tanggal_kematian');
            $table->string('tempat_kematian', 100);
            $table->text('sebab_kematian')->nullable();
            $table->string('status_pengajuan', 30)->default('Draf');
            $table->timestamp('tanggal_pengajuan')->nullable();
            $table->text('catatan_revisi')->nullable();
            $table->boolean('perlu_verifikasi_manual')->default(false);

            $table->foreign('id_pengguna')
                ->references('id_pengguna')
                ->on('pengguna')
                ->restrictOnDelete();

            $table->index('status_pengajuan');
            $table->index('nik_almarhum');
            $table->index('tanggal_kematian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_kematian');
    }
};
