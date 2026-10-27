<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->string('jenis_kelamin', 20)->nullable();
            $table->time('waktu_kematian')->nullable();
            $table->string('jenis_tempat', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_kematian', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_kelamin',
                'waktu_kematian',
                'jenis_tempat',
            ]);
        });
    }
};