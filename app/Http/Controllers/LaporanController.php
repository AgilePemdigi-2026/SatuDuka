<?php

namespace App\Http\Controllers;

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Services\Ai\LayananAnalisisAi;
use App\Services\LayananAudit;
use App\Services\LayananNomorPengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Controller pengajuan laporan kematian (use case 5.2.3).
 *
 * Pemohon mengisi data pelapor, data almarhum, dan mengunggah empat berkas
 * pendukung. Pengajuan dapat disimpan sebagai draf, lalu dikirim sehingga
 * memperoleh nomor pengajuan dan berstatus Menunggu Verifikasi.
 */
class LaporanController extends Controller
{
    /**
     * Pesan galat unggah berkas (user story 6.2.3).
     */
    public const PESAN_BERKAS = 'Format file tidak didukung / ukuran file melebihi batas';

    /**
     * Batas ukuran berkas dalam kilobyte.
     */
    public const MAKS_UKURAN_KB = 5120;

    /**
     * Pemetaan jenis dokumen ke nama kolom unggahan pada formulir.
     *
     * @var array<string, string>
     */
    public const INPUT_BERKAS = [
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN => 'berkas_surat_keterangan',
        BerkasPendukung::JENIS_KTP_ALMARHUM => 'berkas_ktp_almarhum',
        BerkasPendukung::JENIS_KTP_PELAPOR => 'berkas_ktp_pelapor',
        BerkasPendukung::JENIS_KARTU_KELUARGA => 'berkas_kartu_keluarga',
    ];

    /**
     * Jenis ekstensi berkas yang diterima, diperiksa dari MIME.
     */
    private const EKSTENSI_BERKAS = 'pdf,jpg,jpeg,png';

    /**
     * Pilihan hubungan pelapor dengan almarhum.
     *
     * @var list<string>
     */
    private const DAFTAR_HUBUNGAN = [
        'Anak Kandung',
        'Suami/Istri',
        'Orang Tua',
        'Saudara Kandung',
        'Lainnya',
    ];

    /**
     * Kolom data yang diisi melalui formulir.
     *
     * @var list<string>
     */
    private const KOLOM_ISIAN = [
        'nama_pelapor',
        'hubungan_pelapor',
        'nama_almarhum',
        'nik_almarhum',
        'tanggal_kematian',
        'tempat_kematian',
        'sebab_kematian',
    ];

    /**
     * Menampilkan formulir pengajuan baru.
     */
    public function tampilkanBaru(Request $request)
    {
        return view('laporan.formulir', $this->dataFormulir(
            $request->user(),
            null,
            $this->langkahAman($request->integer('langkah', 1)),
        ));
    }

    /**
     * Menampilkan formulir untuk melanjutkan draf.
     */
    public function tampilkanUbah(Request $request, LaporanKematian $laporan)
    {
        $laporan = $this->laporanMilik($request, $laporan);
        $this->pastikanBolehDiubah($laporan);

        return view('laporan.formulir', $this->dataFormulir(
            $request->user(),
            $laporan->load('berkasPendukung'),
            $this->langkahAman($request->integer('langkah', 1)),
        ));
    }

    /**
     * Menyimpan pengajuan sebagai draf (data boleh belum lengkap).
     */
    public function simpanDraf(Request $request, ?LaporanKematian $laporan = null)
    {
        if ($laporan !== null) {
            $laporan = $this->laporanMilik($request, $laporan);
            $this->pastikanBolehDiubah($laporan);
        }

        $data = $this->rapikan($request->validate($this->aturan(false), $this->pesanValidasi()));

        if ($laporan === null) {
            $laporan = new LaporanKematian($data);
            $laporan->id_pengguna = $request->user()->id_pengguna;
            $laporan->status_pengajuan = LaporanKematian::STATUS_DRAF;
            $laporan->save();
        } else {
            $laporan->fill($data)->save();
        }

        LayananAudit::catat(
            aksi: 'menyimpan_draf_laporan',
            objek: 'laporan_kematian',
            idObjek: $laporan->id_laporan,
            detail: ['status' => $laporan->status_pengajuan],
            pengguna: $request->user(),
            hasil: 'Sukses',
        );

        return redirect()
            ->to($this->alamatLangkah($laporan, $this->langkahAman($request->integer('langkah', 1))))
            ->with('sukses', $laporan->adalahDraf()
                ? 'Draf pengajuan berhasil disimpan.'
                : 'Perubahan pengajuan berhasil disimpan.');
    }

