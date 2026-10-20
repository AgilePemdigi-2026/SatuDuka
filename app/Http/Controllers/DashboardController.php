<?php

namespace App\Http\Controllers;

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
                'daftarLaporan' => $pengguna->laporanKematian()->orderByDesc('dibuat_pada')->get(),
            ]),
            $pengguna->adalahVerifikator() => view('dashboard.verifikator', ['pengguna' => $pengguna]),
            default => view('dashboard.pejabat', ['pengguna' => $pengguna]),
        };
    }
}
