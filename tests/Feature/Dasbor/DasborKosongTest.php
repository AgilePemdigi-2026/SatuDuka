<?php

/**
 * Halaman utama tiap peran harus tetap tampil pada basis data kosong.
 *
 * Skenario Behat menyiapkan basis data hanya dengan `migrate:fresh` (tanpa
 * seeder), sehingga halaman tidak boleh bergantung pada data contoh.
 */
it('menampilkan dasbor semua peran pada basis data kosong', function () {
    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();

    foreach ([$pemohon, $verifikator, $pejabat] as $pengguna) {
        $this->actingAs($pengguna)
            ->get(route('dashboard'))
            ->assertOk();
    }
});

it('menampilkan halaman antrean verifikator pada basis data kosong', function () {
    [, $verifikator] = buatAkunDemo();

    $this->actingAs($verifikator)
        ->get(route('verifikasi.index'))
        ->assertOk();

    $this->actingAs($verifikator)
        ->get(route('rekonsiliasi.index'))
        ->assertOk();
});

it('menampilkan halaman tanda tangan pejabat pada basis data kosong', function () {
    [, , $pejabat] = buatAkunDemo();

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.index'))
        ->assertOk();
});
