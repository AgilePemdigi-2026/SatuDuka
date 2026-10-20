<?php

use App\Http\Controllers\AturUlangKataSandiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrasiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaporanKematianController;

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

// Dashboard sesuai peran (kerangka fase ini).
Route::get('/dashboard', [DashboardController::class, 'tampilkan'])
    ->middleware('auth')
    ->name('dashboard');

// Pengajuan laporan kematian (use case 5.2.3).
Route::get('/pengajuan-laporan', [LaporanKematianController::class, 'create'])
    ->middleware('auth')
    ->name('laporan.create');