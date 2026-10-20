<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel anomali dokumen: ketidaksesuaian data yang ditemukan saat verifikasi.
     */
    public function up(): void
    {
        Schema::create('anomali_dokumen', function (Blueprint $table) {
            $table->bigIncrements('id_anomali');
            $table->unsignedBigInteger('id_verifikasi');
            $table->string('jenis_anomali', 50);
            $table->text('deskripsi');
            $table->string('status_rekonsiliasi', 20)->default('Terbuka');
            $table->timestamp('tanggal_rekonsiliasi')->nullable();

            $table->foreign('id_verifikasi')
                ->references('id_verifikasi')
                ->on('hasil_verifikasi')
                ->cascadeOnDelete();

            $table->index('status_rekonsiliasi');
            $table->index('jenis_anomali');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anomali_dokumen');
    }
};
