
<?php

use App\Http\Controllers\AturUlangKataSandiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegistrasiController;
use App\Http\Controllers\VerifikasiBerkasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute SATUDUKA
|--------------------------------------------------------------------------
*/

// Halaman awal: pengguna yang sudah masuk ke dashboard, tamu ke halaman masuk.
Route::get('/', function () {
    return \Illuminate\Support\Facades\Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('masuk');
})->name('beranda');

// Halaman untuk tamu (belum masuk).
Route::middleware('guest')->group(function () {
    // Registrasi akun Pemohon.
    Route::get('/registrasi', [RegistrasiController::class, 'tampilkan'])
        ->name('registrasi');
    Route::post('/registrasi', [RegistrasiController::class, 'daftar'])
        ->name('registrasi.simpan');

    // Masuk.
    Route::get('/masuk', [LoginController::class, 'tampilkan'])
        ->name('masuk');
    Route::post('/masuk', [LoginController::class, 'masuk'])
        ->name('masuk.coba');

    // Pengaturan ulang kata sandi.
    Route::get('/lupa-kata-sandi', [AturUlangKataSandiController::class, 'tampilkanLupa'])
        ->name('lupa_kata_sandi');
    Route::post('/lupa-kata-sandi', [AturUlangKataSandiController::class, 'kirimTautan'])
        ->name('lupa_kata_sandi.kirim');

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

// Verifikasi berkas pengajuan khusus verifikator.
Route::middleware(['auth', 'peran:verifikator'])
    ->prefix('verifikasi-berkas')
    ->name('verifikasi.')
    ->group(function () {
        // Daftar pengajuan yang menunggu verifikasi.
        Route::get('/', [VerifikasiBerkasController::class, 'index'])
            ->name('index');

        // Detail pengajuan.
        Route::get('/{laporan}', [VerifikasiBerkasController::class, 'show'])
            ->name('show');

        // Keputusan verifikasi.
        Route::post('/{laporan}/setujui', [VerifikasiBerkasController::class, 'setujui'])
            ->name('setujui');

        Route::post('/{laporan}/revisi', [VerifikasiBerkasController::class, 'mintaRevisi'])
            ->name('revisi');

        Route::post('/{laporan}/anomali', [VerifikasiBerkasController::class, 'tandaiAnomali'])
            ->name('anomali');
    });
