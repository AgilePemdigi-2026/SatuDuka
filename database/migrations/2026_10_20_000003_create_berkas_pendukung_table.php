<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel berkas pendukung: dokumen yang dilampirkan pada laporan kematian.
     */
    public function up(): void
    {
        Schema::create('berkas_pendukung', function (Blueprint $table) {
            $table->bigIncrements('id_berkas');
            $table->unsignedBigInteger('id_laporan');
            $table->string('jenis_dokumen', 50);
            $table->string('nama_file', 255);
            $table->string('path_file', 255);
            $table->string('status_unggahan', 20)->default('Menunggu');
            $table->timestamp('tanggal_unggah')->nullable();

            $table->foreign('id_laporan')
                ->references('id_laporan')
                ->on('laporan_kematian')
                ->cascadeOnDelete();

            $table->index('jenis_dokumen');
            $table->index('status_unggahan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas_pendukung');
    }
};
