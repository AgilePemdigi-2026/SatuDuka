<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel hasil verifikasi: catatan pemeriksaan berkas oleh verifikator.
     */
    public function up(): void
    {
        Schema::create('hasil_verifikasi', function (Blueprint $table) {
            $table->bigIncrements('id_verifikasi');
            $table->unsignedBigInteger('id_laporan');
            $table->unsignedBigInteger('id_pengguna');
            $table->string('status_verifikasi', 30);
            $table->text('catatan')->nullable();
            $table->timestamp('tanggal_verifikasi')->nullable();

            $table->foreign('id_laporan')
                ->references('id_laporan')
                ->on('laporan_kematian')
                ->cascadeOnDelete();

            $table->foreign('id_pengguna')
                ->references('id_pengguna')
                ->on('pengguna')
                ->restrictOnDelete();

            $table->index('status_verifikasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_verifikasi');
    }
};
