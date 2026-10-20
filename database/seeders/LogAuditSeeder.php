<?php

namespace Database\Seeders;

use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Database\Seeder;

/**
 * Contoh jejak audit. Tabel `log_audit` bersifat append-only: tidak ada pembaruan
 * maupun penghapusan baris log.
 */
class LogAuditSeeder extends Seeder
{
    public function run(): void
    {
        $pemohon = Pengguna::where('email', 'pemohon@demo.satuduka.id')->firstOrFail();
        $verifikator = Pengguna::where('peran', Pengguna::PERAN_VERIFIKATOR)->firstOrFail();

        LogAudit::factory()->create([
            'id_pengguna' => $pemohon->id_pengguna,
            'aksi' => 'mengajukan_laporan',
            'objek' => 'laporan_kematian',
            'id_objek' => null,
            'detail' => ['sumber' => 'formulir pengajuan', 'versi' => 1],
        ]);

        LogAudit::factory()->create([
            'id_pengguna' => $verifikator->id_pengguna,
            'aksi' => 'memverifikasi_berkas',
            'objek' => 'laporan_kematian',
            'id_objek' => null,
            'detail' => ['hasil' => 'diterima'],
        ]);

        LogAudit::factory()->create([
            'id_pengguna' => null,
            'aksi' => 'memeriksa_layanan_eksternal',
            'objek' => 'layanan',
            'id_objek' => null,
            'detail' => ['parameter' => 'nik_almarhum'],
            'layanan_eksternal' => LogAudit::LAYANAN_SIAK,
            'hasil' => LogAudit::HASIL_SUKSES,
        ]);
    }
}