    /**
     * Mengirim draf atau mengirim ulang pengajuan berstatus revisi.
     */
    public function kirim(Request $request, LaporanKematian $laporan)
    {
        $laporan = $this->laporanMilik($request, $laporan);
        $this->pastikanBolehDiubah($laporan);

        $validator = Validator::make(
            $request->all(),
            $this->aturan(true) + ['pernyataan' => ['accepted']],
            $this->pesanValidasi() + ['pernyataan.accepted' => 'Centang pernyataan kebenaran data sebelum mengirim pengajuan.'],
        );

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->redirectTo($this->alamatLangkah($laporan, $this->langkahGalat($validator->errors()->keys())));
        }

        $data = $validator->validated();

        unset($data['pernyataan']);
        $data = $this->rapikan($data);

        $this->pastikanBerkasLengkap($laporan);

        $hasil = DB::transaction(function () use ($laporan, $data) {
            $terkunci = LaporanKematian::query()
                ->whereKey($laporan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($terkunci->bolehDiubah(), 403, 'Pengajuan dengan status ini tidak dapat diubah.');

            $statusSebelumnya = $terkunci->status_pengajuan;
            $kirimUlang = ! $terkunci->adalahDraf();

            if ($kirimUlang) {
                $terkunci->fill($data);
                $terkunci->status_pengajuan = LaporanKematian::STATUS_MENUNGGU_VERIFIKASI;
                $terkunci->save();

                $laporanTerkirim = $terkunci;
            } else {
                $laporanTerkirim = LayananNomorPengajuan::buat(function (string $nomor) use ($terkunci, $data) {
                    $terkunci->fill($data);
                    $terkunci->no_pengajuan = $nomor;
                    $terkunci->status_pengajuan = LaporanKematian::STATUS_MENUNGGU_VERIFIKASI;
                    $terkunci->tanggal_pengajuan = now();
                    $terkunci->save();

                    return $terkunci;
                });
            }

            return [
                'laporan' => $laporanTerkirim,
                'aksi' => $kirimUlang ? 'mengirim_ulang_laporan' : 'mengajukan_laporan',
                'status_sebelumnya' => $statusSebelumnya,
            ];
        });

        $laporan = $hasil['laporan'];
        $kirimUlang = $hasil['aksi'] === 'mengirim_ulang_laporan';

        LayananAudit::catat(
            aksi: $hasil['aksi'],
            objek: 'laporan_kematian',
            idObjek: $laporan->id_laporan,
            detail: $kirimUlang
                ? ['no_pengajuan' => $laporan->no_pengajuan, 'status_sebelumnya' => $hasil['status_sebelumnya']]
                : ['no_pengajuan' => $laporan->no_pengajuan],
            pengguna: $request->user(),
            hasil: 'Sukses',
        );

        return redirect()
            ->route('laporan.lihat', $laporan)
            ->with('sukses', $kirimUlang
                ? 'Pengajuan berhasil dikirim ulang! Nomor pengajuan: '.$laporan->no_pengajuan
                : 'Pengajuan berhasil dikirim! Nomor pengajuan: '.$laporan->no_pengajuan);
    }

