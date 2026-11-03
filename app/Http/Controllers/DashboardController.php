<?php

namespace App\Http\Controllers;

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use Illuminate\Http\Request;

/**
 * Controller dashboard sesuai peran (use case 5.2.2 langkah 6).
 *
 * Isi dashboard dikerjakan bertahap mengikuti urutan fitur; saat ini hanya
 * kerangkanya.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard sesuai peran pengguna yang masuk.
     */
    public function tampilkan(Request $request)
    {
        $pengguna = $request->user();

        return match (true) {
            $pengguna->adalahPemohon() => view('dashboard.pemohon', [
                'pengguna' => $pengguna,
                'perluTindakan' => $pengguna->laporanKematian()
                    ->whereIn('status_pengajuan', LaporanKematian::DAFTAR_STATUS_BOLEH_DIUBAH)
                    ->with(['dokumenResmi', 'hasilVerifikasiRevisiTerbaru'])
                    ->orderByDesc('dibuat_pada')
                    ->get(),
                'pengajuanSaya' => $pengguna->laporanKematian()
                    ->whereIn('status_pengajuan', [
                        LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                        LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
                        LaporanKematian::STATUS_SELESAI,
                    ])
                    ->with(['dokumenResmi', 'hasilVerifikasiRevisiTerbaru'])
                    ->orderByDesc('dibuat_pada')
                    ->limit(5)
                    ->get(),
                'statistikPemohon' => [
                    'total' => $pengguna->laporanKematian()->count(),
                    'menunggu' => $pengguna->laporanKematian()
                        ->where('status_pengajuan', LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
                        ->count(),
                    'perluPerbaikan' => $pengguna->laporanKematian()
                        ->whereIn('status_pengajuan', [
                            LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
                            LaporanKematian::STATUS_DIKEMBALIKAN,
                            LaporanKematian::STATUS_MENUNGGU_PERBAIKAN,
                        ])
                        ->count(),
                    'selesai' => $pengguna->laporanKematian()
                        ->where('status_pengajuan', LaporanKematian::STATUS_SELESAI)
                        ->count(),
                ],
            ]),
            $pengguna->adalahVerifikator() => view('dashboard.verifikator', [
                'pengguna' => $pengguna,
                'statistik' => [
                    'menunggu' => LaporanKematian::query()
                        ->where('status_pengajuan', LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
                        ->count(),
                    'rekonsiliasi' => LaporanKematian::query()
                        ->where('status_pengajuan', LaporanKematian::STATUS_DALAM_REKONSILIASI)
                        ->count(),
                    'selesai' => LaporanKematian::query()
                        ->where('status_pengajuan', LaporanKematian::STATUS_SELESAI)
                        ->count(),
                ],
                'antrean' => LaporanKematian::query()
                    ->whereIn('status_pengajuan', [
                        LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                        LaporanKematian::STATUS_DALAM_REKONSILIASI,
                    ])
                    ->orderBy('tanggal_pengajuan')
                    ->limit(5)
                    ->get(),
            ]),
            default => view('dashboard.pejabat', [
                'pengguna' => $pengguna,
                'statistik' => [
                    'menunggu' => DokumenResmi::query()
                        ->where('status_ttd', DokumenResmi::STATUS_MENUNGGU)
                        ->count(),
                    'selesai' => DokumenResmi::query()
                        ->where('status_ttd', DokumenResmi::STATUS_SELESAI)
                        ->count(),
                    'dikembalikan' => DokumenResmi::query()
                        ->where('status_ttd', DokumenResmi::STATUS_DIKEMBALIKAN)
                        ->count(),
                ],
                'dokumenMenunggu' => DokumenResmi::query()
                    ->with('laporanKematian')
                    ->where('status_ttd', DokumenResmi::STATUS_MENUNGGU)
                    ->orderBy('dibuat_pada')
                    ->orderBy('id_dokumen')
                    ->limit(5)
                    ->get(),
            ]),
        };
    }
}
