<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan skor kecurigaan 0-100 pada catatan analisis.
     *
     * Kolom `skor` yang lama dipakai untuk keyakinan ekstraksi (0-1),
     * sehingga skor kecurigaan memakai kolom terpisah.
     */
    public function up(): void
    {
        Schema::table('analisis_ai', function (Blueprint $table) {
            $table->decimal('skor_kecurigaan', 5, 2)->nullable()->after('skor');
            $table->index('skor_kecurigaan');
        });
    }

    public function down(): void
    {
        Schema::table('analisis_ai', function (Blueprint $table) {
            $table->dropIndex(['skor_kecurigaan']);
            $table->dropColumn('skor_kecurigaan');
        });
    }
};
