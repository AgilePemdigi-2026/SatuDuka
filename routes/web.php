<?php

use App\Http\Controllers\AturUlangKataSandiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PusatNotifikasiController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RegistrasiController;
use App\Http\Controllers\RekonsiliasiController;
use App\Http\Controllers\StatusPengajuanController;
use App\Http\Controllers\TandaTanganController;
use App\Http\Controllers\VerifikasiBerkasController;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman awal.
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('masuk');
})->name('beranda');

// Halaman untuk tamu yang belum login.
Route::middleware('guest')->group(function () {
    Route::get('/registrasi', [RegistrasiController::class, 'tampilkan'])->name('registrasi');
    Route::post('/registrasi', [RegistrasiController::class, 'daftar'])
        ->middleware('throttle:registrasi')
        ->name('registrasi.simpan');

    Route::get('/masuk', [LoginController::class, 'tampilkan'])->name('masuk');
    Route::post('/masuk', [LoginController::class, 'masuk'])
        ->middleware(['throttle:masuk', 'throttle:masuk-ip'])
        ->name('masuk.coba');

    Route::get('/lupa-kata-sandi', [AturUlangKataSandiController::class, 'tampilkanLupa'])->name('lupa_kata_sandi');
    Route::post('/lupa-kata-sandi', [AturUlangKataSandiController::class, 'kirimTautan'])
        ->middleware('throttle:lupa-kata-sandi')
        ->name('lupa_kata_sandi.kirim');

    Route::get('/atur-ulang-kata-sandi/{token}', [AturUlangKataSandiController::class, 'tampilkanAturUlang'])
        ->name('atur_ulang_kata_sandi');
    Route::post('/atur-ulang-kata-sandi', [AturUlangKataSandiController::class, 'aturUlang'])
        ->middleware('throttle:atur-ulang-kata-sandi')
        ->name('atur_ulang_kata_sandi.simpan');
});

// Keluar dari sistem.
Route::post('/keluar', [LoginController::class, 'keluar'])
    ->middleware('auth')
    ->name('keluar');

// Dashboard sesuai peran.
Route::get('/dashboard', [DashboardController::class, 'tampilkan'])
    ->middleware('auth')
    ->name('dashboard');

// Pengelolaan profil seluruh peran.
Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfilController::class, 'tampilkan'])->name('profil.tampilkan');
    Route::put('/profil', [ProfilController::class, 'simpan'])->name('profil.simpan');
    Route::post('/profil/foto', [ProfilController::class, 'unggahFoto'])->name('profil.foto');
    Route::get('/profil/foto', [ProfilController::class, 'tampilkanFoto'])->name('profil.foto.tampil');
    Route::put('/profil/kata-sandi', [ProfilController::class, 'ubahKataSandi'])->name('profil.kata_sandi');
});

// Pengajuan laporan kematian oleh Pemohon (use case 5.2.3 & 5.2.5).
Route::middleware(['auth', 'peran:pemohon'])->group(function () {
    Route::get('/laporan/baru', [LaporanController::class, 'tampilkanBaru'])->name('laporan.baru');
    Route::post('/laporan/draf', [LaporanController::class, 'simpanDraf'])->name('laporan.draf');

    Route::get('/laporan/{laporan}/ubah', [LaporanController::class, 'tampilkanUbah'])->name('laporan.ubah');
    Route::post('/laporan/{laporan}/draf', [LaporanController::class, 'simpanDraf'])->name('laporan.draf.ubah');
    Route::post('/laporan/{laporan}/periksa-ai', [LaporanController::class, 'periksaAi'])
        ->middleware('throttle:analisis-ai')
        ->name('laporan.periksa_ai');
    Route::post('/laporan/{laporan}/kirim', [LaporanController::class, 'kirim'])->name('laporan.kirim');
    Route::post('/laporan/{laporan}/berkas', [LaporanController::class, 'unggahBerkas'])
        ->middleware('throttle:unggah-berkas')
        ->name('laporan.berkas.unggah');
    Route::get('/laporan/{laporan}/berkas/{berkas}', [LaporanController::class, 'pratinjauBerkas'])->name('laporan.berkas');
    Route::get('/laporan/{laporan}', [LaporanController::class, 'lihat'])->name('laporan.lihat');

    // Pemantauan status pengajuan (use case 5.2.5).
    Route::get('/pengajuan/status', [StatusPengajuanController::class, 'daftar'])->name('pengajuan.status');
    Route::get('/pengajuan/status/{laporan}', [StatusPengajuanController::class, 'detail'])->name('pengajuan.detail');
    Route::get('/pengajuan/{laporan}/dokumen', [StatusPengajuanController::class, 'unduh'])->name('pengajuan.unduh');

    // Pusat Notifikasi Pengguna/Pemohon
    Route::get('/notifikasi', [PusatNotifikasiController::class, 'indeks'])->name('notifikasi.index');
    Route::post('/notifikasi/tandai-semua', [PusatNotifikasiController::class, 'tandaiSemuaDibaca'])->name('notifikasi.tandai_semua');
});

