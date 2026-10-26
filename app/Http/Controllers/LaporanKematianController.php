<?php

namespace App\Http\Controllers;

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class LaporanKematianController extends Controller
{
    /**
     * Menampilkan form pengajuan untuk Pemohon.
     */
    public function create(Request $request): View
    {
        $pemohon = $request->user();

        abort_unless(
            $pemohon instanceof Pengguna && $pemohon->adalahPemohon(),
            403
        );

        return view('laporan.create', [
            'pemohon' => $pemohon,
        ]);
    }

    /**
     * Menyimpan laporan dan berkas pendukung ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $pemohon = $request->user();

        abort_unless(
            $pemohon instanceof Pengguna && $pemohon->adalahPemohon(),
            403
        );

        $data = $request->validate([
            'nama_pelapor' => ['required', 'string', 'max:100'],
            'nik_pelapor' => ['required', 'digits:16'],
            'kontak_pelapor' => ['nullable', 'string', 'max:15'],
            'email_pelapor' => ['required', 'email', 'max:100'],
            'hubungan' => ['required', 'string', 'max:50'],

            'nama_almarhum' => ['required', 'string', 'max:100'],
            'nik_almarhum' => ['required', 'digits:16'],
            'jenis_kelamin' => [
                'required',
                Rule::in(['Laki-laki', 'Perempuan']),
            ],
            'tanggal_kematian' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'waktu_kematian' => ['required', 'date_format:H:i'],
            'jenis_tempat' => [
                'required',
                Rule::in(['Rumah Sakit', 'Kediaman', 'Lainnya']),
            ],
            'tempat_kematian' => ['required', 'string', 'max:100'],
            'sebab_kematian' => ['required', 'string', 'max:1000'],

            'surat_kematian' => [
                'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048',
            ],
            'ktp_almarhum' => [
                'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048',
            ],
            'ktp_pelapor' => [
                'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048',
            ],
            'kartu_keluarga' => [
                'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048',
            ],

            'pernyataan' => ['required', 'accepted'],
        ]);

        /*
         * Identitas pelapor harus sesuai dengan akun yang sedang login.
         * Nama, NIK, email, dan nomor telepon pada form merupakan
         * informasi profil Pemohon, bukan identitas yang bebas diganti.
         */
        $kesalahanProfil = [];

        if (trim($data['nama_pelapor']) !== trim($pemohon->nama_lengkap)) {
            $kesalahanProfil['nama_pelapor'] =
                'Nama pelapor harus sesuai dengan profil akun.';
        }

        if ((string) $data['nik_pelapor'] !== (string) $pemohon->nik) {
            $kesalahanProfil['nik_pelapor'] =
                'NIK pelapor harus sesuai dengan akun Pemohon yang login.';
        }

        if (strtolower(trim($data['email_pelapor'])) !==
            strtolower(trim($pemohon->email))) {
            $kesalahanProfil['email_pelapor'] =
                'Email harus sesuai dengan profil akun.';
        }

        if ((string) ($data['kontak_pelapor'] ?? '') !==
            (string) ($pemohon->no_telepon ?? '')) {
            $kesalahanProfil['kontak_pelapor'] =
                'Nomor telepon harus sesuai dengan profil akun.';
        }

        if ($kesalahanProfil !== []) {
            throw ValidationException::withMessages($kesalahanProfil);
        }

        $noPengajuan = $this->buatNomorPengajuan();
        $pathTersimpan = [];

        $jenisBerkas = [
            'surat_kematian' => BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
            'ktp_almarhum' => BerkasPendukung::JENIS_KTP_ALMARHUM,
            'ktp_pelapor' => BerkasPendukung::JENIS_KTP_PELAPOR,
            'kartu_keluarga' => BerkasPendukung::JENIS_KARTU_KELUARGA,
        ];

        try {
            $laporan = DB::transaction(function () use (
                $request,
                $pemohon,
                $data,
                $noPengajuan,
                $jenisBerkas,
                &$pathTersimpan
            ) {
                $laporan = LaporanKematian::create([
                    'id_pengguna' => $pemohon->id_pengguna,
                    'no_pengajuan' => $noPengajuan,
                    'nama_pelapor' => $pemohon->nama_lengkap,
                    'hubungan_pelapor' => $data['hubungan'],
                    'nama_almarhum' => $data['nama_almarhum'],
                    'nik_almarhum' => $data['nik_almarhum'],
                    'tanggal_kematian' => $data['tanggal_kematian'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'waktu_kematian' => $data['waktu_kematian'],
                    'jenis_tempat' => $data['jenis_tempat'],
                    'tempat_kematian' => $data['tempat_kematian'],
                    'sebab_kematian' => $data['sebab_kematian'],
                    'status_pengajuan' =>
                        LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                    'tanggal_pengajuan' => now(),
                ]);

                foreach ($jenisBerkas as $field => $jenis) {
                    $file = $request->file($field);

                    $path = $file->store(
                        'pengajuan/' . $noPengajuan,
                        'local'
                    );

                    if (!is_string($path)) {
                        throw new RuntimeException(
                            'Berkas gagal disimpan. Silakan coba kembali.'
                        );
                    }

                    $pathTersimpan[] = $path;

                    BerkasPendukung::create([
                        'id_laporan' => $laporan->id_laporan,
                        'jenis_dokumen' => $jenis,
                        'nama_file' => Str::limit(
                            $file->getClientOriginalName(),
                            255,
                            ''
                        ),
                        'path_file' => $path,
                        'status_unggahan' =>
                            BerkasPendukung::STATUS_BERHASIL,
                        'tanggal_unggah' => now(),
                    ]);
                }

                return $laporan;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($pathTersimpan);

            throw $exception;
        }

        return redirect()
            ->route('laporan.create')
            ->with('success', 'Pengajuan laporan berhasil dikirim.')
            ->with('nomor_pengajuan', $laporan->no_pengajuan);
    }

    /**
     * Membuat nomor pengajuan unik maksimal 30 karakter.
     */
    private function buatNomorPengajuan(): string
    {
        do {
            $nomor = 'SKM-' . now()->format('YmdHis') . '-'
                . Str::upper(Str::random(4));
        } while (
            LaporanKematian::where('no_pengajuan', $nomor)->exists()
        );

        return $nomor;
    }
}
