<?php

namespace App\Http\Controllers;

use App\Models\AnalisisAi;
use App\Models\AnomaliDokumen;
use App\Models\BerkasPendukung;
use App\Models\DokumenResmi;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\Notifikasi;
use App\Services\Ai\LayananAnalisisAi;
use App\Services\LayananAudit;
use App\Services\Notifikasi\NotifikasiService;
use App\Services\Verifikasi\HasilAnalisis;
use App\Services\Verifikasi\MesinRekonsiliasi;
use App\Support\PengindeksNik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Alur utama verifikasi berkas oleh verifikator.
 *
 * Antrean mencakup seluruh status non-draf dengan pencarian yang memakai
 * blind index NIK (kolom NIK terenkripsi tidak pernah dicari dengan LIKE).
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
        LaporanKematian::STATUS_DIKEMBALIKAN,
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
        private readonly NotifikasiService $notifikasi,
    ) {}

    /**
     * Antrean verifikasi: seluruh status non-draf dengan filter dan pencarian.
     *
     * Pencarian memakai blind index NIK; kolom `nik_almarhum` terenkripsi dan
     * TIDAK PERNAH dicari dengan LIKE.
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
            $cari = mb_strtolower(trim((string) $request->input('cari')));
            $escapedCari = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $cari
            );
            $pola = '%'.$escapedCari.'%';
            $hashNik = PengindeksNik::hash($cari);

            $query->where(function ($subquery) use ($pola, $hashNik) {
                $subquery
                    ->whereRaw('LOWER(no_pengajuan) LIKE ? ESCAPE ?', [$pola, '\\'])
                    ->orWhereRaw('LOWER(nama_almarhum) LIKE ? ESCAPE ?', [$pola, '\\']);

                if ($hashNik !== null) {
                    $subquery->orWhere('nik_almarhum_hash', $hashNik);
                }
            });
        }

        $pengajuan = $query->paginate(10)->withQueryString();

        // Label anomali Indonesia untuk antrean: mesin hanya menyarankan,
        // tampilan menunjukkan anomali teratas dan jumlah sisanya.
        $pengajuan->getCollection()->transform(function (LaporanKematian $item): LaporanKematian {
            $hasil = $this->mesin->analisis($item, false);
            $teratas = $hasil->anomali[0] ?? null;

            $item->setAttribute('ringkasan_anomali', [
                'label' => $teratas
                    ? (string) config('verifikasi.taksonomi_anomali.'.$teratas->kode.'.label', $teratas->kode)
                    : null,
                'varian' => match ($teratas?->keparahan) {
                    'fatal' => 'badge-bad',
                    'tinggi' => 'badge-warning',
                    'sedang', 'rendah' => 'badge-brand',
                    default => 'badge-brand',
                },
                'lainnya' => max(0, count($hasil->anomali) - 1),
            ]);

            return $item;
        });

        $jumlahPerStatus = LaporanKematian::query()
            ->where('status_pengajuan', '!=', LaporanKematian::STATUS_DRAF)
            ->selectRaw('status_pengajuan, COUNT(*) as jumlah')
            ->groupBy('status_pengajuan')
            ->pluck('jumlah', 'status_pengajuan');

        $ringkasan = [
            'total' => $jumlahPerStatus->sum(),
            'menunggu' => (int) $jumlahPerStatus->get(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI, 0),
            'rekonsiliasi' => (int) $jumlahPerStatus->get(LaporanKematian::STATUS_DALAM_REKONSILIASI, 0),
            'selesai' => (int) $jumlahPerStatus->get(LaporanKematian::STATUS_SELESAI, 0),
        ];

        return view('verifikasi.index', compact('pengajuan', 'ringkasan', 'statusFilter'));
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
        $analisisKesesuaian = $laporan->analisisAi()
            ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
            ->where('status', AnalisisAi::STATUS_BERHASIL)
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id_analisis')
            ->first();

        return view('verifikasi.show', [
            'laporan' => $laporan,
            'analisisAturan' => $analisis,
            'analisisKesesuaian' => $analisisKesesuaian,
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
        ], 'Berkas berhasil disetujui dan masuk tahap menunggu TTE.');
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
            // 429/503 dari penyedia AI: tertunda, bukan gagal permanen.
            if ($hasil['tertunda'] ?? false) {
                return redirect()
                    ->route('verifikasi.show', $laporan)
                    ->with('info', $hasil['pesan']);
            }

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
     * Membuat ringkasan ketidaksesuaian berbantuan AI untuk verifikator.
     */
    public function ringkasanAi(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $this->pastikanUntukTampil($laporan);

        $catatanPerbandingan = AnalisisAi::query()
            ->where('id_laporan', $laporan->id_laporan)
            ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
            ->where('status', AnalisisAi::STATUS_BERHASIL)
            ->latest('id_analisis')
            ->first();

        $perbandingan = is_array($catatanPerbandingan?->hasil)
            ? $catatanPerbandingan->hasil
            : ['kolom' => []];

        $hasil = $this->layananAi->ringkas(
            $laporan,
            $perbandingan,
            $request->user(),
            'verifikator_detail'
        );

        if (! ($hasil['berhasil'] ?? false)) {
            if ($hasil['tertunda'] ?? false) {
                return redirect()
                    ->route('verifikasi.show', $laporan)
                    ->with('info', $hasil['pesan']);
            }

            return redirect()
                ->route('verifikasi.show', $laporan)
                ->with('galat', $hasil['pesan'] ?? 'Analisis AI tidak tersedia, lanjutkan secara manual');
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

        $this->kirimNotifikasiKeputusan($laporan, $tindakan, $catatan);

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

    /**
     * Kirim notifikasi ke pemohon sesuai keputusan verifikator.
     */
    private function kirimNotifikasiKeputusan(
        LaporanKematian $laporan,
        string $tindakan,
        string $catatan,
    ): void {
        $pemohon = $laporan->pemohon;

        if (! $pemohon) {
            return;
        }

        [$jenis, $judul, $pesan] = match ($tindakan) {
            self::TINDAKAN_SETUJUI => [
                Notifikasi::JENIS_INFO,
                'Berkas Disetujui',
                'Berkas Anda telah disetujui verifikator dan menunggu tanda tangan elektronik pejabat.',
            ],
            self::TINDAKAN_PERBAIKAN => [
                Notifikasi::JENIS_PERLU_DIPERBAIKI,
                'Pengajuan Perlu Diperbaikan',
                $catatan !== '' ? $catatan : 'Berkas Anda perlu diperbaiki sebelum dapat diproses.',
            ],
            self::TINDAKAN_REVISI => [
                Notifikasi::JENIS_PERLU_DIPERBAIKI,
                'Pengajuan Ditolak/Butuh Revisi',
                $catatan !== '' ? $catatan : 'Pengajuan Anda memerlukan revisi dari verifikator.',
            ],
            self::TINDAKAN_ANOMALI => [
                Notifikasi::JENIS_INFO,
                'Berkas Ditandai Anomali',
                $catatan !== '' ? $catatan : 'Berkas Anda masuk proses rekonsiliasi anomali data.',
            ],
            self::TINDAKAN_FATAL => [
                Notifikasi::JENIS_INFO,
                'Pengajuan Ditolak Permanen',
                $catatan !== '' ? $catatan : 'Pengajuan ditolak karena anomali data fatal.',
            ],
            default => [Notifikasi::JENIS_INFO, 'Status Pengajuan Berubah', $catatan],
        };

        $this->notifikasi->kirim($pemohon, $jenis, $judul, $pesan, $laporan);
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

    private function ringkasanTerakhir(LaporanKematian $laporan): ?AnalisisAi
    {
        $laporan->loadMissing('analisisAi');

        return $laporan->analisisAi
            ->where('jenis', AnalisisAi::JENIS_RINGKASAN)
            ->sortByDesc('id_analisis')
            ->first();
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
