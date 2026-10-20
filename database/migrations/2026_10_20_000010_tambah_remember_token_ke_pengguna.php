<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `remember_token` pada tabel `pengguna`.
 *
 * IMPROVISASI (Fase 2): kolom ini tidak ada pada PDM (docs/DATABASE.md), tetapi
 * dibutuhkan Laravel untuk fitur "Ingat Saya" pada use case 5.2.2 alur 4.b
 * (menyimpan sesi pengguna pada perangkat). Kolom dibuat nullable dan tidak
 * pernah ditampilkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
