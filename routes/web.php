<?php

use App\Http\Controllers\AturUlangKataSandiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrasiController;
use App\Http\Controllers\LaporanKematianController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute SATUDUKA
|--------------------------------------------------------------------------
*/

// Halaman awal: pengguna yang sudah login ke dashboard,
// sedangkan tamu diarahkan ke halaman masuk.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('masuk');
})->name('beranda');

// Halaman untuk tamu (belum login).
Route::middleware('guest')->group(function () {

    // Registrasi akun Pemohon.
    Route::get('/registrasi', [
        RegistrasiController::class,
        'tampilkan',
    ])->name('registrasi');

    Route::post('/registrasi', [
        RegistrasiController::class,
        'daftar',
    ])->name('registrasi.simpan');

    // Login menggunakan NIK/NIP.
    Route::get('/masuk', [
        LoginController::class,
        'tampilkan',
    ])->name('masuk');

    Route::post('/masuk', [
        LoginController::class,
        'masuk',
    ])->name('masuk.coba');

    // Pengaturan ulang kata sandi.
    Route::get('/lupa-kata-sandi', [
        AturUlangKataSandiController::class,
        'tampilkanLupa',
    ])->name('lupa_kata_sandi');

    Route::post('/lupa-kata-sandi', [
        AturUlangKataSandiController::class,
        'kirimTautan',
    ])->name('lupa_kata_sandi.kirim');

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
Route::post('/keluar', [
    LoginController::class,
    'keluar',
])->middleware('auth')->name('keluar');

// Dashboard pengguna yang sudah login.
Route::get('/dashboard', [
    DashboardController::class,
    'tampilkan',
])->middleware('auth')->name('dashboard');

// Pengajuan laporan kematian.
Route::middleware('auth')->group(function () {

    // Menampilkan form pengajuan.
    Route::get('/pengajuan-laporan', [
        LaporanKematianController::class,
        'create',
    ])->name('laporan.create');

    // Mengirim form pengajuan.
    Route::post('/pengajuan-laporan', [
        LaporanKematianController::class,
        'store',
    ])->name('laporan.store');

});