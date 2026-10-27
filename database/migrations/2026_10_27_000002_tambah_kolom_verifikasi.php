<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan jenis kasus laporan dan hasil deteksi aturan verifikasi.
     */
    public function up(): void
    {
        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->string('jenis_kasus', 50)->default('Normal');
        });

        Schema::table('hasil_verifikasi', function (Blueprint $table) {
            $table->json('deteksi_otomatis')->nullable();
            $table->text('alasan_override')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hasil_verifikasi', function (Blueprint $table) {
            $table->dropColumn(['deteksi_otomatis', 'alasan_override']);
        });

        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->dropColumn('jenis_kasus');
        });
    }
};
