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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Alur utama verifikasi berkas oleh verifikator.
 *
 * Mesin rekonsiliasi hanya menyarankan tindakan; keputusan tetap pada
 * verifikator. Semua aksi berjalan dalam satu transaksi dengan lockForUpdate,
 * menyimpan snapshot deteksi otomatis, dan dicatat ke log_audit.
 */
class VerifikasiBerkasController extends Controller
{
    public const TINDAKAN_SETUJUI = 'setujui';

    public const TINDAKAN_PERBAIKAN = 'minta_perbaikan';

    public const TINDAKAN_REVISI = 'tolak_revisi';

    public const TINDAKAN_ANOMALI = 'tandai_anomali';

    public const TINDAKAN_FATAL = 'tolak_fatal';

    /**
     * Status pengajuan yang boleh diproses verifikator.
     *
     * @var list<string>
     */
    private const STATUS_BOLEH_DIVERIFIKASI = [
        LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        LaporanKematian::STATUS_DALAM_REKONSILIASI,
    ];

    /**
     * Jenis berkas yang boleh ditampilkan langsung di browser.
     */
    private const MIME_PRATINJAU = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    public function __construct(
        private readonly MesinRekonsiliasi $mesin,
        private readonly LayananAnalisisAi $layananAi,
    ) {}

    /**
     * Antrean verifikasi: Menunggu Verifikasi dan Dalam Rekonsiliasi Anomali.
     *
     * Urutan memakai keparahan anomali tertinggi dari mesin, lalu tanggal
     * pengajuan terlama. Tidak ada skor tambahan pada antrean.
     */
    public function index(Request $request): View
    {
        $query = LaporanKematian::query()
            ->with('pemohon')
            ->withCount('berkasPendukung')
            ->whereIn('status_pengajuan', self::STATUS_BOLEH_DIVERIFIKASI);

        if ($request->filled('cari')) {
            $cari = mb_strtolower(trim((string) $request->input('cari')));
            $escapedCari = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $cari
            );
            $pola = '%'.$escapedCari.'%';

            $query->where(function ($subquery) use ($pola) {
                $subquery
                    ->whereRaw('LOWER(no_pengajuan) LIKE ? ESCAPE ?', [$pola, '\\'])
                    ->orWhereRaw('LOWER(nama_almarhum) LIKE ? ESCAPE ?', [$pola, '\\'])
                    ->orWhereRaw('LOWER(nik_almarhum) LIKE ? ESCAPE ?', [$pola, '\\']);
            });
        }

        $urutanKeparahan = array_flip((array) config('verifikasi.urutan_keparahan', [
            'fatal',
            'tinggi',
            'sedang',
            'rendah',
            'info',
        ]));

        $baris = $query->get()
            ->map(function (LaporanKematian $laporan) use ($urutanKeparahan): LaporanKematian {
                $hasil = $this->mesin->analisis($laporan, false);
                $laporan->setAttribute('hasil_analisis', $hasil);
                $laporan->setAttribute(
                    'bobot_keparahan',
                    $hasil->anomali === []
                        ? count($urutanKeparahan)
                        : ($urutanKeparahan[$hasil->anomali[0]->keparahan] ?? count($urutanKeparahan)),
                );

                return $laporan;
            })
            ->sort(function (LaporanKematian $kiri, LaporanKematian $kanan): int {
                $bobot = (int) $kiri->getAttribute('bobot_keparahan')
                    <=> (int) $kanan->getAttribute('bobot_keparahan');

                if ($bobot !== 0) {
                    return $bobot;
                }

                return ($kiri->tanggal_pengajuan?->getTimestamp() ?? 0)
                    <=> ($kanan->tanggal_pengajuan?->getTimestamp() ?? 0);
            })
            ->values();

        $halaman = max(1, (int) $request->query('page', 1));
        $ukuran = 10;

        $pengajuan = new LengthAwarePaginator(
            $baris->slice(($halaman - 1) * $ukuran, $ukuran)->values(),
            $baris->count(),
            $ukuran,
            $halaman,
            ['path' => route('verifikasi.index'), 'query' => $request->query()],
        );

        $statistik = [
            'menunggu' => LaporanKematian::query()
                ->where('status_pengajuan', LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
                ->count(),
            'rekonsiliasi' => LaporanKematian::query()
                ->where('status_pengajuan', LaporanKematian::STATUS_DALAM_REKONSILIASI)
                ->count(),
            'selesai' => LaporanKematian::query()
                ->where('status_pengajuan', LaporanKematian::STATUS_SELESAI)
                ->count(),
        ];

        return view('verifikasi.index', compact('pengajuan', 'statistik'));
    }

