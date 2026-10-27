<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel referensi kecamatan Kota Yogyakarta beserta jumlah kematian 2020.
     */
    public function up(): void
    {
        Schema::create('referensi_wilayah', function (Blueprint $table) {
            $table->string('kode_kecamatan', 6)->primary();
            $table->string('nama_kecamatan', 100);
            $table->unsignedInteger('jumlah_kematian_2020');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referensi_wilayah');
    }
};
