<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom profil tambahan: alamat dan lokasi foto profil di disk privat.
     */
    public function up(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->text('alamat')->nullable();
            $table->string('path_foto', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengguna', function (Blueprint $table) {
            $table->dropColumn(['alamat', 'path_foto']);
        });
    }
};