    /**
     * Detail berkas, panel aturan mesin, panel AI, dan keputusan verifikator.
     */
    public function show(LaporanKematian $laporan): View
    {
        $this->pastikanUntukTampil($laporan);

        $laporan->load([
            'pemohon',
            'berkasPendukung',
            'hasilVerifikasi.verifikator',
            'analisisAi',
        ]);

        $analisis = $this->mesin->analisis($laporan);
        $kelengkapan = $this->kelengkapanDokumen($laporan);
        $ringkasan = $this->ringkasanTerakhir($laporan);

        return view('verifikasi.show', [
            'laporan' => $laporan,
            'analisisAturan' => $analisis,
            'tindakanDisarankan' => $this->mesin->tindakanDisarankan($analisis),
            'bannerManual' => $analisis->layananTidakTersedia,
            'dokumenTampil' => $kelengkapan['dokumen_tampil'],
            'jenisWajibTunggal' => $kelengkapan['jenis_wajib_tunggal'],
            'jenisBuktiKematian' => $kelengkapan['jenis_bukti_kematian'],
            'adaBuktiKematian' => $kelengkapan['ada_bukti_kematian'],
            'jumlahWajib' => $kelengkapan['jumlah_wajib'],
            'jumlahTersedia' => $kelengkapan['jumlah_tersedia'],
            'analisis' => session('analisis_ai'),
            'ringkasan' => $ringkasan?->hasil,
            'ringkasanBerhasil' => $ringkasan?->status === AnalisisAi::STATUS_BERHASIL,
        ]);
    }

