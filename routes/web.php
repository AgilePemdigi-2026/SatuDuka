<?php

use App\Http\Controllers\AturUlangKataSandiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RegistrasiController;
use App\Http\Controllers\RekonsiliasiController;
use App\Http\Controllers\StatusPengajuanController;
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
    // Registrasi akun pemohon.
    Route::get('/registrasi', [RegistrasiController::class, 'tampilkan'])
        ->name('registrasi');

    Route::post('/registrasi', [RegistrasiController::class, 'daftar'])
        ->name('registrasi.simpan');

    // Login.
    Route::get('/masuk', [LoginController::class, 'tampilkan'])
        ->name('masuk');

    Route::post('/masuk', [LoginController::class, 'masuk'])
        ->name('masuk.coba');

    // Permintaan pengaturan ulang kata sandi.
    Route::get('/lupa-kata-sandi', [
        AturUlangKataSandiController::class,
        'tampilkanLupa',
    ])->name('lupa_kata_sandi');

    Route::post('/lupa-kata-sandi', [
        AturUlangKataSandiController::class,
        'kirimTautan',
    ])->name('lupa_kata_sandi.kirim');

    // Form pengaturan ulang kata sandi.
    Route::get('/atur-ulang-kata-sandi/{token}', [
        AturUlangKataSandiController::class,
        'tampilkanAturUlang',
    ])->name('atur_ulang_kata_sandi');

    Route::post('/atur-ulang-kata-sandi', [
        AturUlangKataSandiController::class,
        'aturUlang',
    ])->name('atur_ulang_kata_sandi.simpan');
});

// Keluar dari sistem.
Route::post('/keluar', [LoginController::class, 'keluar'])
    ->middleware('auth')
    ->name('keluar');

// Dashboard sesuai peran.
Route::get('/dashboard', [DashboardController::class, 'tampilkan'])
    ->middleware('auth')
    ->name('dashboard');

// Pengelolaan profil seluruh peran (use case 5.2.4).
Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfilController::class, 'tampilkan'])->name('profil.tampilkan');
    Route::put('/profil', [ProfilController::class, 'simpan'])->name('profil.simpan');
    Route::post('/profil/foto', [ProfilController::class, 'unggahFoto'])->name('profil.foto');
    Route::get('/profil/foto', [ProfilController::class, 'tampilkanFoto'])->name('profil.foto.tampil');
    Route::put('/profil/kata-sandi', [ProfilController::class, 'ubahKataSandi'])->name('profil.kata_sandi');
});

// Pengajuan laporan kematian oleh Pemohon (use case 5.2.3).
Route::middleware(['auth', 'peran:pemohon'])->group(function () {
    Route::get('/laporan/baru', [LaporanController::class, 'tampilkanBaru'])->name('laporan.baru');
    Route::post('/laporan/draf', [LaporanController::class, 'simpanDraf'])->name('laporan.draf');

    Route::get('/laporan/{laporan}/ubah', [LaporanController::class, 'tampilkanUbah'])->name('laporan.ubah');
    Route::post('/laporan/{laporan}/draf', [LaporanController::class, 'simpanDraf'])->name('laporan.draf.ubah');
    Route::post('/laporan/{laporan}/periksa-ai', [LaporanController::class, 'periksaAi'])->name('laporan.periksa_ai');
    Route::post('/laporan/{laporan}/kirim', [LaporanController::class, 'kirim'])->name('laporan.kirim');
    Route::post('/laporan/{laporan}/berkas', [LaporanController::class, 'unggahBerkas'])->name('laporan.berkas.unggah');
    Route::get('/laporan/{laporan}/berkas/{berkas}', [LaporanController::class, 'pratinjauBerkas'])->name('laporan.berkas');
    Route::get('/laporan/{laporan}', [LaporanController::class, 'lihat'])->name('laporan.lihat');

    // Pemantauan status pengajuan (use case 5.2.5).
    Route::get('/pengajuan/status', [StatusPengajuanController::class, 'daftar'])->name('pengajuan.status');
    Route::get('/pengajuan/status/{laporan}', [StatusPengajuanController::class, 'detail'])->name('pengajuan.detail');
    Route::get('/pengajuan/{laporan}/dokumen', [StatusPengajuanController::class, 'unduh'])->name('pengajuan.unduh');
});

// Pengalihan rute verifikasi lama ke alur verifikasi-berkas.
Route::middleware(['auth', 'peran:verifikator'])->group(function () {
    Route::get('/verifikasi', fn () => redirect()->route('verifikasi.index'));
    Route::get('/verifikasi/{laporan}/berkas/{berkas}', fn (LaporanKematian $laporan, BerkasPendukung $berkas) => redirect()->route('verifikasi.pratinjau', [$laporan, $berkas]));
    Route::post('/verifikasi/{laporan}/analisis-ai', fn (LaporanKematian $laporan) => redirect()->route('verifikasi.show', $laporan));
    Route::post('/verifikasi/{laporan}/ringkasan-ai', fn (LaporanKematian $laporan) => redirect()->route('verifikasi.show', $laporan));
    Route::get('/verifikasi/{laporan}', fn (LaporanKematian $laporan) => redirect()->route('verifikasi.show', $laporan));
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
        Route::post('/{laporan}/analisis-ai', [VerifikasiBerkasController::class, 'analisis'])->name('analisis_ai');
        Route::post('/{laporan}/ringkasan-ai', [VerifikasiBerkasController::class, 'ringkasanAi'])->name('ringkasan_ai');
        Route::post('/{laporan}/setujui', [VerifikasiBerkasController::class, 'setujui'])->name('setujui');
        Route::post('/{laporan}/perbaikan', [VerifikasiBerkasController::class, 'mintaPerbaikan'])->name('perbaikan');
        Route::post('/{laporan}/revisi', [VerifikasiBerkasController::class, 'mintaRevisi'])->name('revisi');
        Route::post('/{laporan}/anomali', [VerifikasiBerkasController::class, 'tandaiAnomali'])->name('anomali');
        Route::post('/{laporan}/tolak-fatal', [VerifikasiBerkasController::class, 'tolakFatal'])->name('tolak_fatal');
    });

// Rekonsiliasi anomali data kependudukan (use case 5.2.7 dan user story 6.2.7).
Route::middleware(['auth', 'peran:verifikator'])
    ->prefix('rekonsiliasi')
    ->name('rekonsiliasi.')
    ->group(function () {
        Route::get('/', [RekonsiliasiController::class, 'index'])->name('index');
        Route::get('/{laporan}', [RekonsiliasiController::class, 'show'])->name('show');
        Route::post('/{laporan}/selesaikan', [RekonsiliasiController::class, 'selesaikan'])->name('selesaikan');
        Route::post('/{laporan}/tolak-fatal', [RekonsiliasiController::class, 'tolakFatal'])->name('tolak_fatal');
    });

Route::redirect('/pengajuan-laporan', '/laporan/baru');
