<?php

namespace App\Http\Controllers;

use App\Models\AnomaliDokumen;
use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use App\Services\LayananAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pemantauan status pengajuan oleh Pemohon (use case 5.2.5).
 *
 * Pemohon melihat daftar pengajuan yang sudah dikirim, mencari berdasarkan
 * nomor pengajuan, menelusuri lini masa lima tahap, dan mengunduh akta
 * kematian setelah pengajuan selesai.
 */
class StatusPengajuanController extends Controller
{
    /**
     * Pesan saat kolom pencarian dikirim kosong.
     */
    public const PESAN_NOMOR_KOSONG = 'Harap isi nomor pengajuan';

    /**
     * Pesan saat nomor tidak ditemukan atau bukan milik pemohon.
     */
    public const PESAN_TIDAK_DITEMUKAN = 'Data tidak ditemukan';

    /**
     * Menampilkan daftar pengajuan milik pemohon dan menangani pencarian nomor.
     */
    public function daftar(Request $request)
    {
        $nomor = trim((string) $request->query('nomor', ''));
        $galatNomor = null;
        $galatCari = null;

        if ($request->has('nomor')) {
            if ($nomor === '') {
                $galatNomor = self::PESAN_NOMOR_KOSONG;
            } else {
                $ditemukan = $this->laporanPemohon($request)
                    ->where('no_pengajuan', $nomor)
                    ->where('status_pengajuan', '!=', LaporanKematian::STATUS_DRAF)
                    ->first();

                if ($ditemukan !== null) {
                    return redirect()->route('pengajuan.detail', $ditemukan);
                }

                $galatCari = self::PESAN_TIDAK_DITEMUKAN;
            }
        }

        $daftar = $this->laporanPemohon($request)
            ->where('status_pengajuan', '!=', LaporanKematian::STATUS_DRAF)
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id_laporan')
            ->paginate(10)
            ->withQueryString();

        return view('pengajuan.status', [
            'pengguna' => $request->user(),
            'daftar' => $daftar,
            'nomor' => $nomor,
            'galatNomor' => $galatNomor,
            'galatCari' => $galatCari,
        ]);
    }

    /**
     * Menampilkan ringkasan pengajuan beserta lini masa lima tahap.
     */
    public function detail(Request $request, LaporanKematian $laporan)
    {
        $laporan = $this->laporanMilik($request, $laporan);

        abort_if($laporan->adalahDraf(), 404);

        return view('pengajuan.detail', [
            'pengguna' => $request->user(),
            'laporan' => $laporan,
            'liniMasa' => $this->liniMasa($laporan),
        ]);
    }

