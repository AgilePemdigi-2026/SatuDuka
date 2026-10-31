<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use App\Services\LayananAudit;
use App\Services\LayananPenguncian;
use App\Support\PengindeksNik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Controller pengaturan ulang kata sandi (use case 5.2.2 alur 4.a).
 *
 * Penyesuaian terhadap use case: pengguna memasukkan
 * NIK/NIP, sistem mencari surel TERDAFTAR milik akun tersebut, lalu mengirim
 * tautan pengaturan ulang ke surel itu (MAIL_MAILER=log untuk demo). Formulir
 * kata sandi baru dibuka melalui tautan pada surel, bukan langsung setelah
 * menekan "Kirim".
 */
class AturUlangKataSandiController extends Controller
{
    /**
     * Pesan untuk kolom yang kosong.
     */
    private const PESAN_KOSONG = 'Harap isi semua field yang diperlukan';

    /**
     * Pesan kata sandi baru yang tidak memenuhi NFR-01.
     */
    private const PESAN_KATA_SANDI_LEMAH = 'Kata sandi minimal 8 karakter dan mengandung huruf serta angka';

    /**
     * Pesan netral yang tidak membocorkan apakah identitas terdaftar.
     */
    private const PESAN_NETRAL = 'Jika data yang Anda masukkan terdaftar, tautan pengaturan ulang telah dikirim ke surel akun.';

    /**
     * Menampilkan formulir permintaan tautan pengaturan ulang.
     */
    public function tampilkanLupa()
    {
        return view('autentikasi.lupa_kata_sandi');
    }

    /**
     * Memverifikasi NIK/NIP dan mengirim tautan ke surel terdaftar.
     */
    public function kirimTautan(Request $request)
    {
        $valid = $request->validate([
            'identitas' => ['required', 'string'],
        ], [
            'identitas.required' => self::PESAN_KOSONG,
        ]);

        $identitas = trim($valid['identitas']);
        $pengguna = Pengguna::cariDenganNikAtauNip($identitas);

        if ($pengguna === null) {
            LayananAudit::catat(
                aksi: 'minta_atur_ulang_kata_sandi_gagal',
                objek: 'pengguna',
                detail: ['alasan' => 'tidak ditemukan'],
            );

            // Pesan netral agar tidak membocorkan apakah identitas terdaftar.
            return back()
                ->with('info', self::PESAN_NETRAL)
                ->onlyInput('identitas');
        }

        // Tautan dikirim ke surel terdaftar akun, bukan surel yang diketik.
        $status = Password::sendResetLink(['email' => $pengguna->email]);

        LayananAudit::catat(
            aksi: 'minta_atur_ulang_kata_sandi',
            objek: 'pengguna',
            idObjek: $pengguna->id_pengguna,
            pengguna: $pengguna,
        );

        // RESET_THROTTLED hanya muncul bila akun benar-benar ada dan baru saja
        // meminta tautan. Menampilkan pesan berbeda di sini akan membocorkan
        // keberadaan akun, jadi tetap pakai pesan netral yang sama.
        if (in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true)) {
            return back()->with('sukses', self::PESAN_NETRAL);
        }

        return back()->withErrors(['identitas' => __($status)]);
    }

    /**
     * Menampilkan formulir kata sandi baru dari tautan surel.
     */
    public function tampilkanAturUlang(Request $request, string $token)
    {
        return view('autentikasi.atur_ulang_kata_sandi', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Menyimpan kata sandi baru.
     */
    public function aturUlang(Request $request)
    {
        $valid = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'kata_sandi' => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
            'konfirmasi_kata_sandi' => ['required', 'string', 'same:kata_sandi'],
        ], [
            'token.required' => self::PESAN_KOSONG,
            'email.required' => self::PESAN_KOSONG,
            'kata_sandi.required' => self::PESAN_KOSONG,
            'kata_sandi.min' => self::PESAN_KATA_SANDI_LEMAH,
            'kata_sandi.regex' => self::PESAN_KATA_SANDI_LEMAH,
            'konfirmasi_kata_sandi.required' => self::PESAN_KOSONG,
            'konfirmasi_kata_sandi.same' => 'Kata sandi tidak cocok',
        ]);

        $status = Password::reset(
            [
                'email' => $valid['email'],
                'password' => $valid['kata_sandi'],
                'password_confirmation' => $valid['konfirmasi_kata_sandi'],
                'token' => $valid['token'],
            ],
            function (Pengguna $pengguna, string $kataSandiBaru) use ($request) {
                $pengguna->kata_sandi = $kataSandiBaru;
                // Perbarui remember_token agar cookie "Ingat Saya" lama tidak
                // lagi sah setelah kata sandi diubah.
                $pengguna->setRememberToken(Str::random(60));
                $pengguna->save();

                // Cabut semua sesi lain milik pengguna ini (hanya bila sesi
                // disimpan di tabel). Ini memaksa perangkat lain login ulang.
                if (config('session.driver') === 'database') {
                    DB::table((string) config('session.table', 'sessions'))
                        ->where('user_id', $pengguna->getAuthIdentifier())
                        ->where('id', '!=', $request->session()->getId())
                        ->delete();
                }

                // Reset hitungan gagal login karena pengguna baru saja
                // membuktikan kepemilikan akun. Kunci penguncian memakai
                // identitas kanonik (digit saja) sehingga semua format yang
                // mungkin diketik (polos, tanda hubung, spasi) ikut terbuka.
                foreach ([$pengguna->nik, $pengguna->nip] as $identitas) {
                    if ($identitas === null || $identitas === '') {
                        continue;
                    }

                    LayananPenguncian::buka('login:'.PengindeksNik::angka((string) $identitas));
                }

                LayananAudit::catat(
                    aksi: 'atur_ulang_kata_sandi',
                    objek: 'pengguna',
                    idObjek: $pengguna->id_pengguna,
                    pengguna: $pengguna,
                );
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('masuk')->with('sukses', 'Kata sandi berhasil direset. Silakan login.')
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
    }
}
