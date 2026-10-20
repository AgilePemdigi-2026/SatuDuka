<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel dokumen resmi: akta kematian resmi hasil terbitan sistem (relasi 1:1 ke laporan).
     */
    public function up(): void
    {
        Schema::create('dokumen_resmi', function (Blueprint $table) {
            $table->bigIncrements('id_dokumen');
            $table->unsignedBigInteger('id_laporan')->unique();
            $table->unsignedBigInteger('id_pengguna')->nullable();
            $table->string('no_dokumen', 30)->nullable()->unique();
            $table->timestamp('tanggal_terbit')->nullable();
            $table->string('status_ttd', 20)->default('Menunggu');
            $table->string('file_dokumen', 255)->nullable();
            $table->text('alasan_pengembalian')->nullable();

            $table->foreign('id_laporan')
                ->references('id_laporan')
                ->on('laporan_kematian')
                ->cascadeOnDelete();

            $table->foreign('id_pengguna')
                ->references('id_pengguna')
                ->on('pengguna')
                ->nullOnDelete();

            $table->index('status_ttd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_resmi');
    }
};
