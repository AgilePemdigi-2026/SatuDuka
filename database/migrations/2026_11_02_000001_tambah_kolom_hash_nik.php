<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langkah 1 enkripsi NIK: tambah kolom blind index dan lebarkan kolom NIK.
 *
 * Tahap ini belum mengubah data: kolom NIK hanya diubah tipenya menjadi `text`
 * agar cukup menampung ciphertext. Indeks unik lama pada plaintext dilepas
 * pada migrasi ketiga, setelah hash terisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->string('nik_hash', 64)->nullable();
            $table->unique('nik_hash', 'pengguna_nik_hash_unique');
        });

        Schema::table('pengguna', function (Blueprint $table) {
            $table->text('nik')->nullable()->change();
        });

        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->string('nik_almarhum_hash', 64)->nullable();
            $table->index(
                ['nik_almarhum_hash', 'status_pengajuan'],
                'laporan_nik_hash_status_idx',
            );
        });

        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->text('nik_almarhum')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->dropIndex('laporan_nik_hash_status_idx');
            $table->dropColumn('nik_almarhum_hash');
            $table->string('nik_almarhum', 16)->nullable()->change();
        });

        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropUnique('pengguna_nik_hash_unique');
            $table->dropColumn('nik_hash');
            $table->string('nik', 16)->nullable()->change();
        });
    }
};
