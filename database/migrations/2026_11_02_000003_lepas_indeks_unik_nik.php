<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Langkah 3 enkripsi NIK: lepas indeks lama pada kolom plaintext.
 *
 * Indeks unik `pengguna_nik_unique` dan indeks `nik_almarhum` tidak berguna
 * lagi karena kolom NIK kini berisi ciphertext acak. Keunikan NIK dijamin oleh
 * `pengguna_nik_hash_unique` dan pencarian memakai indeks hash.
 *
 * Penghapusan dijaga pemeriksaan keberadaan indeks agar aman diulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (self::punyaIndeks('pengguna', 'pengguna_nik_unique')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->dropUnique('pengguna_nik_unique');
            });
        }

        if (self::punyaIndeks('laporan_kematian', 'laporan_kematian_nik_almarhum_index')) {
            Schema::table('laporan_kematian', function (Blueprint $table) {
                $table->dropIndex('laporan_kematian_nik_almarhum_index');
            });
        }
    }

    public function down(): void
    {
        if (! self::punyaIndeks('pengguna', 'pengguna_nik_unique')) {
            Schema::table('pengguna', function (Blueprint $table) {
                $table->unique('nik', 'pengguna_nik_unique');
            });
        }

        if (! self::punyaIndeks('laporan_kematian', 'laporan_kematian_nik_almarhum_index')) {
            Schema::table('laporan_kematian', function (Blueprint $table) {
                $table->index('nik_almarhum', 'laporan_kematian_nik_almarhum_index');
            });
        }
    }

    private static function punyaIndeks(string $tabel, string $nama): bool
    {
        return collect(Schema::getIndexes($tabel))
            ->contains(fn (array $indeks): bool => ($indeks['name'] ?? null) === $nama);
    }
};
