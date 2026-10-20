<?php

namespace Database\Seeders;

use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\LayananAudit;
use Illuminate\Database\Seeder;

/**
 * Contoh jejak audit. Tabel `log_audit` bersifat append-only: tidak ada pembaruan
 * maupun penghapusan baris log. Seluruh pencatatan memakai {@see LayananAudit}
 * agar formatnya sama dengan aksi pada aplikasi.
 */
class LogAuditSeeder extends Seeder
{
    public function run(): void
    {
        $pemohon = Pengguna::where('email', 'pemohon@demo.satuduka.id')->firstOrFail();
        $verifikator = Pengguna::where('peran', Pengguna::PERAN_VERIFIKATOR)->firstOrFail();

        LayananAudit::catat(
            aksi: 'mengajukan_laporan',
            objek: 'laporan_kematian',
            detail: ['sumber' => 'formulir pengajuan', 'versi' => 1],
            pengguna: $pemohon,
        );

        LayananAudit::catat(
            aksi: 'memverifikasi_berkas',
            objek: 'laporan_kematian',
            detail: ['hasil' => 'diterima'],
            pengguna: $verifikator,
        );

        LayananAudit::catatLayanan(
            layanan: LogAudit::LAYANAN_SIAK,
            hasil: LogAudit::HASIL_SUKSES,
            detail: ['parameter' => 'nik_almarhum'],
        );
    }
}
