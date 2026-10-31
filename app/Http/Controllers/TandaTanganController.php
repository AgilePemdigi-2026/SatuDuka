<?php

namespace App\Http\Controllers;

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\LayananAudit;
use App\Services\LayananPenguncian;
use App\Services\Tte\TTEService;
use App\Services\Tte\TteTidakTersedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Penandatanganan dokumen kematian dengan TTE oleh Pejabat (use case 5.2.8).
 *
 * PIN/OTP tidak pernah disimpan, dicatat di log, atau dikirim ke audit.
 * Akses TTE dikunci 15 menit setelah 3 kali kegagalan berturut-turut melalui
 * {@see LayananPenguncian}; pemaksaan maupun pemukulan kembali dokumen berjalan
 * dalam satu transaksi dengan `lockForUpdate`.
 */
class TandaTanganController extends Controller
{
    /**
     * Pesan ketika akses TTE masih terkunci.
     */
    public const PESAN_TERKUNCI = 'Akses TTE dikunci selama 15 menit. Silakan coba lagi nanti.';

    /**
     * Pesan ketika kredensial tidak valid.
     */
    public const PESAN_KREDENSIAL_SALAH = 'Kredensial tidak valid. Silakan coba lagi.';

    /**
     * Pesan ketika layanan TTE tidak dapat dihubungi.
     */
    public const PESAN_LAYANAN_GAGAL = 'Penandatanganan gagal. Silakan coba lagi nanti.';

    /**
     * Status dokumen yang boleh diproses pejabat.
     *
     * @var list<string>
     */
    private const STATUS_DOKUMEN_TAMPIL = [
        DokumenResmi::STATUS_MENUNGGU,
        DokumenResmi::STATUS_SELESAI,
        DokumenResmi::STATUS_DIKEMBALIKAN,
    ];

    /**
     * Status pengajuan yang sudah disetujui verifikator.
     *
     * @var list<string>
     */
    private const STATUS_LAPORAN_TAMPIL = [
        LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
        LaporanKematian::STATUS_SELESAI,
        LaporanKematian::STATUS_DIKEMBALIKAN,
    ];

    public function __construct(private readonly TTEService $tte) {}

