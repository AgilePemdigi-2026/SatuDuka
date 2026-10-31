<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan gambar tanda tangan visual pejabat beserta hash integritasnya.
     *
     * Gambar disimpan di basis data (bukan disk) karena filesystem hosting bisa
     * sementara. `tanda_tangan_visual` berisi PNG hasil render ulang dalam
     * base64 tanpa awalan data URL.
     */
    public function up(): void
    {
        Schema::table('dokumen_resmi', function (Blueprint $table) {
            $table->text('tanda_tangan_visual')->nullable();
            $table->string('tanda_tangan_hash', 64)->nullable();
            $table->index('tanda_tangan_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokumen_resmi', function (Blueprint $table) {
            $table->dropIndex(['tanda_tangan_hash']);
            $table->dropColumn(['tanda_tangan_visual', 'tanda_tangan_hash']);
        });
    }
};
