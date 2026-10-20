<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mengizinkan draf pengajuan menyimpan data yang belum lengkap.
 *
 * Nomor pengajuan baru diterbitkan saat pengajuan dikirim, sehingga draf harus
 * dapat disimpan tanpa nomor. Kolom data pelapor, data almarhum, dan tanggal
 * kematian juga dibuat boleh kosong agar draf dapat diisi bertahap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->string('no_pengajuan', 30)->nullable()->change();
            $table->string('nama_pelapor', 100)->nullable()->change();
            $table->string('hubungan_pelapor', 50)->nullable()->change();
            $table->string('nama_almarhum', 100)->nullable()->change();
            $table->string('nik_almarhum', 16)->nullable()->change();
            $table->date('tanggal_kematian')->nullable()->change();
            $table->string('tempat_kematian', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->string('no_pengajuan', 30)->nullable(false)->change();
            $table->string('nama_pelapor', 100)->nullable(false)->change();
            $table->string('hubungan_pelapor', 50)->nullable(false)->change();
            $table->string('nama_almarhum', 100)->nullable(false)->change();
            $table->string('nik_almarhum', 16)->nullable(false)->change();
            $table->date('tanggal_kematian')->nullable(false)->change();
            $table->string('tempat_kematian', 100)->nullable(false)->change();
        });
    }
};