    /**
     * Mengunduh akta kematian yang sudah diterbitkan.
     */
    public function unduh(Request $request, LaporanKematian $laporan)
    {
        $laporan = $this->laporanMilik($request, $laporan);

        abort_unless($laporan->selesai(), 403, 'Dokumen belum dapat diunduh.');

        $dokumen = $laporan->dokumenResmi;

        // Dokumen sudah ditandatangani tetapi berkas PDF belum diterbitkan
        // (Fase TTE belum membuat PDF): arahkan kembali dengan pesan jelas.
        if ($dokumen !== null && blank($dokumen->file_dokumen)) {
            return redirect()
                ->route('pengajuan.detail', $laporan)
                ->with('galat', 'Dokumen resmi sedang disiapkan.');
        }

        abort_unless(
            $dokumen !== null && $dokumen->file_dokumen !== null && $dokumen->file_dokumen !== '',
            403,
            'Dokumen belum tersedia.',
        );

        if (! Storage::disk('local')->exists($dokumen->file_dokumen)) {
            return redirect()
                ->route('pengajuan.detail', $laporan)
                ->with('galat', 'Dokumen resmi sedang disiapkan.');
        }

        LayananAudit::catat(
            aksi: 'mengunduh_dokumen',
            objek: 'dokumen_resmi',
            idObjek: $dokumen->id_dokumen,
            detail: ['no_pengajuan' => $laporan->no_pengajuan],
            pengguna: $request->user(),
            hasil: 'Sukses',
        );

        $nomor = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) ($dokumen->no_dokumen ?? '')) ?? '';
        $namaBerkas = ($nomor === '' ? 'Akta_Kematian' : 'Akta_Kematian_'.$nomor).'.pdf';

        return Storage::disk('local')->download(
            $dokumen->file_dokumen,
            $namaBerkas,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$namaBerkas.'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store, private',
            ],
        );
    }

    /**
     * Lini masa lima tahap proses pengajuan.
     *
     * Tanggal diambil dari kolom yang sudah tersedia; tahap tanpa kolom tanggal
     * ditampilkan tanpa tanggal.
     *
     * @return list<array{judul: string, tanggal: mixed, selesai: bool}>
     */
    private function liniMasa(LaporanKematian $laporan): array
    {
        $tanggalVerifikasi = $laporan->hasilVerifikasi()
            ->whereNotNull('tanggal_verifikasi')
            ->orderBy('tanggal_verifikasi')
            ->first()
            ?->tanggal_verifikasi;

        $tanggalRekonsiliasi = AnomaliDokumen::query()
            ->whereIn('id_verifikasi', $laporan->hasilVerifikasi()->select('id_verifikasi'))
            ->whereNotNull('tanggal_rekonsiliasi')
            ->orderByDesc('tanggal_rekonsiliasi')
            ->first()
            ?->tanggal_rekonsiliasi;

        $dokumen = $laporan->dokumenResmi;

        $tahapData = 1;
        $tahapData = $tanggalVerifikasi !== null ? 2 : $tahapData;
        $tahapData = $tanggalRekonsiliasi !== null ? 3 : $tahapData;
        $tahapData = $dokumen?->status_ttd === DokumenResmi::STATUS_SELESAI ? 4 : $tahapData;
        $tahapData = $dokumen?->tanggal_terbit !== null ? 5 : $tahapData;

        $tahapStatus = match ($laporan->status_pengajuan) {
            LaporanKematian::STATUS_MENUNGGU_VERIFIKASI => 1,
            LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
            LaporanKematian::STATUS_DIKEMBALIKAN,
            LaporanKematian::STATUS_DALAM_REKONSILIASI,
            LaporanKematian::STATUS_MENUNGGU_PERBAIKAN => 2,
            LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE => 4,
            LaporanKematian::STATUS_SELESAI => 5,
            default => 0,
        };

        $tahapSelesai = max($tahapData, $tahapStatus);

        $liniMasa = [
            ['judul' => 'Pengajuan Diterima', 'tanggal' => $laporan->tanggal_pengajuan],
            ['judul' => 'Verifikasi Dokumen', 'tanggal' => $tanggalVerifikasi],
            ['judul' => 'Peninjauan Verifikator', 'tanggal' => $tanggalRekonsiliasi],
            ['judul' => 'Persetujuan dan Tanda Tangan Elektronik', 'tanggal' => null],
            ['judul' => 'Dokumen Terbit', 'tanggal' => $dokumen?->tanggal_terbit],
        ];

        foreach ($liniMasa as $indeks => $tahap) {
            $liniMasa[$indeks]['selesai'] = ($indeks + 1) <= $tahapSelesai;
        }

        return $liniMasa;
    }

    /**
     * Query pengajuan milik pemohon yang sedang masuk.
     */
    private function laporanPemohon(Request $request): Builder
    {
        return LaporanKematian::query()
            ->where('id_pengguna', $request->user()->id_pengguna);
    }

    /**
     * Memastikan laporan milik pemohon yang sedang masuk.
     */
    private function laporanMilik(Request $request, LaporanKematian $laporan): LaporanKematian
    {
        abort_unless((int) $laporan->id_pengguna === (int) $request->user()->id_pengguna, 404);

        return $laporan;
    }
}
