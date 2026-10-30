<?php

namespace App\Http\Controllers;

use App\Services\LayananAudit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Pengelolaan profil pengguna (use case 5.2.4): data diri, foto, dan kata sandi.
 *
 * Berlaku untuk seluruh peran. NIK (Pemohon) dan NIP (Verifikator/Pejabat)
 * hanya ditampilkan dan tidak pernah diisi dari masukan formulir agar data
 * kependudukan/kepegawaian tetap utuh.
 */
class ProfilController extends Controller
{
    /**
     * Pesan kegagalan unggah foto (use case 5.2.4 alur alternatif).
     */
    private const PESAN_FOTO = 'Ukuran berkas melebihi batas (maks. 2MB) atau format tidak didukung (gunakan JPG/PNG).';

    /**
     * Pesan kata sandi lemah (sama dengan aturan registrasi).
     */
    private const PESAN_KATA_SANDI_LEMAH = 'Kata sandi minimal 8 karakter dan harus mengandung huruf serta angka';

    /**
     * Pesan kegagalan koneksi basis data.
     */
    private const PESAN_KONEKSI = 'Gagal terhubung ke server. Periksa koneksi internet Anda dan coba lagi.';

    /**
     * Pesan surel/telepon yang sudah dipakai pengguna lain.
     */
    private const PESAN_DUPLIKAT = 'Email / Nomor Telepon sudah digunakan oleh pengguna lain.';

    /**
     * Menampilkan halaman Kelola Profil.
     */
    public function tampilkan(Request $request)
    {
        return view('profil.tampilkan', [
            'pengguna' => $request->user(),
        ]);
    }

