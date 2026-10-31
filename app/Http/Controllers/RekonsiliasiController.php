<?php

namespace App\Http\Controllers;

use App\Models\AnalisisAi;
use App\Models\AnomaliDokumen;
use App\Models\BerkasPendukung;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Services\LayananAudit;
use App\Services\Verifikasi\LayananValidasiNik;
use App\Support\DataSIAK;
use App\Support\MaskerNik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Rekonsiliasi anomali data kependudukan (SRS 5.2.7 dan 6.2.7).
 *
 * Verifikator membandingkan isian pemohon, data SIAK, dan hasil ekstraksi
 * surat keterangan, lalu memilih menyelesaikan rekonsiliasi, mengembalikan
 * berkas ke pemohon, atau menolak permanen karena anomali fatal. Semua aksi
 * berjalan dalam satu transaksi dengan lockForUpdate dan dicatat ke log audit.
 * Mesin hanya menyarankan; keputusan tetap pada manusia.
 */
class RekonsiliasiController extends Controller
{
    /**
     * Kolom yang ditampilkan pada panel komparasi.
     *
     * @var array<string, string>
     */
    private const KOLOM_KOMPARASI = [
        'nama_almarhum' => 'Nama Almarhum/Almarhumah',
        'nik' => 'NIK',
        'tanggal_kematian' => 'Tanggal Kematian',
        'tempat_kematian' => 'Tempat Kematian',
    ];

    public function __construct(
        private readonly LayananValidasiNik $validasiNik,
    ) {}

    /**
     * Antrean rekonsiliasi: pengajuan berstatus Dalam Rekonsiliasi Anomali.
     */
    public function index(Request $request): View
    {
        $query = LaporanKematian::query()
            ->with(['pemohon', 'hasilVerifikasi.anomaliDokumen'])
            ->where('status_pengajuan', LaporanKematian::STATUS_DALAM_REKONSILIASI);

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

        $pengajuan = $query
            ->orderBy('tanggal_pengajuan')
            ->paginate(10)
            ->withQueryString();

        return view('rekonsiliasi.index', compact('pengajuan'));
    }

    /**
     * Detail komparasi data dan aksi rekonsiliasi.
     */
    public function show(LaporanKematian $laporan): View
    {
        $this->pastikanDalamRekonsiliasi($laporan);

        $laporan->load([
            'pemohon',
            'berkasPendukung',
            'hasilVerifikasi.verifikator',
            'hasilVerifikasi.anomaliDokumen',
        ]);

        $anomali = $this->anomaliTerbuka($laporan);
        $siak = $this->ambilSiak($laporan);
        $ekstraksi = $this->ekstraksiTerakhir($laporan);

        return view('rekonsiliasi.show', [
            'laporan' => $laporan,
            'anomali' => $anomali,
            'kolomKomparasi' => $this->susunKomparasi($laporan, $siak, $ekstraksi),
            'siakTersedia' => $siak !== null,
            'ekstraksiTersedia' => $ekstraksi !== null,
            'bannerManual' => $siak === null || $ekstraksi === null,
        ]);
    }

    /**
     * Menyelesaikan rekonsiliasi: koreksi data dan kembalikan ke antrean.
     */
    public function selesaikan(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $data = $request->validate([
            'nama_almarhum' => ['required', 'string', 'max:100'],
            'nik_almarhum' => ['nullable', 'regex:/^\d{16}$/'],
            'tanggal_kematian' => ['required', 'date'],
            'catatan_rekonsiliasi' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'nama_almarhum.required' => 'Nama almarhum wajib diisi.',
            'nik_almarhum.regex' => 'NIK almarhum harus tepat 16 digit angka.',
            'tanggal_kematian.required' => 'Tanggal kematian wajib diisi.',
            'tanggal_kematian.date' => 'Tanggal kematian tidak valid.',
            'catatan_rekonsiliasi.required' => 'Catatan rekonsiliasi wajib diisi.',
            'catatan_rekonsiliasi.min' => 'Catatan rekonsiliasi minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($request, $laporan, $data) {
            $laporanTerkunci = $this->kunciLaporan($laporan);

            $this->validasiKoreksi($laporanTerkunci, $data);

            $perubahan = $this->susunPerubahan($laporanTerkunci, $data);
            $nilaiSebelum = $laporanTerkunci->only(array_keys($perubahan));

            if ($perubahan !== []) {
                $laporanTerkunci->update($perubahan);
            }

            $laporanTerkunci->update([
                'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                'catatan_revisi' => null,
            ]);

            $this->tutupAnomali($laporanTerkunci, AnomaliDokumen::STATUS_SELESAI);

            HasilVerifikasi::create([
                'id_laporan' => $laporanTerkunci->id_laporan,
                'id_pengguna' => $request->user()->id_pengguna,
                'status_verifikasi' => HasilVerifikasi::STATUS_REKONSILIASI,
                'catatan' => $data['catatan_rekonsiliasi'],
                'tanggal_verifikasi' => now(),
            ]);

            LayananAudit::catat(
                aksi: 'menyelesaikan_rekonsiliasi',
                objek: 'laporan_kematian',
                idObjek: $laporanTerkunci->id_laporan,
                detail: $this->detailAudit($laporanTerkunci, $data, $perubahan, $nilaiSebelum),
                pengguna: $request->user(),
                hasil: 'Sukses',
            );
        });

        return redirect()
            ->route('rekonsiliasi.index')
            ->with('sukses', 'Anomali berhasil diselesaikan dan berkas dikembalikan ke antrean verifikasi.');
    }