    /**
     * Menyetujui pengajuan dan membuat dokumen resmi berstatus Menunggu.
     */
    public function setujui(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_SETUJUI, [
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], 'berkas berhasil disetujui dan masuk tahap menunggu TTE.');
    }

    /**
     * Meminta perbaikan berkas oleh pemohon.
     */
    public function mintaPerbaikan(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_PERBAIKAN, [
            'catatan' => ['required', 'string', 'min:5', 'max:2000'],
        ], 'permintaan perbaikan berkas berhasil dikirim.');
    }

    /**
     * Menolak pengajuan dan meminta revisi.
     */
    public function mintaRevisi(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_REVISI, [
            'catatan' => ['required', 'string', 'min:5', 'max:2000'],
        ], 'catatan revisi berhasil dikirim.');
    }

    /**
     * Memindahkan pengajuan ke antrean rekonsiliasi anomali.
     */
    public function tandaiAnomali(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_ANOMALI, [
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
        ], 'berkas dialihkan ke proses rekonsiliasi anomali.');
    }

    /**
     * Menolak permanen karena anomali data fatal.
     */
    public function tolakFatal(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        return $this->putuskan($request, $laporan, self::TINDAKAN_FATAL, [
            'catatan' => ['required', 'string', 'min:5', 'max:2000'],
        ], 'pengajuan ditolak karena anomali data fatal.');
    }

    /**
     * Membaca dokumen dengan bantuan AI. Status pengajuan tidak diubah.
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
            return redirect()
                ->route('verifikasi.show', $laporan)
                ->with('galat', 'Surat Keterangan Kematian tidak ditemukan.');
        }

        $hasil = $this->layananAi->periksa($berkas, $request->user(), 'verifikator_detail');

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('verifikasi.show', $laporan)
                ->with('galat', $hasil['pesan']);
        }

        return redirect()
            ->route('verifikasi.show', $laporan)
            ->with('analisis_ai', $hasil)
            ->with('sukses', 'Analisis AI selesai. Hasil bantuan AI, hanya saran.');
    }

    /**
     * Membuat ringkasan ketidaksesuaian dengan bantuan AI.
     */
    public function ringkasanAi(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $this->pastikanUntukTampil($laporan);

        $berkas = $this->suratKeterangan($laporan);

        if ($berkas === null) {
            return redirect()
                ->route('verifikasi.show', $laporan)
                ->with('galat', 'Surat Keterangan Kematian tidak ditemukan.');
        }

        $laporan->loadMissing('analisisAi');
        $perbandingan = $this->perbandinganTerakhir($laporan);

        if ($perbandingan === null) {
            $analisis = $this->layananAi->periksa($berkas, $request->user(), 'verifikator_ringkasan');
            $perbandingan = $analisis['perbandingan'];

            if (! $analisis['berhasil'] || ! is_array($perbandingan)) {
                return redirect()
                    ->route('verifikasi.show', $laporan)
                    ->with('galat', $analisis['pesan'] ?? 'Analisis AI tidak tersedia, lanjutkan secara manual');
            }
        }

        $hasil = $this->layananAi->ringkas($laporan, $perbandingan, $request->user(), 'verifikator_ringkasan');

        if (! $hasil['berhasil']) {
            return redirect()
                ->route('verifikasi.show', $laporan)
                ->with('galat', $hasil['pesan']);
        }

        return redirect()
            ->route('verifikasi.show', $laporan)
            ->with('sukses', 'Ringkasan AI dibuat. Hasil bantuan AI, hanya saran.');
    }

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
            'Content-Disposition' => 'inline; filename="'.
                addcslashes(basename($berkas->nama_file), '"\\').'"',
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

        $response = Storage::disk('local')->download($path, $berkas->nama_file);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    /**
     * @param  array<string, mixed>  $aturanValidasi
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

    /**
     * @param  array<string, mixed>  $data
     */
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

    /**
     * Memastikan berkas milik laporan ini dan benar-benar ada di penyimpanan.
     */
    private function jalurBerkas(
        LaporanKematian $laporan,
        BerkasPendukung $berkas
    ): string {
        $this->pastikanUntukTampil($laporan);

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

    private function pastikanUntukTampil(?LaporanKematian $laporan): void
    {
        abort_unless(
            $laporan && in_array($laporan->status_pengajuan, self::STATUS_BOLEH_DIVERIFIKASI, true),
            404,
        );
    }

    private function pastikanBisaDiverifikasi(?LaporanKematian $laporan): void
    {
        abort_unless(
            $laporan && in_array($laporan->status_pengajuan, self::STATUS_BOLEH_DIVERIFIKASI, true),
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

    /**
     * @return array{
     *     kurang: list<string>,
     *     dokumen_tampil: list<string>,
     *     jenis_wajib_tunggal: list<string>,
     *     jenis_bukti_kematian: list<string>,
     *     ada_bukti_kematian: bool,
     *     jumlah_wajib: int,
     *     jumlah_tersedia: int
     * }
     */
    private function kelengkapanDokumen(LaporanKematian $laporan): array
    {
        $terunggah = $this->jenisBerkasBerhasil($laporan);

        $terlambat = $this->terlambat($laporan);
        $jenisKasus = $laporan->jenis_kasus ?? LaporanKematian::JENIS_KASUS_NORMAL;

        $buktiKematian = array_values((array) config('verifikasi.berkas_wajib.bukti_kematian.jenis', []));
        $lainnya = array_values((array) config('verifikasi.berkas_wajib.lainnya', []));
        $keterlambatan = $terlambat
            ? array_values((array) config('verifikasi.berkas_bersyarat.keterlambatan', []))
            : [];
        $kasusKhusus = $jenisKasus !== LaporanKematian::JENIS_KASUS_NORMAL
            ? array_values((array) config('verifikasi.berkas_bersyarat.jenis_kasus_khusus', []))
            : [];
        $opsional = array_values((array) config('verifikasi.berkas_opsional', []));

        $kurang = BerkasPendukung::jenisYangKurang($terunggah, $terlambat, $jenisKasus);

        $jenisWajibTunggal = array_values(array_unique(array_merge(
            $lainnya,
            $keterlambatan,
            $kasusKhusus,
        )));

        $dokumenTampil = array_values(array_unique(array_merge(
            $buktiKematian,
            $jenisWajibTunggal,
            $opsional,
        )));

        $jumlahWajib = ($buktiKematian === [] ? 0 : 1)
            + count($lainnya)
            + count($keterlambatan)
            + count($kasusKhusus);

        return [
            'kurang' => $kurang,
            'dokumen_tampil' => $dokumenTampil,
            'jenis_wajib_tunggal' => $jenisWajibTunggal,
            'jenis_bukti_kematian' => $buktiKematian,
            'ada_bukti_kematian' => array_intersect($buktiKematian, $terunggah) !== [],
            'jumlah_wajib' => $jumlahWajib,
            'jumlah_tersedia' => $jumlahWajib - count($kurang),
        ];
    }

    /**
     * @return list<string>
     */
    private function jenisBerkasBerhasil(LaporanKematian $laporan): array
    {
        return $laporan->berkasPendukung
            ->where('status_unggahan', BerkasPendukung::STATUS_BERHASIL)
            ->pluck('jenis_dokumen')
            ->unique()
            ->values()
            ->all();
    }

    private function terlambat(LaporanKematian $laporan): bool
    {
        $tanggalKematian = $laporan->tanggal_kematian;

        if ($tanggalKematian === null) {
            return false;
        }

        $tanggalAcuan = $laporan->tanggal_pengajuan ?? now();

        return $tanggalKematian->copy()->startOfDay()
            ->diffInDays($tanggalAcuan->copy()->startOfDay(), false)
            > (int) config('verifikasi.batas_hari_pelaporan', 30);
    }
}
