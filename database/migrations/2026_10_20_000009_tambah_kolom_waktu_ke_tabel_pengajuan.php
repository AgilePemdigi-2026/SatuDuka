<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `dibuat_pada` dan `diperbarui_pada` pada tabel pengajuan
 * dan tabel yang terkait dengannya.
 *
 * Tabel `laporan_kematian`, `hasil_verifikasi`, `anomali_dokumen`, dan
 * `dokumen_resmi` sebelumnya tidak
 * memiliki kolom waktu buat/ubah sehingga riwayat perubahan status hanya
 * terekam pada `log_audit`. Mulai dari migration ini seluruh tabel aplikasi
 * memiliki jejak waktu ubah.
 *
 * Migration ini sengaja dibuat sebagai migration BARU (bukan mengubah migration
 * lama) agar basis data yang sudah terisi tetap dapat dimigrasikan.
 */
return new class extends Migration
{
    /**
     * Tabel yang mendapat kolom waktu buat dan ubah.
     *
     * @var list<string>
     */
    private array $tabel = [
        'laporan_kematian',
        'hasil_verifikasi',
        'anomali_dokumen',
        'dokumen_resmi',
    ];

    public function up(): void
    {
        foreach ($this->tabel as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->timestamp('dibuat_pada')->nullable();
                $table->timestamp('diperbarui_pada')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tabel as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn(['dibuat_pada', 'diperbarui_pada']);
            });
        }
    }
};