    /**
     * Menyimpan perubahan data diri.
     */
    public function simpan(Request $request)
    {
        $pengguna = $request->user();

        $valid = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email:filter',
                'max:100',
                Rule::unique('pengguna', 'email')->ignore($pengguna->id_pengguna, 'id_pengguna'),
            ],
            'no_telepon' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9+\-() ]+$/',
                Rule::unique('pengguna', 'no_telepon')->ignore($pengguna->id_pengguna, 'id_pengguna'),
            ],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ], [
            'nama_lengkap.required' => 'Harap isi semua field yang diperlukan',
            'nama_lengkap.max' => 'Nama lengkap terlalu panjang (maksimal 100 karakter)',
            'email.required' => 'Harap isi semua field yang diperlukan',
            'email.email' => 'Format alamat surel tidak valid',
            'email.max' => 'Alamat surel terlalu panjang (maksimal 100 karakter)',
            'email.unique' => self::PESAN_DUPLIKAT,
            'no_telepon.max' => 'Nomor telepon terlalu panjang (maksimal 15 karakter)',
            'no_telepon.regex' => 'Format nomor telepon tidak valid',
            'no_telepon.unique' => self::PESAN_DUPLIKAT,
            'alamat.max' => 'Alamat terlalu panjang (maksimal 1000 karakter)',
        ]);

        // Hanya kolom data diri yang boleh berubah; NIK/NIP diabaikan.
        $pengguna->fill($valid);
        $kolomBerubah = array_keys($pengguna->getDirty());

        try {
            $pengguna->save();
        } catch (QueryException $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['umum' => self::PESAN_KONEKSI]);
        }

        LayananAudit::catat(
            aksi: 'memperbarui_profil',
            objek: 'pengguna',
            idObjek: $pengguna->id_pengguna,
            detail: ['kolom' => $kolomBerubah],
            pengguna: $pengguna,
            hasil: 'Sukses',
        );

        return redirect()
            ->route('profil.tampilkan')
            ->with('sukses', 'Profil berhasil diperbarui.');
    }

    /**
     * Mengunggah atau mengganti foto profil.
     */
    public function unggahFoto(Request $request)
    {
        $pengguna = $request->user();

        $request->validate([
            'foto' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'foto.required' => self::PESAN_FOTO,
            'foto.file' => self::PESAN_FOTO,
            'foto.image' => self::PESAN_FOTO,
            'foto.mimes' => self::PESAN_FOTO,
            'foto.max' => self::PESAN_FOTO,
        ]);

        $berkas = $request->file('foto');
        $ekstensi = strtolower($berkas->extension() ?: 'jpg');
        $fotoLama = $pengguna->path_foto;

        // Nama berkas dibuat server agar tidak mengikuti nama unggahan pengguna.
        $path = Storage::disk('local')->putFileAs(
            'foto_profil/'.$pengguna->id_pengguna,
            $berkas,
            Str::random(40).'.'.$ekstensi,
        );

        $pengguna->path_foto = $path;

        try {
            $pengguna->save();
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($path);
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['umum' => self::PESAN_KONEKSI]);
        }

        // Foto lama baru dihapus setelah foto baru tersimpan.
        if ($fotoLama !== null && $fotoLama !== '' && $fotoLama !== $path
            && Storage::disk('local')->exists($fotoLama)) {
            Storage::disk('local')->delete($fotoLama);
        }

        LayananAudit::catat(
            aksi: 'mengubah_foto_profil',
            objek: 'pengguna',
            idObjek: $pengguna->id_pengguna,
            detail: [],
            pengguna: $pengguna,
            hasil: 'Sukses',
        );

        return redirect()
            ->route('profil.tampilkan')
            ->with('sukses', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Menampilkan foto profil dari disk privat, atau inisial bila belum ada.
     */
    public function tampilkanFoto(Request $request)
    {
        $pengguna = $request->user();

        if ($pengguna->path_foto !== null && $pengguna->path_foto !== ''
            && Storage::disk('local')->exists($pengguna->path_foto)) {
            $response = Storage::disk('local')->response($pengguna->path_foto);

            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Cache-Control', 'no-store, private');

            return $response;
        }

        return response(
            view('profil.inisial', ['inisial' => $pengguna->inisial()]),
            200,
            ['Content-Type' => 'image/svg+xml'],
        );
    }

    /**
     * Mengubah kata sandi setelah memverifikasi kata sandi lama.
     */
    public function ubahKataSandi(Request $request)
    {
        $pengguna = $request->user();

        $valid = $request->validate([
            'kata_sandi_lama' => ['required', 'string'],
            'kata_sandi_baru' => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
            'konfirmasi_kata_sandi' => ['required', 'string', 'same:kata_sandi_baru'],
        ], [
            'kata_sandi_lama.required' => 'Harap isi semua field yang diperlukan',
            'kata_sandi_baru.required' => 'Harap isi semua field yang diperlukan',
            'kata_sandi_baru.min' => self::PESAN_KATA_SANDI_LEMAH,
            'kata_sandi_baru.regex' => self::PESAN_KATA_SANDI_LEMAH,
            'konfirmasi_kata_sandi.required' => 'Harap isi semua field yang diperlukan',
            'konfirmasi_kata_sandi.same' => 'Konfirmasi kata sandi tidak cocok',
        ]);

        if (! Hash::check($valid['kata_sandi_lama'], $pengguna->kata_sandi)) {
            return back()->withErrors(['kata_sandi_lama' => 'Kata sandi lama tidak sesuai']);
        }

        try {
            // Cast `hashed` pada model yang melakukan hash; jangan Hash::make lagi.
            $pengguna->kata_sandi = $valid['kata_sandi_baru'];
            $pengguna->save();
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['umum' => self::PESAN_KONEKSI]);
        }

        LayananAudit::catat(
            aksi: 'mengubah_kata_sandi',
            objek: 'pengguna',
            idObjek: $pengguna->id_pengguna,
            detail: [],
            pengguna: $pengguna,
            hasil: 'Sukses',
        );

        $request->session()->regenerate();

        return redirect()
            ->route('profil.tampilkan')
            ->with('sukses', 'Kata sandi berhasil diperbarui');
    }
}
