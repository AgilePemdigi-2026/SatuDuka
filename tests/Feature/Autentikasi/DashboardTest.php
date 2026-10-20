<?php

/**
 * Pengujian dashboard per peran dan middleware pembatas peran.
 */

use App\Models\Pengguna;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('mengalihkan tamu yang membuka dashboard ke halaman masuk', function () {
    $this->get(route('dashboard'))->assertRedirect(route('masuk'));
});

it('menampilkan dashboard sesuai peran pengguna', function () {
    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();

    $this->actingAs($pemohon)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Pemohon')
        ->assertSee('Kelola Profil')
        ->assertDontSee('Profil Saya');

    $this->actingAs($verifikator)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Verifikator')
        ->assertSee('Kelola Profil');

    $this->actingAs($pejabat)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Pejabat Penanda Tangan')
        ->assertSee('Kelola Profil');
});

it('menampilkan menu avatar dengan inisial saat foto profil belum ada', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('PD')
        ->assertSee('Pemohon')
        ->assertSee(route('profil.tampilkan'))
        ->assertSee(route('keluar'));
});

it('menampilkan foto avatar saat foto profil tersedia', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();

    Storage::disk('local')->put('foto_profil/uji.png', 'foto');
    $pemohon->update(['path_foto' => 'foto_profil/uji.png']);

    $this->actingAs($pemohon)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('profil.foto.tampil'));
});

it('membatasi akses rute berdasarkan peran melalui middleware peran', function () {
    // Rute sementara hanya untuk menguji middleware.
    Route::get('/uji-peran', fn () => 'boleh diakses')->middleware('peran:verifikator,pejabat');

    [$pemohon, $verifikator] = buatAkunDemo();

    $this->actingAs($pemohon)->get('/uji-peran')->assertForbidden();

    $this->actingAs($verifikator)->get('/uji-peran')->assertOk();
});

it('menolak pengguna tanpa peran yang sesuai dengan status 403', function () {
    Route::get('/uji-peran-pemohon', fn () => 'boleh diakses')->middleware('peran:pemohon');

    $pemohon = Pengguna::factory()->pemohon()->create();

    $this->actingAs($pemohon)->get('/uji-peran-pemohon')->assertOk();
    $this->post(route('keluar'));

    $penggunaLain = Pengguna::factory()->verifikator()->create();

    $this->actingAs($penggunaLain)->get('/uji-peran-pemohon')->assertForbidden();
});
