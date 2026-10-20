<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Services\LayananAudit;
use App\Support\DataSIAK;
use Illuminate\Http\Request;

/**
 * Controller registrasi akun Pemohon (use case 5.2.1).
 *
 * Hanya Pemohon yang mendaftar sendiri; akun Verifikator dan Pejabat dibuat
 * oleh administrasi (seed), karena keduanya login dengan NIP.
 *
 * Penyimpangan dari use case 5.2.1 (keputusan pemilik proyek): kolom alamat
 * surel WAJIB diisi karena `pengguna.email` NOT NULL UNIQUE dan dipakai untuk
 * pengaturan ulang kata sandi. Nama lengkap diambil dari data kependudukan
 * (SIAK) berdasarkan NIK sehingga tidak perlu diisi manual.
 */
class RegistrasiController extends Controller
{
    /**
     * Pesan untuk kolom yang kosong (use case 5.2.1 alur 3.a.2).
     */
    private const PESAN_KOSONG = 'Harap isi semua field yang diperlukan';

    /**
     * Pesan kata sandi lemah (NFR-01 dan use case 5.2.1 alur 3.b.3).
     */
    private const PESAN_KATA_SANDI_LEMAH = 'Kata sandi minimal 8 karakter dan mengandung huruf serta angka';

    /**
     * Menampilkan halaman registrasi.
     */
    public function tampilkan()
    {
        return view('autentikasi.daftar');
    }

    /**
     * Memproses pendaftaran akun Pemohon baru.
     */
    public function daftar(Request $request)
    {
        $valid = $request->validate([
            'nik' => ['required', 'digits:16', 'unique:pengguna,nik'],
            'email' => ['required', 'email:filter', 'max:100', 'unique:pengguna,email'],
            'kata_sandi' => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
            'konfirmasi_kata_sandi' => ['required', 'string', 'same:kata_sandi'],
        ], [
            'nik.required' => self::PESAN_KOSONG,
            'nik.digits' => 'NIK tidak valid',
            'nik.unique' => 'NIK sudah terdaftar',
            'email.required' => self::PESAN_KOSONG,
            'email.email' => 'Format alamat surel tidak valid',
            'email.max' => 'Alamat surel terlalu panjang (maksimal 100 karakter)',
            'email.unique' => 'Surel sudah terdaftar',
            'kata_sandi.required' => self::PESAN_KOSONG,
            'kata_sandi.min' => self::PESAN_KATA_SANDI_LEMAH,
            'kata_sandi.regex' => self::PESAN_KATA_SANDI_LEMAH,
            'konfirmasi_kata_sandi.required' => self::PESAN_KOSONG,
            'konfirmasi_kata_sandi.same' => 'Kata sandi tidak cocok',
        ]);

        // Use case 5.2.1 langkah 5: memverifikasi NIK pada data kependudukan.
        // Sementara memakai pembaca data dummy SIAK; Fase 6 mengalihkannya ke
        // SIAKService.
        $dataSiak = DataSIAK::cariNik($valid['nik']);

        if ($dataSiak === null) {
            return back()
                ->withErrors(['nik' => 'NIK tidak ditemukan pada data kependudukan'])
                ->withInput($request->only('nik', 'email'));
        }

        $pengguna = Pengguna::create([
            'nama_lengkap' => $dataSiak['nama_lengkap'],
            'nik' => $valid['nik'],
            'email' => $valid['email'],
            'kata_sandi' => $valid['kata_sandi'],
            'peran' => Pengguna::PERAN_PEMOHON,
        ]);

        LayananAudit::catat(
            aksi: 'registrasi',
            objek: 'pengguna',
            idObjek: $pengguna->id_pengguna,
            detail: ['peran' => $pengguna->peran, 'nama_dari_siak' => true],
            pengguna: $pengguna,
        );

        // Use case 5.2.1 langkah 7: notifikasi sukses lalu halaman Login.
        return redirect()->route('masuk')->with('sukses', 'Registrasi berhasil!');
    }
}
