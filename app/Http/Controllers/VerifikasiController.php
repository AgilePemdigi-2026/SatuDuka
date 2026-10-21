<?php

namespace App\Http\Controllers;

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Services\Ai\LayananAnalisisAi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Verifikasi berkas versi baca saja.
 *
 * Halaman ini hanya menampilkan data, pratinjau berkas, dan hasil bantuan AI.
 * Tidak ada tombol menyetujui atau menolak pada tahap ini.
 */
class VerifikasiController extends Controller
{
    /**
     * Daftar pengajuan yang menunggu verifikasi.
     */
    public function daftar(Request $request)
    {
        $daftar = LaporanKematian::query()
            ->where('status_pengajuan', LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
            ->orderByDesc('tanggal_pengajuan')
            ->paginate(10)
            ->withQueryString();

        return view('verifikasi.daftar', [
            'pengguna' => $request->user(),
            'daftar' => $daftar,
        ]);
    }

    /**
     * Detail satu pengajuan beserta daftar berkas dan panel AI.
     */
    public function detail(Request $request, LaporanKematian $laporan)
    {
        $this->pastikanMenungguVerifikasi($laporan);

        return view('verifikasi.detail', [
            'pengguna' => $request->user(),
            'laporan' => $laporan->load('berkasPendukung'),
            'analisis' => session('analisis_ai'),
        ]);
    }

    /**
     * Pratinjau berkas melalui rute khusus peran verifikator.
     */
    public function pratinjauBerkas(Request $request, LaporanKematian $laporan, BerkasPendukung $berkas)
    {
        $this->pastikanMenungguVerifikasi($laporan);

        abort_unless((int) $berkas->id_laporan === (int) $laporan->id_laporan, 404);
        abort_unless($berkas->path_file !== '' && Storage::disk('local')->exists($berkas->path_file), 404);

        return Storage::disk('local')->response($berkas->path_file, $berkas->nama_file);
    }

    /**
     * Membaca dokumen dengan bantuan AI. Status pengajuan tidak diubah.
     */
    public function analisis(Request $request, LaporanKematian $laporan, LayananAnalisisAi $layanan)
    {
        $this->pastikanMenungguVerifikasi($laporan);

        $data = $request->validate([
            'id_berkas' => ['required', 'integer'],
        ], [
            'id_berkas.required' => 'Pilih dokumen yang akan dibaca.',
        ]);

        $berkas = $laporan->berkasPendukung()
            ->whereKey($data['id_berkas'])
            ->where('jenis_dokumen', BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN)
            ->first();

        if ($berkas === null) {
            return redirect()
                ->route('verifikasi.detail', $laporan)
                ->with('galat', 'Surat Keterangan Kematian tidak ditemukan.');
        }

        $hasil = $layanan->periksa($berkas, $request->user(), 'verifikator_detail');

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('verifikasi.detail', $laporan)
                ->with('galat', $hasil['pesan']);
        }

        return redirect()
            ->route('verifikasi.detail', $laporan)
            ->with('analisis_ai', $hasil)
            ->with('sukses', 'Analisis AI selesai. Hasil bantuan AI, hanya saran.');
    }

    private function pastikanMenungguVerifikasi(LaporanKematian $laporan): void
    {
        abort_unless($laporan->status_pengajuan === LaporanKematian::STATUS_MENUNGGU_VERIFIKASI, 404);
    }
}
