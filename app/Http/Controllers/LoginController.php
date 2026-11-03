<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Services\LayananAudit;
use App\Services\LayananPenguncian;
use App\Support\PengindeksNik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Controller masuk (login) SATUDUKA (use case 5.2.2).
 *
 * Satu formulir untuk semua peran: identitas 16 digit dibaca sebagai NIK
 * (Pemohon) dan identitas 18 digit dibaca sebagai NIP (Verifikator dan
 * Pejabat). Kegagalan login dihitung pada
 * cache ({@see LayananPenguncian}) dan seluruh aksi dicatat ke `log_audit`
 * melalui {@see LayananAudit}.
 */
class LoginController extends Controller
{
    /**
     * Pesan untuk kolom yang kosong (use case 5.2.2 alur 4.a.2).
     */
    private const PESAN_KOSONG = 'Harap isi semua field yang diperlukan';

    /**
     * Pesan ketika kredensial tidak cocok (user story 6.2.1).
     *
     * Catatan: use case 5.2.2 alur 4.d membedakan "NIK/NIP tidak ditemukan"
     * dan "Kata sandi tidak cocok", tetapi user story 6.2.1 memakai satu
     * pesan umum untuk keduanya. Pesan umum dipakai agar tidak membocorkan
     * keberadaan akun.
     */
    private const PESAN_SALAH = 'NIK/NIP atau kata sandi salah';

    /**
     * Pesan ketika akun baru dikunci (use case 5.2.2 alur 4.c.3).
     */
    private const PESAN_BARU_TERKUNCI = 'Terlalu banyak percobaan gagal. Akun Anda dikunci sementara. Coba lagi dalam 15 menit';

    /**
     * Pesan ketika akun sudah terkunci (use case 5.2.2 alur 4.c.6).
     */
    private const PESAN_SUDAH_TERKUNCI = 'Akun Anda dikunci sementara';

    /**
     * Hash bcrypt tetap untuk percobaan masuk saat akun tidak ditemukan.
     *
     * `Hash::check()` tetap dijalankan terhadap hash ini agar waktu respons
     * tidak membocorkan apakah sebuah NIK/NIP terdaftar. Nilainya hash dari
     * kata sandi acak yang tidak pernah dipakai; cost-nya sama dengan cost
     * hash kata sandi sebenarnya (bcrypt 12).
     */
    private const HASH_DUMMY = '$2y$12$NjrrAOmyVtqTmCrAQGjbKOpgo7Bnp3oD2yBi2pV604.YTWAJB9IEq';

    /**
     * Menampilkan halaman masuk.
     */
    public function tampilkan()
    {
        return view('autentikasi.masuk');
    }

    /**
     * Memproses percobaan masuk.
     */
    public function masuk(Request $request)
    {
        $valid = $request->validate([
            'identitas' => ['required', 'string'],
            'kata_sandi' => ['required', 'string'],
        ], [
            'identitas.required' => self::PESAN_KOSONG,
            'kata_sandi.required' => self::PESAN_KOSONG,
        ]);

        // Identitas dibersihkan menjadi digit saja (NIK/NIP kanonik) SEBELUM
        // dipakai sebagai kunci penguncian, kunci audit, dan pencarian. Dengan
        // begitu "3471-...", "3471 ...", dan "3471..." diperlakukan sebagai akun
        // yang sama sehingga penghitung kegagalan tidak bisa diakali format.
        $identitasBersih = PengindeksNik::angka(trim($valid['identitas']));
        $kunci = 'login:'.$identitasBersih;

        // Alur 4.c.5: percobaan masuk saat akun sedang terkunci.
        if (LayananPenguncian::terkunci($kunci)) {
            LayananAudit::catat(
                aksi: 'percobaan_masuk_saat_terkunci',
                objek: 'pengguna',
                detail: ['identitas' => $identitasBersih],
            );

            return back()
                ->withErrors(['identitas' => self::PESAN_SUDAH_TERKUNCI])
                ->onlyInput('identitas');
        }

        $pengguna = Pengguna::cariDenganNikAtauNip($identitasBersih);

        // Saat akun tidak ditemukan, tetap jalankan Hash::check terhadap hash
        // dummy agar waktu respons setara dengan kasus kata sandi salah (tidak
        // membocorkan keberadaan akun lewat selisih waktu).
        if ($pengguna === null) {
            Hash::check($valid['kata_sandi'], self::HASH_DUMMY);
            $kataSandiCocok = false;
        } else {
            $kataSandiCocok = Hash::check($valid['kata_sandi'], $pengguna->kata_sandi);
        }

        // Alur 4.b: kredensial tidak cocok, tambah hitungan percobaan gagal.
        if (! $kataSandiCocok) {
            $percobaanKe = LayananPenguncian::catatGagalDanKunciBilaPerlu(
                kunci: $kunci,
                maksGagal: LayananPenguncian::MAKS_GAGAL_LOGIN,
                pengguna: $pengguna,
            );

            LayananAudit::catat(
                aksi: 'masuk_gagal',
                objek: 'pengguna',
                idObjek: $pengguna?->id_pengguna,
                detail: ['identitas' => $identitasBersih, 'percobaan_ke' => $percobaanKe],
                pengguna: $pengguna,
            );

            // Alur 4.c: penguncian terjadi pada percobaan gagal yang ke-N.
            if (LayananPenguncian::terkunci($kunci)) {
                return back()
                    ->withErrors(['identitas' => self::PESAN_BARU_TERKUNCI])
                    ->onlyInput('identitas');
            }

            return back()
                ->withErrors(['identitas' => self::PESAN_SALAH])
                ->onlyInput('identitas');
        }

        // Alur normal: masuk berhasil, hitungan kegagalan direset.
        Auth::login($pengguna, $request->boolean('ingat_saya'));
        LayananPenguncian::buka($kunci);

        LayananAudit::catat(
            aksi: 'masuk',
            objek: 'pengguna',
            idObjek: $pengguna->id_pengguna,
            detail: ['peran' => $pengguna->peran, 'ingat_saya' => $request->boolean('ingat_saya')],
            pengguna: $pengguna,
        );

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Memproses keluar dari sistem.
     */
    public function keluar(Request $request)
    {
        $pengguna = $request->user();

        LayananAudit::catat(
            aksi: 'keluar',
            objek: 'pengguna',
            idObjek: $pengguna->id_pengguna,
            pengguna: $pengguna,
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk')->with('sukses', 'Anda telah keluar dari sistem.');
    }
}