// Alur utama verifikasi berkas: persetujuan, perbaikan, revisi, dan anomali.
Route::middleware(['auth', 'peran:verifikator'])
    ->prefix('verifikasi-berkas')
    ->name('verifikasi.')
    ->group(function () {
        Route::get('/', [VerifikasiBerkasController::class, 'index'])->name('index');
        Route::get('/{laporan}', [VerifikasiBerkasController::class, 'show'])->name('show');
        Route::get('/{laporan}/berkas/{berkas}', [VerifikasiBerkasController::class, 'pratinjau'])->name('pratinjau');
        Route::get('/{laporan}/berkas/{berkas}/unduh', [VerifikasiBerkasController::class, 'unduh'])->name('unduh');
        Route::post('/{laporan}/analisis-ai', [VerifikasiBerkasController::class, 'analisis'])
            ->middleware('throttle:analisis-ai')
            ->name('analisis_ai');
        Route::post('/{laporan}/ringkasan-ai', [VerifikasiBerkasController::class, 'ringkasanAi'])
            ->middleware('throttle:analisis-ai')
            ->name('ringkasan_ai');
        Route::post('/{laporan}/setujui', [VerifikasiBerkasController::class, 'setujui'])->name('setujui');
        Route::post('/{laporan}/perbaikan', [VerifikasiBerkasController::class, 'mintaPerbaikan'])->name('perbaikan');
        Route::post('/{laporan}/revisi', [VerifikasiBerkasController::class, 'mintaRevisi'])->name('revisi');
        Route::post('/{laporan}/anomali', [VerifikasiBerkasController::class, 'tandaiAnomali'])->name('anomali');
        Route::post('/{laporan}/tolak-fatal', [VerifikasiBerkasController::class, 'tolakFatal'])->name('tolak_fatal');
    });

// Rekonsiliasi anomali data kependudukan.
Route::middleware(['auth', 'peran:verifikator'])
    ->prefix('rekonsiliasi')
    ->name('rekonsiliasi.')
    ->group(function () {
        Route::get('/', [RekonsiliasiController::class, 'index'])->name('index');
        Route::get('/{laporan}', [RekonsiliasiController::class, 'show'])->name('show');
        Route::post('/{laporan}/selesaikan', [RekonsiliasiController::class, 'selesaikan'])->name('selesaikan');
        Route::post('/{laporan}/tolak-fatal', [RekonsiliasiController::class, 'tolakFatal'])->name('tolak_fatal');
    });

// Penandatanganan dokumen dengan TTE oleh Pejabat.
Route::middleware(['auth', 'peran:pejabat'])
    ->prefix('tanda-tangan')
    ->name('tanda_tangan.')
    ->group(function () {
        Route::get('/', [TandaTanganController::class, 'index'])->name('index');
        Route::get('/{laporan}', [TandaTanganController::class, 'show'])->name('show');
        Route::get('/{laporan}/unduh', [TandaTanganController::class, 'unduh'])->name('unduh');
        Route::post('/{laporan}/tandatangani', [TandaTanganController::class, 'tandatangani'])
            ->middleware('throttle:tandatangani')
            ->name('tandatangani');
        Route::post('/{laporan}/kembalikan', [TandaTanganController::class, 'kembalikan'])->name('kembalikan');
        Route::post('/{laporan}/terbitkan', [TandaTanganController::class, 'terbitkan'])
            ->middleware('throttle:terbitkan')
            ->name('terbitkan');
    });

Route::redirect('/pengajuan-laporan', '/laporan/baru');