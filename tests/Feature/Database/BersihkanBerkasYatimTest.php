<?php

use App\Models\BerkasPendukung;
use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Storage;

it('membersihkan berkas yatim dengan mode uji dan menghapus yang tidak dirujuk', function () {
    Storage::fake('local');

    $disk = Storage::disk('local');
    $disk->put('berkas_laporan/1/ada.pdf', 'isi');
    $disk->put('berkas_laporan/1/yatim.pdf', 'isi');
    $disk->put('foto_profil/1/ada.png', 'isi');
    $disk->put('foto_profil/1/yatim.png', 'isi');
    $disk->put('dokumen/akta.pdf', 'isi');

    $pengguna = Pengguna::factory()->pemohon()->create([
        'path_foto' => 'foto_profil/1/ada.png',
    ]);
    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pengguna->id_pengguna,
    ]);
    BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'path_file' => 'berkas_laporan/1/ada.pdf',
    ]);
    DokumenResmi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'file_dokumen' => 'dokumen/akta.pdf',
    ]);

    $this->artisan('berkas:bersihkan', ['--dry-run' => true])
        ->assertSuccessful();

    expect($disk->exists('berkas_laporan/1/yatim.pdf'))->toBeTrue()
        ->and($disk->exists('foto_profil/1/yatim.png'))->toBeTrue();

    $this->artisan('berkas:bersihkan')
        ->assertSuccessful();

    expect($disk->exists('berkas_laporan/1/ada.pdf'))->toBeTrue()
        ->and($disk->exists('foto_profil/1/ada.png'))->toBeTrue()
        ->and($disk->exists('dokumen/akta.pdf'))->toBeTrue()
        ->and($disk->exists('berkas_laporan/1/yatim.pdf'))->toBeFalse()
        ->and($disk->exists('foto_profil/1/yatim.png'))->toBeFalse();
});
