<?php

/**
 * Pengujian dashboard per peran dan middleware pembatas peran.
 */

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
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
        ->assertSee('Anda masuk sebagai Pemohon')
        ->assertSee('Kelola Profil')
        ->assertDontSee('Profil Saya');

    $this->actingAs($verifikator)->get(route('dashboard'))
        ->assertRedirect(route('verifikasi.index'));

    $this->actingAs($verifikator)->get(route('verifikasi.index'))
        ->assertOk()
        ->assertSee('Dashboard Verifikator')
        ->assertSee('Semua Status')
        ->assertDontSee('<aside class="skd-side">', false);

    $this->actingAs($pejabat)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Pejabat Penanda Tangan')
        ->assertSee('Anda masuk sebagai Pejabat')
        ->assertSee('Kelola Profil')
        ->assertDontSee('Profil Saya');
});

it('mengambil statistik dan antrean dashboard pejabat dari database', function () {
    [, , $pejabat] = buatAkunDemo();

    $menunggu = LaporanKematian::factory()
        ->denganStatus(LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE)
        ->create(['nama_almarhum' => 'Almarhum Antrean']);
    DokumenResmi::factory()->create(['id_laporan' => $menunggu->id_laporan]);

    $selesai = LaporanKematian::factory()
        ->denganStatus(LaporanKematian::STATUS_SELESAI)
        ->create();
    DokumenResmi::factory()->selesai()->create(['id_laporan' => $selesai->id_laporan]);

    $dikembalikan = LaporanKematian::factory()->create();
    DokumenResmi::factory()->create([
        'id_laporan' => $dikembalikan->id_laporan,
        'status_ttd' => DokumenResmi::STATUS_DIKEMBALIKAN,
    ]);

    $respons = $this->actingAs($pejabat)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Menunggu Tindakan')
        ->assertSee('Almarhum Antrean')
        ->assertSee(route('tanda_tangan.show', $menunggu));

    expect($respons->viewData('statistik'))->toBe([
        'menunggu' => 1,
        'selesai' => 1,
        'dikembalikan' => 1,
    ])->and($respons->viewData('dokumenMenunggu')->pluck('id_dokumen')->all())
        ->toBe([DokumenResmi::query()->where('id_laporan', $menunggu->id_laporan)->value('id_dokumen')]);
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