    /**
     * Mengunggah satu berkas pendukung pada draf pengajuan.
     */
    public function unggahBerkas(Request $request, LaporanKematian $laporan)
    {
        $laporan = $this->laporanMilik($request, $laporan);
        $this->pastikanBolehDiubah($laporan);

        $jenis = $request->validate([
            'jenis_dokumen' => ['required', Rule::in(BerkasPendukung::DAFTAR_JENIS)],
        ], [
            'jenis_dokumen.required' => 'Jenis dokumen tidak dikenali.',
            'jenis_dokumen.in' => 'Jenis dokumen tidak dikenali.',
        ])['jenis_dokumen'];

        $namaInput = self::INPUT_BERKAS[$jenis];

        $request->validate([
            $namaInput => ['required', 'file', 'max:'.self::MAKS_UKURAN_KB, 'mimes:'.self::EKSTENSI_BERKAS],
        ], [
            $namaInput.'.required' => self::PESAN_BERKAS,
            $namaInput.'.file' => self::PESAN_BERKAS,
            $namaInput.'.max' => self::PESAN_BERKAS,
            $namaInput.'.mimes' => self::PESAN_BERKAS,
        ]);

        $berkas = $request->file($namaInput);
        $ekstensi = $berkas->extension() ?: 'bin';

        $lama = $laporan->berkasPendukung()->where('jenis_dokumen', $jenis)->first();

        $path = Storage::disk('local')->putFileAs(
            'berkas_laporan/'.$laporan->id_laporan,
            $berkas,
            Str::random(40).'.'.$ekstensi,
        );

        $namaFile = $this->namaFileBersih($berkas->getClientOriginalName(), $ekstensi);

        $tersimpan = $laporan->berkasPendukung()->updateOrCreate(
            ['jenis_dokumen' => $jenis],
            [
                'nama_file' => $namaFile,
                'path_file' => $path,
                'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
                'tanggal_unggah' => now(),
            ],
        );

        if ($lama !== null && $lama->path_file !== '' && $lama->path_file !== $path
            && Storage::disk('local')->exists($lama->path_file)) {
            Storage::disk('local')->delete($lama->path_file);
        }

        LayananAudit::catat(
            aksi: 'mengunggah_berkas',
            objek: 'berkas_pendukung',
            idObjek: $tersimpan->id_berkas,
            detail: ['id_laporan' => $laporan->id_laporan, 'jenis_dokumen' => $jenis],
            pengguna: $request->user(),
            hasil: 'Sukses',
        );

        return redirect()
            ->to($this->alamatLangkah($laporan, 3))
            ->with('sukses', 'Berkas '.$jenis.' berhasil diunggah.');
    }

    /**
     * Menampilkan atau mengunduh berkas milik laporan pemohon.
     */
    public function pratinjauBerkas(Request $request, LaporanKematian $laporan, BerkasPendukung $berkas)
    {
        $laporan = $this->laporanMilik($request, $laporan);

        abort_unless((int) $berkas->id_laporan === (int) $laporan->id_laporan, 404);
        abort_unless($berkas->path_file !== '' && Storage::disk('local')->exists($berkas->path_file), 404);

        return Storage::disk('local')->response($berkas->path_file, $berkas->nama_file);
    }

    /**
     * Memeriksa surat keterangan kematian dengan bantuan AI.
     *
     * Hasil hanya disimpan sebagai saran; status pengajuan tidak diubah dan
     * tombol Kirim tetap dapat dipakai meskipun analisis gagal.
     */
    public function periksaAi(
        Request $request,
        LaporanKematian $laporan,
        LayananAnalisisAi $layanan,
    ) {
        $laporan = $this->laporanMilik($request, $laporan);
        $this->pastikanBolehDiubah($laporan);

        $berkas = $laporan->berkasPendukung()
            ->where('jenis_dokumen', BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN)
            ->first();

        if ($berkas === null) {
            return redirect()
                ->to($this->alamatLangkah($laporan, 4))
                ->with('galat', 'Unggah Surat Keterangan Kematian terlebih dahulu untuk memeriksa dokumen.');
        }

        $hasil = $layanan->periksa($berkas, $request->user(), 'pemohon_review');

        if (! $hasil['berhasil']) {
            return redirect()
                ->to($this->alamatLangkah($laporan, 4))
                ->with('galat', $hasil['pesan']);
        }

        return redirect()
            ->to($this->alamatLangkah($laporan, 4))
            ->with('analisis_ai', $hasil)
            ->with('sukses', 'Analisis AI selesai. Hasil bantuan AI, hanya saran.');
    }

    /**
     * Menampilkan ringkasan pengajuan.
     */
    public function lihat(Request $request, LaporanKematian $laporan)
    {
        $laporan = $this->laporanMilik($request, $laporan);

        return view('laporan.lihat', [
            'laporan' => $laporan->load('berkasPendukung'),
            'pengguna' => $request->user(),
            'inputBerkas' => self::INPUT_BERKAS,
        ]);
    }

    /**
     * Data pendukung tampilan formulir.
     *
     * @return array<string, mixed>
     */
    private function dataFormulir(Pengguna $pengguna, ?LaporanKematian $laporan, int $langkah): array
    {
        return [
            'pengguna' => $pengguna,
            'laporan' => $laporan,
            'langkah' => $langkah,
            'daftarHubungan' => self::DAFTAR_HUBUNGAN,
            'daftarJenis' => BerkasPendukung::DAFTAR_JENIS,
            'inputBerkas' => self::INPUT_BERKAS,
        ];
    }

    /**
     * Nomor langkah yang sah (1 sampai 4).
     */
    private function langkahAman(int $langkah): int
    {
        return max(1, min(4, $langkah));
    }