    /**
     * Menolak permanen dan mengeskalasi anomali data fatal.
     */
    public function tolakFatal(Request $request, LaporanKematian $laporan): RedirectResponse
    {
        $data = $request->validate([
            'alasan_eskalasi' => ['required', 'string', 'min:5', 'max:2000'],
            'catatan_resmi' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'alasan_eskalasi.required' => 'Alasan eskalasi wajib diisi.',
            'alasan_eskalasi.min' => 'Alasan eskalasi minimal 5 karakter.',
            'catatan_resmi.required' => 'Catatan resmi wajib diisi.',
            'catatan_resmi.min' => 'Catatan resmi minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($request, $laporan, $data) {
            $laporanTerkunci = $this->kunciLaporan($laporan);

            $laporanTerkunci->update([
                'status_pengajuan' => LaporanKematian::STATUS_DITOLAK_ANOMALI_FATAL,
                'catatan_revisi' => $data['catatan_resmi'],
            ]);

            $this->tutupAnomali(
                $laporanTerkunci,
                AnomaliDokumen::STATUS_DITOLAK_PERMANEN,
            );

            HasilVerifikasi::create([
                'id_laporan' => $laporanTerkunci->id_laporan,
                'id_pengguna' => $request->user()->id_pengguna,
                'status_verifikasi' => HasilVerifikasi::STATUS_DITOLAK,
                'catatan' => $data['catatan_resmi'],
                'alasan_override' => $data['alasan_eskalasi'],
                'tanggal_verifikasi' => now(),
            ]);

            LayananAudit::catat(
                aksi: 'menolak_anomali_fatal_rekonsiliasi',
                objek: 'laporan_kematian',
                idObjek: $laporanTerkunci->id_laporan,
                detail: [
                    'no_pengajuan' => $laporanTerkunci->no_pengajuan,
                    'alasan_eskalasi' => MaskerNik::terapkan($data['alasan_eskalasi']),
                    'catatan_resmi' => MaskerNik::terapkan($data['catatan_resmi']),
                ],
                pengguna: $request->user(),
                hasil: 'Sukses',
            );
        });

        return redirect()
            ->route('rekonsiliasi.index')
            ->with('sukses', 'Pengajuan ditolak permanen karena anomali data fatal.');
    }

