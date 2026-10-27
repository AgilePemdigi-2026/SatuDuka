<?php

namespace App\Http\Controllers;

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Services\Ai\LayananAnalisisAi;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * Verifikasi berkas versi baca saja.
 *
 * Halaman ini hanya menampilkan data, pratinjau berkas, skor kecurigaan, dan
 * hasil bantuan AI. Tidak ada tombol menyetujui atau menolak pada tahap ini.
 */
class VerifikasiController extends Controller
{
    /**
     * Daftar pengajuan yang menunggu verifikasi, diurutkan berdasarkan prioritas.
     */
    public function daftar(Request $request, LayananAnalisisAi $layanan)
    {
        $urut = (string) $request->query('urut', 'prioritas');
        $prioritas = (string) $request->query('prioritas', 'semua');

        if (! in_array($urut, ['prioritas', 'terbaru', 'terlama'], true)) {
            $urut = 'prioritas';
        }

        if (! in_array($prioritas, ['semua', 'tinggi', 'sedang', 'rendah'], true)) {
            $prioritas = 'semua';
        }

        $semua = LaporanKematian::query()
            ->where('status_pengajuan', LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
            ->with(['analisisAi', 'berkasPendukung'])
            ->get();

        $baris = $semua->map(function (LaporanKematian $laporan) use ($layanan): array {
            return [
                'laporan' => $laporan,
                'skor' => $layanan->skor($laporan, $this->perbandinganTerakhir($laporan)),
            ];
        });

        if ($prioritas !== 'semua') {
            $baris = $baris
                ->filter(fn (array $item): bool => $item['skor']['kategori'] === $prioritas)
                ->values();
        }

        $baris = $baris->sort(function (array $kiri, array $kanan) use ($urut): int {
            return match ($urut) {
                'terbaru' => $this->bandingkanTanggal($kanan['laporan'], $kiri['laporan']),
                'terlama' => $this->bandingkanTanggal($kiri['laporan'], $kanan['laporan']),
                default => ($kanan['skor']['skor'] <=> $kiri['skor']['skor'])
                    ?: $this->bandingkanTanggal($kanan['laporan'], $kiri['laporan']),
            };
        })->values();

        $halaman = max(1, (int) $request->query('page', 1));
        $ukuran = 10;
        $total = $baris->count();
        $potongan = $baris->slice(($halaman - 1) * $ukuran, $ukuran)->values();

        $daftar = new LengthAwarePaginator(
            $potongan->pluck('laporan')->values(),
            $total,
            $ukuran,
            $halaman,
            ['path' => route('verifikasi.daftar'), 'query' => $request->query()],
        );

        return view('verifikasi.daftar', [
            'pengguna' => $request->user(),
            'daftar' => $daftar,
            'skorPerLaporan' => $potongan->mapWithKeys(
                fn (array $item): array => [$item['laporan']->id_laporan => $item['skor']],
            ),
            'urut' => $urut,
            'prioritas' => $prioritas,
        ]);
    }

    /**
     * Detail satu pengajuan beserta skor, daftar berkas, dan panel AI.
     */
    public function detail(Request $request, LaporanKematian $laporan, LayananAnalisisAi $layanan)
    {
        $this->pastikanMenungguVerifikasi($laporan);

        $laporan->load(['berkasPendukung', 'analisisAi']);
        $perbandingan = $this->perbandinganTerakhir($laporan);
        $ringkasan = $this->ringkasanTerakhir($laporan);

        return view('verifikasi.detail', [
            'pengguna' => $request->user(),
            'laporan' => $laporan,
            'analisis' => session('analisis_ai'),
            'skor' => $layanan->skor($laporan, $perbandingan),
            'ringkasan' => $ringkasan?->hasil,
            'ringkasanBerhasil' => $ringkasan?->status === AnalisisAi::STATUS_BERHASIL,
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

        $layanan->skor($laporan, $hasil['perbandingan']);

        return redirect()
            ->route('verifikasi.detail', $laporan)
            ->with('analisis_ai', $hasil)
            ->with('sukses', 'Analisis AI selesai. Hasil bantuan AI, hanya saran.');
    }

    /**
     * Membuat ringkasan ketidaksesuaian dengan bantuan AI.
     *
     * Bila belum ada hasil perbandingan, dokumen dibaca terlebih dahulu.
     * Bila AI gagal, skor aturan tetap tersedia dan kegagalan dicatat.
     */
    public function ringkasanAi(Request $request, LaporanKematian $laporan, LayananAnalisisAi $layanan)
    {
        $this->pastikanMenungguVerifikasi($laporan);

        $berkas = $this->suratKeterangan($laporan);

        if ($berkas === null) {
            return redirect()
                ->route('verifikasi.detail', $laporan)
                ->with('galat', 'Surat Keterangan Kematian tidak ditemukan.');
        }

        $laporan->loadMissing('analisisAi');
        $perbandingan = $this->perbandinganTerakhir($laporan);

        if ($perbandingan === null) {
            $analisis = $layanan->periksa($berkas, $request->user(), 'verifikator_ringkasan');
            $perbandingan = $analisis['perbandingan'];

            if (! $analisis['berhasil'] || ! is_array($perbandingan)) {
                $layanan->skor($laporan, null);

                return redirect()
                    ->route('verifikasi.detail', $laporan)
                    ->with('galat', $analisis['pesan'] ?? 'Analisis AI tidak tersedia, lanjutkan secara manual');
            }
        }

        $hasil = $layanan->ringkas($laporan, $perbandingan, $request->user(), 'verifikator_ringkasan');
        $layanan->skor($laporan, $perbandingan);

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('verifikasi.detail', $laporan)
                ->with('galat', $hasil['pesan']);
        }

        return redirect()
            ->route('verifikasi.detail', $laporan)
            ->with('sukses', 'Ringkasan AI dibuat. Hasil bantuan AI, hanya saran.');
    }

    private function pastikanMenungguVerifikasi(LaporanKematian $laporan): void
    {
        abort_unless($laporan->status_pengajuan === LaporanKematian::STATUS_MENUNGGU_VERIFIKASI, 404);
    }

    private function suratKeterangan(LaporanKematian $laporan): ?BerkasPendukung
    {
        return $laporan->berkasPendukung()
            ->where('jenis_dokumen', BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN)
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function perbandinganTerakhir(LaporanKematian $laporan): ?array
    {
        $laporan->loadMissing('analisisAi');

        $analisis = $laporan->analisisAi
            ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
            ->sortByDesc('id_analisis')
            ->first();

        return $analisis?->hasil;
    }

    private function ringkasanTerakhir(LaporanKematian $laporan): ?AnalisisAi
    {
        $laporan->loadMissing('analisisAi');

        return $laporan->analisisAi
            ->where('jenis', AnalisisAi::JENIS_RINGKASAN)
            ->sortByDesc('id_analisis')
            ->first();
    }

    private function bandingkanTanggal(LaporanKematian $kiri, LaporanKematian $kanan): int
    {
        $waktuKiri = $kiri->tanggal_pengajuan?->getTimestamp() ?? 0;
        $waktuKanan = $kanan->tanggal_pengajuan?->getTimestamp() ?? 0;

        return $waktuKiri <=> $waktuKanan;
    }
}