    /**
     * Alamat formulir draf pada langkah tertentu.
     */
    private function alamatLangkah(LaporanKematian $laporan, int $langkah): string
    {
        return route('laporan.ubah', $laporan).'?langkah='.$this->langkahAman($langkah);
    }

    /**
     * Langkah formulir yang memuat kolom bermasalah.
     *
     * @param  array<int, string>  $kunci
     */
    private function langkahGalat(array $kunci): int
    {
        $peta = [
            'nama_pelapor' => 1,
            'hubungan_pelapor' => 1,
            'nama_almarhum' => 2,
            'nik_almarhum' => 2,
            'tanggal_kematian' => 2,
            'tempat_kematian' => 2,
            'sebab_kematian' => 2,
        ];

        foreach ($kunci as $kolom) {
            if (isset($peta[$kolom])) {
                return $peta[$kolom];
            }

            if ($kolom === 'berkas' || str_starts_with($kolom, 'berkas_')) {
                return 3;
            }
        }

        return 4;
    }

    /**
     * Aturan validasi isian formulir.
     *
     * @return array<string, list<mixed>>
     */
    private function aturan(bool $wajib): array
    {
        $awalan = $wajib ? 'required' : 'nullable';

        return [
            'nama_pelapor' => [$awalan, 'string', 'max:100'],
            'hubungan_pelapor' => [$awalan, 'string', 'max:50', Rule::in(self::DAFTAR_HUBUNGAN)],
            'nama_almarhum' => [$awalan, 'string', 'max:100'],
            'nik_almarhum' => [$awalan, 'digits:16'],
            'tanggal_kematian' => [$awalan, 'date', 'before_or_equal:today'],
            'tempat_kematian' => [$awalan, 'string', 'max:100'],
            'sebab_kematian' => [$awalan, 'string', 'max:1000'],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        return [
            'required' => 'Harap isi semua field yang diperlukan',
            'hubungan_pelapor.in' => 'Hubungan dengan almarhum tidak valid.',
            'nik_almarhum.digits' => 'NIK almarhum harus terdiri dari 16 digit.',
            'tanggal_kematian.date' => 'Tanggal kematian tidak valid.',
            'tanggal_kematian.before_or_equal' => 'Tanggal kematian tidak boleh di masa depan.',
        ];
    }

    /**
     * Memastikan laporan milik pemohon yang sedang masuk.
     */
    private function laporanMilik(Request $request, LaporanKematian $laporan): LaporanKematian
    {
        abort_unless((int) $laporan->id_pengguna === (int) $request->user()->id_pengguna, 404);

        return $laporan;
    }

    /**
     * Hanya pengajuan yang masih boleh diubah yang dapat diperbarui.
     */
    private function pastikanBolehDiubah(LaporanKematian $laporan): void
    {
        abort_unless($laporan->bolehDiubah(), 403, 'Pengajuan dengan status ini tidak dapat diubah.');
    }

    /**
     * Keempat berkas pendukung wajib sudah terunggah.
     */
    private function pastikanBerkasLengkap(LaporanKematian $laporan): void
    {
        $terunggah = $laporan->berkasPendukung()
            ->where('status_unggahan', BerkasPendukung::STATUS_BERHASIL)
            ->pluck('jenis_dokumen')
            ->all();

        $kurang = array_diff(BerkasPendukung::DAFTAR_JENIS, $terunggah);

        if ($kurang !== []) {
            throw ValidationException::withMessages([
                'berkas' => 'Keempat berkas pendukung wajib diunggah sebelum pengajuan dikirim.',
            ])->redirectTo($this->alamatLangkah($laporan, 3));
        }
    }

    /**
     * Membuang kolom di luar isian formulir dan nilai kosong.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rapikan(array $data): array
    {
        $data = array_intersect_key($data, array_flip(self::KOLOM_ISIAN));

        return array_map(
            static fn ($nilai) => is_string($nilai) ? trim($nilai) : $nilai,
            $data,
        );
    }

    /**
     * Nama berkas untuk ditampilkan, tanpa karakter berbahaya.
     */
    private function namaFileBersih(string $namaAsli, string $ekstensi): string
    {
        $nama = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($namaAsli)) ?? '';
        $nama = trim(Str::limit($nama, 200, ''));

        return $nama !== '' ? $nama : 'berkas.'.$ekstensi;
    }
}
