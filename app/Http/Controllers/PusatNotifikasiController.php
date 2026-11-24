<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pusat notifikasi pengguna (utama untuk Pemohon).
 *
 * Notifikasi dibuat oleh sistem pada perubahan status penting; halaman ini
 * hanya membaca dan menandai sudah dibaca. Isi pesan tidak pernah memuat NIK.
 */
class PusatNotifikasiController extends Controller
{
    /**
     * Daftar notifikasi milik pengguna dengan tab dan pencarian.
     */
    public function indeks(Request $request): View
    {
        $pengguna = $request->user();
        $tab = (string) $request->query('tab', 'semua');
        $cari = trim((string) $request->query('cari', ''));

        $query = Notifikasi::query()
            ->where('id_pengguna', $pengguna->id_pengguna)
            ->with('laporan');

        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('judul', 'like', "%{$cari}%")
                    ->orWhere('pesan', 'like', "%{$cari}%")
                    ->orWhereHas('laporan', function ($qLaporan) use ($cari) {
                        $qLaporan->where('no_pengajuan', 'like', "%{$cari}%");
                    });
            });
        }

        $query = match ($tab) {
            'belum_dibaca' => $query->where('sudah_dibaca', false),
            'perlu_tindakan' => $query->where('jenis', Notifikasi::JENIS_PERLU_DIPERBAIKI),
            'selesai' => $query->where('jenis', Notifikasi::JENIS_DOKUMEN_TERBIT),
            default => $query,
        };

        $notifikasis = $query
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id_notifikasi')
            ->paginate(15)
            ->withQueryString();

        $jumlahBelumDibaca = Notifikasi::query()
            ->where('id_pengguna', $pengguna->id_pengguna)
            ->where('sudah_dibaca', false)
            ->count();

        return view('notifikasi.index', compact('pengguna', 'notifikasis', 'jumlahBelumDibaca', 'tab', 'cari'));
    }

    /**
     * Tandai seluruh notifikasi pengguna sebagai sudah dibaca.
     */
    public function tandaiSemuaDibaca(Request $request): RedirectResponse
    {
        Notifikasi::query()
            ->where('id_pengguna', $request->user()->id_pengguna)
            ->where('sudah_dibaca', false)
            ->update(['sudah_dibaca' => true]);

        return back()->with('sukses', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function tandaiDibaca(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        abort_unless(
            (int) $notifikasi->id_pengguna === (int) $request->user()->id_pengguna,
            404,
        );

        $notifikasi->update(['sudah_dibaca' => true]);

        return back()->with('sukses', 'Notifikasi ditandai sebagai dibaca.');
    }
}
