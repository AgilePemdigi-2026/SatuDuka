<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pengguna: induk bagi ketiga peran aktor (pemohon, verifikator, pejabat).
     */
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->bigIncrements('id_pengguna');
            $table->string('nama_lengkap', 100);
            $table->string('nik', 16)->nullable()->unique();
            $table->string('nip', 20)->nullable()->unique();
            $table->string('email', 100)->unique();
            $table->string('kata_sandi', 255);
            $table->string('no_telepon', 15)->nullable();
            $table->enum('peran', ['pemohon', 'verifikator', 'pejabat'])->default('pemohon');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->index('peran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};
