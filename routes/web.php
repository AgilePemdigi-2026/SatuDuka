<?php

use App\Http\Controllers\AturUlangKataSandiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrasiController;
use App\Http\Controllers\StatusPengajuanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute SATUDUKA
|--------------------------------------------------------------------------
*/

// Halaman awal: pengguna yang sudah masuk ke dashboard, tamu ke halaman masuk.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('masuk');
})->name('beranda');

// Halaman untuk tamu (belum masuk).
Route::middleware('guest')->group(function () {
    // Registrasi akun Pemohon (use case 5.2.1).
    Route::get('/registrasi', [RegistrasiController::class, 'tampilkan'])->name('registrasi');
    Route::post('/registrasi', [RegistrasiController::class, 'daftar'])->name('registrasi.simpan');

    // Masuk satu formulir NIK/NIP (use case 5.2.2).
    Route::get('/masuk', [LoginController::class, 'tampilkan'])->name('masuk');
    Route::post('/masuk', [LoginController::class, 'masuk'])->name('masuk.coba');

    // Pengaturan ulang kata sandi lewat surel terdaftar (use case 5.2.2 alur 4.a).
    Route::get('/lupa-kata-sandi', [AturUlangKataSandiController::class, 'tampilkanLupa'])->name('lupa_kata_sandi');
    Route::post('/lupa-kata-sandi', [AturUlangKataSandiController::class, 'kirimTautan'])->name('lupa_kata_sandi.kirim');
    Route::get('/atur-ulang-kata-sandi/{token}', [AturUlangKataSandiController::class, 'tampilkanAturUlang'])
        ->name('atur_ulang_kata_sandi');
    Route::post('/atur-ulang-kata-sandi', [AturUlangKataSandiController::class, 'aturUlang'])
        ->name('atur_ulang_kata_sandi.simpan');
});

// Keluar dari sistem.
Route::post('/keluar', [LoginController::class, 'keluar'])
    ->middleware('auth')
    ->name('keluar');

// Dashboard sesuai peran (kerangka).
Route::get('/dashboard', [DashboardController::class, 'tampilkan'])
    ->middleware('auth')
    ->name('dashboard');

// Pengajuan laporan kematian oleh Pemohon (use case 5.2.3).
Route::middleware(['auth', 'peran:pemohon'])->group(function () {
    Route::get('/laporan/baru', [LaporanController::class, 'tampilkanBaru'])->name('laporan.baru');
    Route::post('/laporan/draf', [LaporanController::class, 'simpanDraf'])->name('laporan.draf');

    Route::get('/laporan/{laporan}/ubah', [LaporanController::class, 'tampilkanUbah'])->name('laporan.ubah');
    Route::post('/laporan/{laporan}/draf', [LaporanController::class, 'simpanDraf'])->name('laporan.draf.ubah');
    Route::post('/laporan/{laporan}/kirim', [LaporanController::class, 'kirim'])->name('laporan.kirim');
    Route::post('/laporan/{laporan}/berkas', [LaporanController::class, 'unggahBerkas'])->name('laporan.berkas.unggah');
    Route::get('/laporan/{laporan}/berkas/{berkas}', [LaporanController::class, 'pratinjauBerkas'])->name('laporan.berkas');
    Route::get('/laporan/{laporan}', [LaporanController::class, 'lihat'])->name('laporan.lihat');

    // Pemantauan status pengajuan (use case 5.2.5).
    Route::get('/pengajuan/status', [StatusPengajuanController::class, 'daftar'])->name('pengajuan.status');
    Route::get('/pengajuan/status/{laporan}', [StatusPengajuanController::class, 'detail'])->name('pengajuan.detail');
    Route::get('/pengajuan/{laporan}/dokumen', [StatusPengajuanController::class, 'unduh'])->name('pengajuan.unduh');
});
