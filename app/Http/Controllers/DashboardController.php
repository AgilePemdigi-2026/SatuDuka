<?php

namespace App\Http\Controllers;

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
                    ->orderByDesc('dibuat_pada')
                    ->get(),
                'pengajuanSaya' => $pengguna->laporanKematian()
                    ->where('status_pengajuan', '!=', LaporanKematian::STATUS_DRAF)
                    ->orderByDesc('dibuat_pada')
                    ->limit(5)
                    ->get(),
                'ringkasanStatus' => $pengguna->laporanKematian()
                    ->where('status_pengajuan', '!=', LaporanKematian::STATUS_DRAF)
                    ->selectRaw('status_pengajuan, count(*) as jumlah')
                    ->groupBy('status_pengajuan')
                    ->pluck('jumlah', 'status_pengajuan')
                    ->filter(fn ($jumlah) => (int) $jumlah > 0)
                    ->sortBy(fn ($jumlah, string $status) => array_search($status, LaporanKematian::DAFTAR_STATUS, true)),
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
            default => view('dashboard.pejabat', ['pengguna' => $pengguna]),
        };
    }
}
