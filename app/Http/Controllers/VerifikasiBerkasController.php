<?php

namespace App\Http\Controllers;

use App\Models\AnomaliDokumen;
use App\Models\BerkasPendukung;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Services\LayananAudit;
use App\Support\DataSIAK;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiBerkasController extends Controller
{
    /**
     * Daftar antrean yang menunggu pemeriksaan.
     */
    public function index(Request $request): View
    {
        $query = LaporanKematian::query()
            ->with('pemohon')
            ->withCount('berkasPendukung')
            ->where(
                'status_pengajuan',
                LaporanKematian::STATUS_MENUNGGU_VERIFIKASI
            )
            ->orderBy('tanggal_pengajuan');

        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            // Escape karakter khusus SQL (% dan _)
            $escapedCari = addcslashes($cari, '%_');

            $query->where(function ($subquery) use ($escapedCari) {
                $subquery
                    ->where('no_pengajuan', 'ilike', "%{$escapedCari}%")
                    ->orWhere('nama_almarhum', 'ilike', "%{$escapedCari}%")
                    ->orWhere('nik_almarhum', 'ilike', "%{$escapedCari}%");
            });
        }

        $pengajuan = $query->paginate(10)->withQueryString();

        return view('verifikasi.index', compact('pengajuan'));
    }

    /**
     * Detail berkas dan pencocokan data SIAK.
     */
    public function show(LaporanKematian $laporan): View
    {
        abort_unless(
            $laporan->status_pengajuan === LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            404
        );

        $laporan->load([
            'pemohon',
            'berkasPendukung',
            'hasilVerifikasi.verifikator',
        ]);

        $dataSIAK = DataSIAK::cariNik($laporan->nik_almarhum);
        $dokumenWajib = BerkasPendukung::DAFTAR_JENIS;
        
        // Gunakan helper method dokumenKurang() agar konsisten
        $dokumenKurang = $this->dokumenKurang($laporan);

        $hasilPencocokan = [
            'ditemukan_di_siak' => $dataSIAK !== null,
            'nik_sesuai' => $dataSIAK !== null
                && ($dataSIAK['nik'] ?? null) === $laporan->nik_almarhum,
            'nama_sesuai' => $dataSIAK !== null
                && mb_strtolower(trim($dataSIAK['nama_lengkap'] ?? ''))
                    === mb_strtolower(trim($laporan->nama_almarhum)),
            'dokumen_kurang' => $dokumenKurang,
        ];

        return view('verifikasi.show', compact(
            'laporan',
            'dataSIAK',
            'hasilPencocokan',
            'dokumenWajib'
        ));
    }

    /**
     * Menyetujui pengajuan yang lolos pemeriksaan.
     */
    public function setujui(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $this->pastikanMenunggu($laporan);

        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $laporan->load('berkasPendukung');

        $dokumenKurang = $this->dokumenKurang($laporan);

        if ($dokumenKurang !== []) {
            return back()
                ->withErrors([
                    'dokumen' => 'Pengajuan belum dapat disetujui. Dokumen kurang: '
                        . implode(', ', $dokumenKurang),
                ])
                ->withInput();
        }

        $dataSIAK = DataSIAK::cariNik($laporan->nik_almarhum);

        if ($dataSIAK === null) {
            return back()->withErrors([
                'siak' => 'Data SIAK tidak ditemukan. Lakukan pemeriksaan manual sebelum mengambil keputusan.',
            ])->withInput();
        }

        $nikSesuai = ($dataSIAK['nik'] ?? null) === $laporan->nik_almarhum;
        $namaSesuai = mb_strtolower(trim($dataSIAK['nama_lengkap'] ?? ''))
            === mb_strtolower(trim($laporan->nama_almarhum));

        if (! $nikSesuai || ! $namaSesuai) {
            return back()->withErrors([
                'siak' => 'Data identitas tidak cocok. Tandai sebagai anomali atau lakukan pemeriksaan sesuai prosedur.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $laporan, $data) {
            // Kunci record laporan untuk menghindari race condition
            $laporanTerkunci = LaporanKematian::where('id_laporan', $laporan->id_laporan)
                ->lockForUpdate()
                ->first();

            $this->pastikanMenunggu($laporanTerkunci);

            $laporanTerkunci->update([
                'status_pengajuan' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
                'catatan_revisi' => null,
            ]);

            HasilVerifikasi::create([
                'id_laporan' => $laporanTerkunci->id_laporan,
                'id_pengguna' => $request->user()->id_pengguna,
                'status_verifikasi' => HasilVerifikasi::STATUS_DITERIMA,
                'catatan' => $data['catatan'] ?? 'Berkas lengkap dan identitas sesuai.',
                'tanggal_verifikasi' => now(),
            ]);

            LayananAudit::catat(
                aksi: 'menyetujui_berkas_pengajuan',
                objek: 'laporan_kematian',
                idObjek: $laporanTerkunci->id_laporan,
                detail: ['no_pengajuan' => $laporanTerkunci->no_pengajuan],
                pengguna: $request->user(),
                hasil: 'Sukses',
            );
        });

        return redirect()
            ->route('verifikasi.index')
            ->with('sukses', 'Berkas berhasil disetujui dan masuk tahap menunggu TTE.');
    }

    /**
     * Meminta pemohon memperbaiki dokumen.
     */
    public function mintaRevisi(
        Request $request,
        LaporanKematian $laporan
    ): RedirectResponse {
        $this->pastikanMenunggu($laporan);

        $data = $request->validate([
            'catatan' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $laporan, $data) {
            $laporanTerkunci = LaporanKematian::where('id_laporan', $laporan->id_laporan)
                ->lockForUpdate()
                ->first();

            $this->pastikanMenunggu($laporanTerkunci);

            $laporanTerkunci->update([
                'status_pengajuan' => LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
                'catatan_revisi' => $data['catatan'],
            ]);

            HasilVerifikasi::create([
                'id_laporan' => $laporanTerkunci->id_laporan,
                'id_pengguna' => $request->user()->id_pengguna,
                'status_verifikasi' => HasilVerifikasi::STATUS_PERLU_REVISI,
                'catatan' => $data['catatan'],
                'tanggal_verifikasi' => now(),
            ]);

            LayananAudit::catat(
                aksi: 'meminta_revisi_berkas',
                objek: 'laporan_kematian',
                idObjek: $laporanTerkunci->id_laporan,
                detail: [
                    'no_pengajuan' => $laporanTerkunci->no_pengajuan,
                    'catatan' => $data['catatan'],
                ],
                pengguna: $request->user(),
                hasil: 'Sukses',
            );
        });

        return redirect()
            ->route('verifikasi.index')
            ->with('sukses', 'Catatan revisi berhasil disimpan.');
    }

    /**
     * Memindahkan pengajuan ke antrean rekonsiliasi anomali.
     */
    public function tandaiAnomali(
        Request $request,
        LaporanKematian $laporan
    ): RedirectResponse {
        $this->pastikanMenunggu($laporan);

        $data = $request->validate([
            'jenis_anomali' => [
                'required',
                'string',
                Rule::in([
                    AnomaliDokumen::JENIS_NIK_GANDA,
                    AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
                    AnomaliDokumen::JENIS_TANGGAL_KEMATIAN,
                    AnomaliDokumen::JENIS_TEMPAT_KEMATIAN,
                    AnomaliDokumen::JENIS_DOKUMEN_TIDAK_TERBACA,
                ]),
            ],
            'deskripsi' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $laporan, $data) {
            $laporanTerkunci = LaporanKematian::where('id_laporan', $laporan->id_laporan)
                ->lockForUpdate()
                ->first();

            $this->pastikanMenunggu($laporanTerkunci);

            $laporanTerkunci->update([
                'status_pengajuan' => LaporanKematian::STATUS_DALAM_REKONSILIASI,
            ]);

            $hasil = HasilVerifikasi::create([
                'id_laporan' => $laporanTerkunci->id_laporan,
                'id_pengguna' => $request->user()->id_pengguna,
                'status_verifikasi' => HasilVerifikasi::STATUS_ANOMALI,
                'catatan' => $data['deskripsi'],
                'tanggal_verifikasi' => now(),
            ]);

            AnomaliDokumen::create([
                'id_verifikasi' => $hasil->id_verifikasi,
                'jenis_anomali' => $data['jenis_anomali'],
                'deskripsi' => $data['deskripsi'],
                'status_rekonsiliasi' => AnomaliDokumen::STATUS_TERBUKA,
            ]);

            LayananAudit::catat(
                aksi: 'menandai_anomali_berkas',
                objek: 'laporan_kematian',
                idObjek: $laporanTerkunci->id_laporan,
                detail: [
                    'no_pengajuan' => $laporanTerkunci->no_pengajuan,
                    'jenis_anomali' => $data['jenis_anomali'],
                ],
                pengguna: $request->user(),
                hasil: 'Sukses',
            );
        });

        return redirect()
            ->route('verifikasi.index')
            ->with('sukses', 'Berkas dialihkan ke proses rekonsiliasi anomali.');
    }

    /**
     * Jenis berkas yang boleh ditampilkan langsung di browser.
     */
    private const MIME_PRATINJAU = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    /**
     * Menampilkan berkas langsung di browser (tanpa mengunduh).
     */
    public function pratinjau(
        LaporanKematian $laporan,
        BerkasPendukung $berkas
    ): StreamedResponse {
        $path = $this->jalurBerkas($laporan, $berkas);

        $ekstensi = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::MIME_PRATINJAU[$ekstensi] ?? null;

        abort_if(
            $mime === null,
            415,
            'Jenis berkas ini tidak bisa dipratinjau. Silakan unduh.'
        );

        return Storage::disk('local')->response($path, $berkas->nama_file, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' .
                addcslashes(basename($berkas->nama_file), '"\\') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Mengunduh berkas.
     */
    public function unduh(
        LaporanKematian $laporan,
        BerkasPendukung $berkas
    ): StreamedResponse {
        $path = $this->jalurBerkas($laporan, $berkas);

        return Storage::disk('local')->download($path, $berkas->nama_file, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Memastikan berkas milik laporan ini dan benar-benar ada di penyimpanan.
     */
    private function jalurBerkas(
        LaporanKematian $laporan,
        BerkasPendukung $berkas
    ): string {
        abort_unless(
            $laporan->status_pengajuan === LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            404
        );
        abort_unless(
            (int) $berkas->id_laporan === (int) $laporan->id_laporan,
            404
        );
        abort_unless(
            $berkas->status_unggahan === BerkasPendukung::STATUS_BERHASIL,
            404
        );
        abort_unless(
            $berkas->path_file !== '' &&
                Storage::disk('local')->exists($berkas->path_file),
            404
        );

        return $berkas->path_file;
    }

    private function pastikanMenunggu(?LaporanKematian $laporan): void
    {
        abort_unless(
            $laporan && $laporan->status_pengajuan === LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            409,
            'Pengajuan ini sudah tidak berada dalam antrean verifikasi.'
        );
    }

    /**
     * @return list<string>
     */
    private function dokumenKurang(LaporanKematian $laporan): array
    {
        $tersedia = $laporan->berkasPendukung
            ->where('status_unggahan', BerkasPendukung::STATUS_BERHASIL)
            ->pluck('jenis_dokumen')
            ->unique()
            ->all();

        return array_values(array_diff(
            BerkasPendukung::DAFTAR_JENIS,
            $tersedia
        ));
    }
}