    private function kunciLaporan(LaporanKematian $laporan): LaporanKematian
    {
        $laporanTerkunci = LaporanKematian::query()
            ->whereKey($laporan->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        abort_unless(
            $laporanTerkunci->status_pengajuan === LaporanKematian::STATUS_DALAM_REKONSILIASI,
            409,
            'Pengajuan ini tidak berada dalam proses rekonsiliasi.'
        );

        return $laporanTerkunci;
    }

    private function pastikanDalamRekonsiliasi(?LaporanKematian $laporan): void
    {
        abort_unless(
            $laporan && $laporan->status_pengajuan === LaporanKematian::STATUS_DALAM_REKONSILIASI,
            404,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validasiKoreksi(LaporanKematian $laporan, array $data): void
    {
        $nikMasukan = preg_replace('/\D+/', '', (string) ($data['nik_almarhum'] ?? '')) ?? '';
        $nikBaru = $nikMasukan === '' ? (string) $laporan->nik_almarhum : $nikMasukan;
        $tanggalBaru = Carbon::parse((string) $data['tanggal_kematian'])->startOfDay();

        $nikBerubah = $nikBaru !== (string) $laporan->nik_almarhum;
        $tanggalBerubah = $laporan->tanggal_kematian?->toDateString() !== $tanggalBaru->toDateString();

        $errors = [];

        if ($tanggalBerubah) {
            if ($tanggalBaru->isAfter(today())) {
                $errors['tanggal_kematian'][] = 'Tanggal kematian tidak boleh di masa depan.';
            }

            if ($laporan->tanggal_pengajuan !== null
                && $tanggalBaru->isAfter($laporan->tanggal_pengajuan->copy()->startOfDay())) {
                $errors['tanggal_kematian'][] = 'Tanggal kematian tidak boleh melewati tanggal pengajuan.';
            }

            if ($laporan->tanggal_pengajuan !== null
                && $tanggalBaru->diffInDays($laporan->tanggal_pengajuan->copy()->startOfDay(), false)
                    > (int) config('verifikasi.batas_hari_pelaporan', 30)
                && ! $this->punyaSptjm($laporan)) {
                $errors['tanggal_kematian'][] = 'Tanggal kematian melebihi 30 hari dan SPTJM Kebenaran Data Kematian belum diunggah.';
            }
        }

        if ($nikBerubah || $tanggalBerubah) {
            $siak = $this->cariSiak($nikBaru);

            $hasil = $this->validasiNik->periksa(
                nik: $nikBaru,
                jenisKelamin: $siak['jenis_kelamin'] ?? null,
                tanggalLahir: $this->tanggalLahirSiak($siak),
                tanggalKematian: $tanggalBaru,
            );

            foreach ($hasil->pelanggaran as $pelanggaran) {
                $kolom = $pelanggaran->kode === LayananValidasiNik::KODE_TANGGAL_TIDAK_KONSISTEN
                    ? 'tanggal_kematian'
                    : 'nik_almarhum';

                $errors[$kolom][] = $pelanggaran->pesan;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(
                array_map(
                    static fn (array $pesan): string => implode(' ', array_values(array_unique($pesan))),
                    $errors,
                ),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    private function susunPerubahan(LaporanKematian $laporan, array $data): array
    {
        $perubahan = [];

        $nama = trim((string) $data['nama_almarhum']);

        if ($nama !== '' && $nama !== (string) $laporan->nama_almarhum) {
            $perubahan['nama_almarhum'] = $nama;
        }

        $nik = preg_replace('/\D+/', '', (string) ($data['nik_almarhum'] ?? '')) ?? '';

        if ($nik !== '' && $nik !== (string) $laporan->nik_almarhum) {
            $perubahan['nik_almarhum'] = $nik;
        }

        $tanggal = Carbon::parse((string) $data['tanggal_kematian'])->toDateString();

        if ($laporan->tanggal_kematian?->toDateString() !== $tanggal) {
            $perubahan['tanggal_kematian'] = $tanggal;
        }

        return $perubahan;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string|null>  $perubahan
     * @param  array<string, mixed>  $nilaiSebelum
     * @return array<string, mixed>
     */
    private function detailAudit(LaporanKematian $laporan, array $data, array $perubahan, array $nilaiSebelum): array
    {
        $sebelum = [];
        $sesudah = [];

        foreach (array_keys($perubahan) as $kolom) {
            $nilaiLama = (string) ($nilaiSebelum[$kolom] ?? '');
            $nilaiBaru = (string) $perubahan[$kolom];

            if ($kolom === 'nik_almarhum') {
                $sebelum[$kolom] = MaskerNik::masker($nilaiLama);
                $sesudah[$kolom] = MaskerNik::masker($nilaiBaru);

                continue;
            }

            $sebelum[$kolom] = MaskerNik::terapkan($nilaiLama);
            $sesudah[$kolom] = MaskerNik::terapkan($nilaiBaru);
        }

        return [
            'no_pengajuan' => $laporan->no_pengajuan,
            'perubahan' => $perubahan === [] ? null : [
                'sebelum' => $sebelum,
                'sesudah' => $sesudah,
            ],
            'catatan_rekonsiliasi' => MaskerNik::terapkan((string) $data['catatan_rekonsiliasi']),
        ];
    }

    /**
     * @return list<AnomaliDokumen>
     */
    private function anomaliTerbuka(LaporanKematian $laporan): array
    {
        return AnomaliDokumen::query()
            ->whereIn('id_verifikasi', $laporan->hasilVerifikasi()->select('id_verifikasi'))
            ->where('status_rekonsiliasi', AnomaliDokumen::STATUS_TERBUKA)
            ->orderBy('id_anomali')
            ->get()
            ->all();
    }

    private function tutupAnomali(LaporanKematian $laporan, string $status): void
    {
        AnomaliDokumen::query()
            ->whereIn('id_verifikasi', $laporan->hasilVerifikasi()->select('id_verifikasi'))
            ->where('status_rekonsiliasi', AnomaliDokumen::STATUS_TERBUKA)
            ->update([
                'status_rekonsiliasi' => $status,
                'tanggal_rekonsiliasi' => now(),
            ]);
    }

    /**
     * @return array<string, string>|null
     */
    private function ambilSiak(LaporanKematian $laporan): ?array
    {
        $nik = trim((string) $laporan->nik_almarhum);

        return $nik === '' ? null : $this->cariSiak($nik);
    }

    /**
     * @return array<string, string>|null
     */
    private function cariSiak(string $nik): ?array
    {
        try {
            return DataSIAK::cariNik($nik);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, array{nilai: string|null, keyakinan: float}>|null
     */
    private function ekstraksiTerakhir(LaporanKematian $laporan): ?array
    {
        $baris = AnalisisAi::query()
            ->where('id_laporan', $laporan->id_laporan)
            ->where('jenis', AnalisisAi::JENIS_EKSTRAKSI)
            ->where('status', AnalisisAi::STATUS_BERHASIL)
            ->orderByDesc('id_analisis')
            ->first();

        $hasil = $baris?->hasil;
        $kolom = is_array($hasil) ? ($hasil['kolom'] ?? null) : null;

        return is_array($kolom) ? $kolom : null;
    }

    /**
     * @param  array<string, string>|null  $siak
     * @param  array<string, array{nilai: string|null, keyakinan: float}>|null  $ekstraksi
     * @return list<array<string, string|null>>
     */
    private function susunKomparasi(LaporanKematian $laporan, ?array $siak, ?array $ekstraksi): array
    {
        $baris = [];

        foreach (self::KOLOM_KOMPARASI as $kunci => $label) {
            $nilaiPemohon = $this->nilaiPemohon($laporan, $kunci);
            $nilaiSiak = $this->nilaiSiak($siak, $kunci);
            $nilaiEkstraksi = $ekstraksi[$kunci]['nilai'] ?? null;

            $baris[] = [
                'kunci' => $kunci,
                'label' => $label,
                'nilai_pemohon' => $this->tampilkan($kunci, $nilaiPemohon),
                'nilai_siak' => $this->tampilkan($kunci, $nilaiSiak),
                'nilai_ekstraksi' => $this->tampilkan($kunci, $nilaiEkstraksi),
                'status' => $this->statusBaris($kunci, $nilaiPemohon, $nilaiSiak, $nilaiEkstraksi),
            ];
        }

        return $baris;
    }

    private function nilaiPemohon(LaporanKematian $laporan, string $kunci): ?string
    {
        return match ($kunci) {
            'nama_almarhum' => $laporan->nama_almarhum,
            'nik' => $laporan->nik_almarhum,
            'tanggal_kematian' => $laporan->tanggal_kematian?->toDateString(),
            'tempat_kematian' => $laporan->tempat_kematian,
            default => null,
        };
    }

    /**
     * @param  array<string, string>|null  $siak
     */
    private function nilaiSiak(?array $siak, string $kunci): ?string
    {
        if ($siak === null) {
            return null;
        }

        return match ($kunci) {
            'nama_almarhum' => $siak['nama_lengkap'] ?? null,
            'nik' => $siak['nik'] ?? null,
            default => null,
        };
    }

    private function tampilkan(string $kunci, ?string $nilai): ?string
    {
        if ($nilai === null || trim($nilai) === '') {
            return null;
        }

        if ($kunci === 'nik') {
            return MaskerNik::masker($nilai);
        }

        if ($kunci === 'tanggal_kematian') {
            try {
                return Carbon::parse($nilai)->translatedFormat('j F Y');
            } catch (\Throwable) {
                return $nilai;
            }
        }

        return $nilai;
    }

    private function statusBaris(string $kunci, ?string $pemohon, ?string $siak, ?string $ekstraksi): string
    {
        if ($ekstraksi === null || trim((string) $ekstraksi) === '') {
            return 'tidak terbaca';
        }

        $normalPemohon = $this->normalisasi($kunci, $pemohon);

        if ($normalPemohon === $this->normalisasi($kunci, $ekstraksi)) {
            if ($siak === null || $normalPemohon === $this->normalisasi($kunci, $siak)) {
                return 'cocok';
            }
        }

        return 'beda';
    }

    private function normalisasi(string $kunci, ?string $nilai): string
    {
        if ($nilai === null) {
            return '';
        }

        if ($kunci === 'nik') {
            return preg_replace('/\D+/', '', $nilai) ?? '';
        }

        if ($kunci === 'tanggal_kematian') {
            try {
                return Carbon::parse($nilai)->toDateString();
            } catch (\Throwable) {
                return mb_strtolower(trim($nilai));
            }
        }

        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $nilai) ?? $nilai));
    }

    private function punyaSptjm(LaporanKematian $laporan): bool
    {
        return $laporan->berkasPendukung()
            ->where('jenis_dokumen', BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN)
            ->where('status_unggahan', BerkasPendukung::STATUS_BERHASIL)
            ->exists();
    }

    /**
     * @param  array<string, string>|null  $siak
     */
    private function tanggalLahirSiak(?array $siak): ?Carbon
    {
        $tanggal = $siak['tanggal_lahir'] ?? null;

        if (blank($tanggal)) {
            return null;
        }

        try {
            return Carbon::parse($tanggal);
        } catch (\Throwable) {
            return null;
        }
    }
}