    /**
     * Daftar dokumen beserta statistik, pencarian, dan filter status.
     */
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));
        $status = in_array($status, self::STATUS_DOKUMEN_TAMPIL, true) ? $status : '';

        $cari = trim((string) $request->query('cari', ''));

        $query = DokumenResmi::query()
            ->with(['laporanKematian.pemohon', 'pejabat'])
            ->whereIn('status_ttd', self::STATUS_DOKUMEN_TAMPIL);

        if ($status !== '') {
            $query->where('status_ttd', $status);
        }

        if ($cari !== '') {
            $pola = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($cari)).'%';

            $query->whereHas('laporanKematian', function ($subquery) use ($pola): void {
                $subquery
                    ->whereRaw('LOWER(no_pengajuan) LIKE ? ESCAPE ?', [$pola, '\\'])
                    ->orWhereRaw('LOWER(nik_almarhum) LIKE ? ESCAPE ?', [$pola, '\\'])
                    ->orWhereRaw('LOWER(nama_almarhum) LIKE ? ESCAPE ?', [$pola, '\\']);
            });
        }

        $daftar = $query
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('id_dokumen')
            ->paginate(10)
            ->withQueryString();

        $statistik = [
            'menunggu' => DokumenResmi::query()->where('status_ttd', DokumenResmi::STATUS_MENUNGGU)->count(),
            'selesai' => DokumenResmi::query()->where('status_ttd', DokumenResmi::STATUS_SELESAI)->count(),
            'dikembalikan' => DokumenResmi::query()->where('status_ttd', DokumenResmi::STATUS_DIKEMBALIKAN)->count(),
        ];

        return view('tanda_tangan.index', [
            'daftar' => $daftar,
            'statistik' => $statistik,
            'cari' => $cari,
            'status' => $status,
        ]);
    }

    /**
     * Detail dokumen, pratinjau akta, dan aksi TTE.
     */
    public function show(LaporanKematian $laporan): Response
    {
        $dokumen = $this->dokumenUntukTampil($laporan);

        $laporan->loadMissing('pemohon');

        return response()
            ->view('tanda_tangan.show', [
                'laporan' => $laporan,
                'dokumen' => $dokumen,
                'terkunci' => LayananPenguncian::terkunci($this->kunciTte(request()->user())),
                'sisaKunci' => LayananPenguncian::sisaKunci($this->kunciTte(request()->user())),
            ])
            ->withHeaders([
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }

    /**
     * Menandatangani dokumen dengan kredensial TTE.
     */
    public function tandatangani(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $pengguna = $request->user();
        $kunci = $this->kunciTte($pengguna);

        $data = $request->validate([
            'pin' => ['required', 'digits:6'],
        ], [
            'pin.required' => 'PIN/OTP wajib diisi.',
            'pin.digits' => 'PIN/OTP harus 6 digit angka.',
        ]);

        // Penguncian diperiksa SEBELUM PIN divalidasi ke layanan TTE.
        if (LayananPenguncian::terkunci($kunci)) {
            LayananAudit::catat(
                aksi: 'percobaan_tte_saat_terkunci',
                objek: 'laporan_kematian',
                idObjek: $laporan->id_laporan,
                detail: ['no_pengajuan' => $laporan->no_pengajuan],
                pengguna: $pengguna,
                layananEksternal: LogAudit::LAYANAN_TTE,
                hasil: LogAudit::HASIL_GAGAL,
            );

            return back()->with('galat', self::PESAN_TERKUNCI);
        }

        try {
            $sah = $this->tte->verifikasiKredensial($pengguna, $data['pin']);
        } catch (TteTidakTersedia) {
            LayananAudit::catat(
                aksi: 'memeriksa_layanan_eksternal',
                objek: 'laporan_kematian',
                idObjek: $laporan->id_laporan,
                detail: ['no_pengajuan' => $laporan->no_pengajuan, 'keterangan' => 'Layanan TTE tidak tersedia.'],
                pengguna: $pengguna,
                layananEksternal: LogAudit::LAYANAN_TTE,
                hasil: LogAudit::HASIL_GAGAL,
            );

            return back()->with('galat', self::PESAN_LAYANAN_GAGAL);
        }

        if (! $sah) {
            $percobaan = LayananPenguncian::catatGagalDanKunciBilaPerlu(
                kunci: $kunci,
                maksGagal: LayananPenguncian::MAKS_GAGAL_TTE,
                pengguna: $pengguna,
            );

            // Detail audit hanya berisi nomor percobaan, tanpa kredensial.
            LayananAudit::catat(
                aksi: 'gagal_verifikasi_tte',
                objek: 'laporan_kematian',
                idObjek: $laporan->id_laporan,
                detail: ['no_pengajuan' => $laporan->no_pengajuan, 'percobaan_ke' => $percobaan],
                pengguna: $pengguna,
                layananEksternal: LogAudit::LAYANAN_TTE,
                hasil: LogAudit::HASIL_GAGAL,
            );

            return back()->with(
                'galat',
                LayananPenguncian::terkunci($kunci) ? self::PESAN_TERKUNCI : self::PESAN_KREDENSIAL_SALAH,
            );
        }

        DB::transaction(function () use ($request, $laporan): void {
            $laporanTerkunci = LaporanKematian::query()
                ->whereKey($laporan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $dokumen = DokumenResmi::query()
                ->where('id_laporan', $laporanTerkunci->id_laporan)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $laporanTerkunci->status_pengajuan === LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE
                    && $dokumen !== null
                    && $dokumen->status_ttd === DokumenResmi::STATUS_MENUNGGU,
                409,
                'Dokumen ini sudah tidak menunggu tanda tangan.',
            );

            $dokumen->update([
                'status_ttd' => DokumenResmi::STATUS_SELESAI,
                'id_pengguna' => $request->user()->id_pengguna,
                'tanggal_terbit' => now(),
                'alasan_pengembalian' => null,
            ]);

            $laporanTerkunci->update([
                'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
            ]);

            LayananAudit::catat(
                aksi: 'menandatangani_dokumen',
                objek: 'dokumen_resmi',
                idObjek: $dokumen->id_dokumen,
                detail: [
                    'no_pengajuan' => $laporanTerkunci->no_pengajuan,
                    'penyedia' => $this->tte->namaPenyedia(),
                ],
                pengguna: $request->user(),
                layananEksternal: LogAudit::LAYANAN_TTE,
                hasil: LogAudit::HASIL_SUKSES,
            );
        });

        // Kredensial benar: hitungan kegagalan direset.
        LayananPenguncian::buka($kunci);

        return redirect()
            ->route('tanda_tangan.index')
            ->with('sukses', 'Dokumen berhasil ditandatangani secara elektronik.');
    }

    /**
     * Mengembalikan dokumen kepada verifikator/pemohon beserta alasan.
     */
    public function kembalikan(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'alasan.required' => 'Alasan pengembalian wajib diisi.',
            'alasan.min' => 'Alasan pengembalian minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($request, $laporan, $data): void {
            $laporanTerkunci = LaporanKematian::query()
                ->whereKey($laporan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $dokumen = DokumenResmi::query()
                ->where('id_laporan', $laporanTerkunci->id_laporan)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $laporanTerkunci->status_pengajuan === LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE
                    && $dokumen !== null
                    && $dokumen->status_ttd === DokumenResmi::STATUS_MENUNGGU,
                409,
                'Dokumen ini sudah tidak menunggu tanda tangan.',
            );

            $alasan = trim((string) $data['alasan']);

            $dokumen->update([
                'status_ttd' => DokumenResmi::STATUS_DIKEMBALIKAN,
                'alasan_pengembalian' => $alasan,
            ]);

            $laporanTerkunci->update([
                'status_pengajuan' => LaporanKematian::STATUS_DIKEMBALIKAN,
                'catatan_revisi' => $alasan,
            ]);

            LayananAudit::catat(
                aksi: 'mengembalikan_dokumen',
                objek: 'dokumen_resmi',
                idObjek: $dokumen->id_dokumen,
                detail: [
                    'no_pengajuan' => $laporanTerkunci->no_pengajuan,
                    'alasan' => $alasan,
                ],
                pengguna: $request->user(),
                layananEksternal: LogAudit::LAYANAN_TTE,
                hasil: LogAudit::HASIL_SUKSES,
            );
        });

        return redirect()
            ->route('tanda_tangan.index')
            ->with('sukses', 'Dokumen dikembalikan untuk perbaikan.');
    }

    /**
     * Dokumen milik laporan yang boleh ditampilkan ke pejabat.
     */
    private function dokumenUntukTampil(LaporanKematian $laporan): DokumenResmi
    {
        $dokumen = $laporan->dokumenResmi;

        abort_unless(
            $dokumen !== null
                && in_array($laporan->status_pengajuan, self::STATUS_LAPORAN_TAMPIL, true)
                && in_array($dokumen->status_ttd, self::STATUS_DOKUMEN_TAMPIL, true),
            404,
        );

        return $dokumen;
    }

    /**
     * Kunci penguncian TTE per pejabat (bukan per dokumen).
     */
    private function kunciTte(?Pengguna $pengguna): string
    {
        return 'tte:'.($pengguna?->id_pengguna ?? 'tamu');
    }
}
