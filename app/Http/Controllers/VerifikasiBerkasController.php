<?php

namespace App\Http\Controllers;

use App\Models\AnalisisAi;
use App\Models\AnomaliDokumen;
use App\Models\BerkasPendukung;
use App\Models\DokumenResmi;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Services\Ai\LayananAnalisisAi;
use App\Services\LayananAudit;
use App\Services\Verifikasi\HasilAnalisis;
use App\Services\Verifikasi\MesinRekonsiliasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerifikasiBerkasController extends Controller
{
    public const TINDAKAN_SETUJUI = 'setujui';
    public const TINDAKAN_PERBAIKAN = 'minta_perbaikan';
    public const TINDAKAN_REVISI = 'tolak_revisi';
    public const TINDAKAN_ANOMALI = 'tandai_anomali';
    public const TINDAKAN_FATAL = 'tolak_fatal';

    public function __construct(
        private readonly LayananAnalisisAi $layananAi,
        private readonly MesinRekonsiliasi $mesin,
    ) {}

    /**
     * Daftar seluruh permohonan untuk pemantauan dan pemeriksaan.
     */
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:semua,disetujui,menunggu,rekonsiliasi,selesai'],
        ]);

        $statusFilter = $filter['status'] ?? 'semua';

        $query = LaporanKematian::query()
            ->with('pemohon')
            ->where('status_pengajuan', '!=', LaporanKematian::STATUS_DRAF)
            ->orderByRaw('CASE WHEN tanggal_pengajuan IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('dibuat_pada');

        $statusPengajuan = match ($statusFilter) {
            'disetujui' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
            'menunggu' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            'rekonsiliasi' => LaporanKematian::STATUS_DALAM_REKONSILIASI,
            'selesai' => LaporanKematian::STATUS_SELESAI,
            default => null,
        };

        if ($statusPengajuan !== null) {
            $query->where('status_pengajuan', $statusPengajuan);
        }

        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            $polaCari = '%'.addcslashes($cari, '\\%_').'%';

            $driver = DB::connection()->getDriverName();
            $operatorPencarian = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
            $klausaEscape = $driver === 'mysql'
                ? "ESCAPE '\\\\'"
                : "ESCAPE '\\'";

            $query->where(function ($subquery) use (
                $polaCari,
                $operatorPencarian,
                $klausaEscape
            ) {
                $subquery
                    ->whereRaw(
                        "no_pengajuan {$operatorPencarian} ? {$klausaEscape}",
                        [$polaCari]
                    )
                    ->orWhereRaw(
                        "nama_almarhum {$operatorPencarian} ? {$klausaEscape}",
                        [$polaCari]
                    )
                    ->orWhereRaw(
                        "nik_almarhum {$operatorPencarian} ? {$klausaEscape}",
                        [$polaCari]
                    );
            });
        }

        $pengajuan = $query->paginate(10)->withQueryString();

        $jumlahPerStatus = LaporanKematian::query()
            ->where('status_pengajuan', '!=', LaporanKematian::STATUS_DRAF)
            ->selectRaw('status_pengajuan, COUNT(*) as jumlah')
            ->groupBy('status_pengajuan')
            ->pluck('jumlah', 'status_pengajuan');

        $ringkasan = [
            'total' => $jumlahPerStatus->sum(),
            'menunggu' => (int) $jumlahPerStatus->get(
                LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                0
            ),
            'rekonsiliasi' => (int) $jumlahPerStatus->get(
                LaporanKematian::STATUS_DALAM_REKONSILIASI,
                0
            ),
            'selesai' => (int) $jumlahPerStatus->get(
                LaporanKematian::STATUS_SELESAI,
                0
            ),
        ];

        return view(
            'verifikasi.index',
            compact('pengajuan', 'ringkasan', 'statusFilter')
        );
    }

    /**
     * Menampilkan detail berkas untuk diperiksa verifikator.
     */
    public function show(LaporanKematian $laporan): View
    {
        abort_unless(
            in_array($laporan->status_pengajuan, [
                LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                LaporanKematian::STATUS_DALAM_REKONSILIASI,
                LaporanKematian::STATUS_DIKEMBALIKAN,
            ], true),
            404
        );

        $laporan->load([
            'pemohon',
            'berkasPendukung',
            'hasilVerifikasi.verifikator',
            'dokumenResmi',
        ]);

        // Ambil hasil analisis AI terakhir yang berhasil.
        $analisisKesesuaian = $laporan->analisisAi()
            ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
            ->where('status', AnalisisAi::STATUS_BERHASIL)
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id_analisis')
            ->first();

        $dokumenWajib = BerkasPendukung::DAFTAR_JENIS;
        $analisisAturan = $this->mesin->analisis($laporan);
        $tindakanDisarankan = $this->mesin->tindakanDisarankan($analisisAturan);
        $bannerManual = $analisisAturan->layananTidakTersedia;

        return view('verifikasi.show', compact(
            'laporan',
            'dokumenWajib',
            'analisisKesesuaian',
            'analisisAturan',
            'tindakanDisarankan',
            'bannerManual',
        ));
    }

    /** Menyetujui berkas. Mesin hanya memberikan rekomendasi; verifikator memutuskan. */
    public function setujui(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_SETUJUI, [
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], 'Berkas berhasil disetujui dan masuk tahap menunggu TTE.');
    }

    /** Meminta pemohon memperbaiki berkas. */
    public function mintaPerbaikan(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_PERBAIKAN, [
            'catatan' => ['required', 'string', 'min:5', 'max:2000'],
        ], 'Permintaan perbaikan berkas berhasil dikirim.');
    }

    /** Meminta revisi berkas. */
    public function mintaRevisi(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_REVISI, [
            'catatan' => ['required', 'string', 'min:5', 'max:2000'],
        ], 'Catatan revisi berhasil disimpan.');
    }

    /** Menandai anomali untuk rekonsiliasi. */
    public function tandaiAnomali(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_ANOMALI, [
            'jenis_anomali' => [
                'required', 'string',
                Rule::in([
                    AnomaliDokumen::JENIS_NIK_GANDA,
                    AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
                    AnomaliDokumen::JENIS_TANGGAL_KEMATIAN,
                    AnomaliDokumen::JENIS_TEMPAT_KEMATIAN,
                    AnomaliDokumen::JENIS_DOKUMEN_TIDAK_TERBACA,
                ]),
            ],
            'deskripsi' => ['required', 'string', 'min:5', 'max:2000'],
        ], 'Berkas dialihkan ke proses rekonsiliasi anomali.');
    }

    /** Menolak pengajuan dengan anomali fatal. */
    public function tolakFatal(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_FATAL, [
            'catatan' => ['required', 'string', 'min:5', 'max:2000'],
        ], 'Pengajuan ditolak karena anomali data fatal.');
    }

    /**
     * Membaca Surat Keterangan Kematian melalui layanan AI.
     * Status pengajuan tidak diubah.
     */
    public function analisis(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $this->pastikanUntukTampil($laporan);

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
            return redirect()->route('verifikasi.show', $laporan)
                ->with('galat', 'Surat Keterangan Kematian tidak ditemukan.');
        }

        $hasil = $this->layananAi->periksa($berkas, $request->user(), 'verifikator_detail');

        if (! $hasil['berhasil']) {
            return redirect()->route('verifikasi.show', $laporan)
                ->with('galat', $hasil['pesan']);
        }

        return redirect()->route('verifikasi.show', $laporan)
            ->with('analisis_ai', $hasil)
            ->with('sukses', 'Analisis AI selesai. Hasil bantuan AI, hanya saran.');
    }

    /**
     * Merangkum hasil pemeriksaan AI terbaru, tanpa mengubah status.
     */
    public function ringkasanAi(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $this->pastikanUntukTampil($laporan);
        $berkas = $this->suratKeterangan($laporan);

        if ($berkas === null) {
            return redirect()->route('verifikasi.show', $laporan)
                ->with('galat', 'Surat Keterangan Kematian tidak ditemukan.');
        }

        $perbandingan = $this->perbandinganTerakhir($laporan);

        if ($perbandingan === null) {
            $analisis = $this->layananAi->periksa($berkas, $request->user(), 'verifikator_ringkasan');
            $perbandingan = $analisis['perbandingan'] ?? null;

            if (! $analisis['berhasil'] || ! is_array($perbandingan)) {
                return redirect()->route('verifikasi.show', $laporan)
                    ->with('galat', $analisis['pesan'] ?? 'Analisis AI tidak tersedia, lanjutkan secara manual');
            }
        }

        $hasil = $this->layananAi->ringkas(
            $laporan,
            $perbandingan,
            $request->user(),
            'verifikator_ringkasan'
        );

        if (! $hasil['berhasil']) {
            return redirect()->route('verifikasi.show', $laporan)
                ->with('galat', $hasil['pesan']);
        }

        return redirect()->route('verifikasi.show', $laporan)
            ->with('sukses', 'Ringkasan AI dibuat. Hasil bantuan AI, hanya saran.');
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
     * Menampilkan berkas langsung di browser.
     */
    public function pratinjau(
        LaporanKematian $laporan,
        BerkasPendukung $berkas
    ): StreamedResponse {
        $path = $this->jalurBerkas($laporan, $berkas);

        $ekstensi = strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        );

        $mime = self::MIME_PRATINJAU[$ekstensi] ?? null;

        abort_if(
            $mime === null,
            415,
            'Jenis berkas ini tidak bisa dipratinjau. Silakan unduh.'
        );

        $respons = Storage::disk('local')->response(
            $path,
            $berkas->nama_file,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'
                    . addcslashes(
                        basename($berkas->nama_file),
                        '"\\\\'
                    )
                    . '"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store, private',
            ]
        );
        $respons->headers->set('Cache-Control', 'no-store, private');

        return $respons;
    }

    /**
     * Mengunduh berkas.
     */
    public function unduh(
        LaporanKematian $laporan,
        BerkasPendukung $berkas
    ): StreamedResponse {
        $path = $this->jalurBerkas($laporan, $berkas);

        $respons = Storage::disk('local')->download($path, $berkas->nama_file);
        $respons->headers->set('X-Content-Type-Options', 'nosniff');
        $respons->headers->set('Cache-Control', 'no-store, private');

        return $respons;
    }

    /**
     * Memastikan berkas milik laporan dan tersedia di penyimpanan.
     */
    /**
     * Keputusan atomik dan terkunci; tidak melibatkan SIAK.
     * @param array<string, mixed> $aturanValidasi
     */
    private function putuskan(
        Request $request,
        LaporanKematian $laporan,
        string $tindakan,
        array $aturanValidasi,
        string $pesanSukses,
    ): RedirectResponse {
        $data = $request->validate(array_merge($aturanValidasi, [
            'alasan_override' => ['nullable', 'string', 'min:5', 'max:2000'],
        ]));

        DB::transaction(function () use ($request, $laporan, $tindakan, $data) {
            $laporanTerkunci = LaporanKematian::query()
                ->whereKey($laporan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->pastikanBisaDiverifikasi($laporanTerkunci);

            $analisis = $this->mesin->analisis($laporanTerkunci, false);
            $disarankan = $this->mesin->tindakanDisarankan($analisis);
            $alasanOverride = trim((string) ($data['alasan_override'] ?? ''));

            if ($tindakan !== $disarankan && $alasanOverride === '') {
                throw ValidationException::withMessages([
                    'alasan_override' => 'Tindakan berbeda dari rekomendasi mesin. Isi alasan override.',
                ]);
            }

            $this->terapkanKeputusan(
                $request,
                $laporanTerkunci,
                $tindakan,
                $data,
                $analisis,
                $alasanOverride === '' ? null : $alasanOverride,
            );
        });

        return redirect()
            ->route('verifikasi.index')
            ->with('sukses', $pesanSukses);
    }

    /** @param array<string, mixed> $data */
    private function terapkanKeputusan(
        Request $request,
        LaporanKematian $laporan,
        string $tindakan,
        array $data,
        HasilAnalisis $analisis,
        ?string $alasanOverride,
    ): void {
        $catatan = trim((string) ($data['catatan'] ?? $data['deskripsi'] ?? ''));

        $pemetaan = [
            self::TINDAKAN_SETUJUI => [
                LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
                HasilVerifikasi::STATUS_DITERIMA,
            ],
            self::TINDAKAN_PERBAIKAN => [
                LaporanKematian::STATUS_MENUNGGU_PERBAIKAN,
                HasilVerifikasi::STATUS_PERLU_REVISI,
            ],
            self::TINDAKAN_REVISI => [
                LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
                HasilVerifikasi::STATUS_PERLU_REVISI,
            ],
            self::TINDAKAN_ANOMALI => [
                LaporanKematian::STATUS_DALAM_REKONSILIASI,
                HasilVerifikasi::STATUS_ANOMALI,
            ],
            self::TINDAKAN_FATAL => [
                LaporanKematian::STATUS_DITOLAK_ANOMALI_FATAL,
                HasilVerifikasi::STATUS_DITOLAK,
            ],
        ];

        abort_unless(isset($pemetaan[$tindakan]), 422, 'Tindakan verifikasi tidak dikenal.');
        [$statusBaru, $statusVerifikasi] = $pemetaan[$tindakan];

        $perubahan = ['status_pengajuan' => $statusBaru];
        if ($tindakan === self::TINDAKAN_SETUJUI) {
            $perubahan['catatan_revisi'] = null;
        } elseif ($catatan !== '') {
            $perubahan['catatan_revisi'] = $catatan;
        }

        $laporan->update($perubahan);

        if ($tindakan === self::TINDAKAN_SETUJUI) {
            $dokumen = DokumenResmi::query()->firstOrCreate(
                ['id_laporan' => $laporan->id_laporan],
                ['status_ttd' => DokumenResmi::STATUS_MENUNGGU],
            );
            $dokumen->update(['status_ttd' => DokumenResmi::STATUS_MENUNGGU]);
        }

        $hasil = HasilVerifikasi::create([
            'id_laporan' => $laporan->id_laporan,
            'id_pengguna' => $request->user()->id_pengguna,
            'status_verifikasi' => $statusVerifikasi,
            'catatan' => $catatan === '' ? null : $catatan,
            'tanggal_verifikasi' => now(),
        ]);

        if ($tindakan === self::TINDAKAN_ANOMALI) {
            AnomaliDokumen::create([
                'id_verifikasi' => $hasil->id_verifikasi,
                'jenis_anomali' => $data['jenis_anomali'],
                'deskripsi' => $data['deskripsi'],
                'status_rekonsiliasi' => AnomaliDokumen::STATUS_TERBUKA,
            ]);
        }

        $this->mesin->simpanKeputusan($hasil, $analisis, $alasanOverride);

        LayananAudit::catat(
            aksi: $this->namaAksiAudit($tindakan),
            objek: 'laporan_kematian',
            idObjek: $laporan->id_laporan,
            detail: array_filter([
                'no_pengajuan' => $laporan->no_pengajuan,
                'tindakan' => $tindakan,
                'rekomendasi_mesin' => $this->mesin->tindakanDisarankan($analisis),
                'alasan_override' => $alasanOverride,
                'catatan' => $catatan === '' ? null : $catatan,
                'jenis_anomali' => $data['jenis_anomali'] ?? null,
            ], static fn ($nilai): bool => $nilai !== null && $nilai !== ''),
            pengguna: $request->user(),
            hasil: 'Sukses',
        );
    }

    private function namaAksiAudit(string $tindakan): string
    {
        return match ($tindakan) {
            self::TINDAKAN_SETUJUI => 'menyetujui_berkas_pengajuan',
            self::TINDAKAN_PERBAIKAN => 'meminta_perbaikan_berkas',
            self::TINDAKAN_REVISI => 'meminta_revisi_berkas',
            self::TINDAKAN_ANOMALI => 'menandai_anomali_berkas',
            self::TINDAKAN_FATAL => 'menolak_anomali_fatal',
            default => 'memverifikasi_berkas',
        };
    }

    private function jalurBerkas(
        LaporanKematian $laporan,
        BerkasPendukung $berkas
    ): string {
        abort_unless(
            in_array($laporan->status_pengajuan, [
                LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                LaporanKematian::STATUS_DALAM_REKONSILIASI,
                LaporanKematian::STATUS_DIKEMBALIKAN,
            ], true),
            404
        );

        abort_unless(
            (int) $berkas->id_laporan ===
                (int) $laporan->id_laporan,
            404
        );

        abort_unless(
            $berkas->status_unggahan ===
                BerkasPendukung::STATUS_BERHASIL,
            404
        );

        abort_unless(
            $berkas->path_file !== ''
                && Storage::disk('local')->exists(
                    $berkas->path_file
                ),
            404
        );

        return $berkas->path_file;
    }

    private function pastikanMenunggu(
        ?LaporanKematian $laporan
    ): void {
        abort_unless(
            $laporan
                && $laporan->status_pengajuan ===
                    LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            409,
            'Pengajuan ini sudah tidak berada dalam antrean verifikasi.'
        );
    }

    /**
     * Detail dan AI boleh diperiksa pada antrean aktif serta kasus yang dikembalikan.
     */
    private function pastikanUntukTampil(?LaporanKematian $laporan): void
    {
        abort_unless(
            $laporan && in_array($laporan->status_pengajuan, [
                LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                LaporanKematian::STATUS_DALAM_REKONSILIASI,
                LaporanKematian::STATUS_DIKEMBALIKAN,
            ], true),
            404,
        );
    }

    /**
     * Aksi keputusan tambahan hanya berlaku bagi laporan yang masih bisa diverifikasi.
     */
    private function pastikanBisaDiverifikasi(?LaporanKematian $laporan): void
    {
        abort_unless(
            $laporan && in_array($laporan->status_pengajuan, [
                LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                LaporanKematian::STATUS_DALAM_REKONSILIASI,
            ], true),
            409,
            'Pengajuan ini sudah tidak berada dalam antrean verifikasi.'
        );
    }

    private function suratKeterangan(LaporanKematian $laporan): ?BerkasPendukung
    {
        return $laporan->berkasPendukung()
            ->where('jenis_dokumen', BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN)
            ->first();
    }

    /** @return array<string, mixed>|null */
    private function perbandinganTerakhir(LaporanKematian $laporan): ?array
    {
        $laporan->loadMissing('analisisAi');

        $analisis = $laporan->analisisAi
            ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
            ->sortByDesc('id_analisis')
            ->first();

        return $analisis?->hasil;
    }

    /**
     * @return list<string>
     */
    private function dokumenKurang(
        LaporanKematian $laporan
    ): array {
        $tersedia = $laporan->berkasPendukung
            ->where(
                'status_unggahan',
                BerkasPendukung::STATUS_BERHASIL
            )
            ->pluck('jenis_dokumen')
            ->unique()
            ->all();

        return array_values(array_diff(
            BerkasPendukung::DAFTAR_JENIS,
            $tersedia
        ));
    }
}
