<?php

/**
 * Pengujian penguatan keamanan: header, penjaga rute, halaman galat, dan
 * masking NIK pada log.
 */

use App\Http\Middleware\KepalaKeamanan;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('memasang header keamanan dasar pada halaman web', function () {
    $this->get(route('masuk'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
});

it('tetap memuat halaman pratinjau berkas sendiri di dalam iframe', function () {
    Storage::fake('local');

    [$pemohon] = buatAkunDemo();
    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
    ]);
    $berkas = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_KTP_PELAPOR,
        'path_file' => 'berkas_laporan/'.$laporan->id_laporan.'/uji.pdf',
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
    ]);
    Storage::disk('local')->put($berkas->path_file, '%PDF-1.4 uji');

    $this->actingAs($pemohon)
        ->get(route('laporan.berkas', [$laporan, $berkas]))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('mewajibkan auth pada semua rute selain daftar putih publik', function () {
    $publik = [
        '/',
        'up',
        'masuk',
        'registrasi',
        'lupa-kata-sandi',
        'atur-ulang-kata-sandi',
        'atur-ulang-kata-sandi/{token}',
        'pengajuan-laporan',
    ];

    $tanpaAuth = [];

    foreach (Route::getRoutes() as $rute) {
        $uri = $rute->uri();

        if (in_array($uri, $publik, true)) {
            continue;
        }

        if (! in_array('auth', $rute->gatherMiddleware(), true)) {
            $tanpaAuth[] = $rute->methods()[0].' /'.$uri;
        }
    }

    expect($tanpaAuth)->toBe([], 'Rute tanpa middleware auth: '.implode(', ', $tanpaAuth));
});

it('tidak mendaftarkan rute penyaji berkas privat bawaan', function () {
    $nama = collect(Route::getRoutes())->map(fn ($rute) => $rute->getName())->filter()->all();

    expect($nama)->not->toContain('storage.local')
        ->and($nama)->not->toContain('storage.local.upload');
});

it('menampilkan halaman 404 berbahasa Indonesia tanpa jejak tumpukan', function () {
    $this->get('/halaman-tidak-ada-sama-sekali')
        ->assertNotFound()
        ->assertSee('Halaman Tidak Ditemukan')
        ->assertSee('Kembali ke Halaman Masuk')
        ->assertDontSee('Whoops')
        ->assertDontSee('Stack trace');
});

it('menampilkan halaman 403 berbahasa Indonesia dengan tautan kembali', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    $this->actingAs($verifikator)
        ->get(route('pengajuan.status'))
        ->assertForbidden()
        ->assertSee('Akses Ditolak')
        ->assertSee('Kembali ke Dashboard');

    expect($pemohon->id_pengguna)->not->toBeNull();
});

it('menampilkan halaman 500 statis tanpa pesan pengecualian walau debug aktif', function () {
    config(['app.debug' => true]);

    Route::middleware('web')->get('/uji-500-penguatan', function () {
        throw new RuntimeException('Rahasia internal 3471031203559001');
    });

    $this->get('/uji-500-penguatan')
        ->assertStatus(500)
        ->assertSee('Terjadi Gangguan')
        ->assertDontSee('Rahasia internal')
        ->assertDontSee('3471031203559001')
        ->assertDontSee('RuntimeException');
});

it('memasker NIK pada pesan dan konteks log termasuk QueryException', function () {
    $jalur = storage_path('logs/laravel.log');
    $ukuranAwal = is_file($jalur) ? (int) filesize($jalur) : 0;

    $nik = '3471031203559001';
    $exception = new QueryException(
        'sqlite',
        'update "laporan_kematian" set "nik_almarhum" = ?',
        [$nik],
        new RuntimeException('Gagal menyimpan NIK '.$nik),
    );

    Log::channel('single')->error('Gagal memproses NIK '.$nik, [
        'exception' => $exception,
        'nik' => $nik,
        'catatan' => ['nik_almarhum' => $nik],
    ]);

    $isi = (string) file_get_contents($jalur);
    $baru = substr($isi, $ukuranAwal);

    expect($baru)->toContain('347103120355****')
        ->and($baru)->not->toContain($nik);
});

it('memasang header keamanan yang sama pada seluruh respons', function () {
    expect(KepalaKeamanan::HEADER['X-Frame-Options'])->toBe('SAMEORIGIN');
});
