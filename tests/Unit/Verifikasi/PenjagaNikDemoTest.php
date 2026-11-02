<?php

use App\Support\PenjagaNikDemo;

/**
 * Guard NIK demo: tidak boleh aktif otomatis di produksi tanpa flag eksplisit.
 */
it('menonaktifkan nik demo di produksi tanpa flag demo eksplisit', function () {
    expect(PenjagaNikDemo::putuskan('production', diminta: true, izinkanMock: false))
        ->toBe(['aktif' => false, 'diabaikan' => true]);
});

it('mengizinkan nik demo di produksi bila flag demo eksplisit dinyalakan', function () {
    expect(PenjagaNikDemo::putuskan('production', diminta: true, izinkanMock: true))
        ->toBe(['aktif' => true, 'diabaikan' => false]);
});

it('mengizinkan nik demo di lingkungan non-produksi', function () {
    expect(PenjagaNikDemo::putuskan('local', diminta: true, izinkanMock: false))
        ->toBe(['aktif' => true, 'diabaikan' => false]);
});

it('tidak memperingatkan bila nik demo memang tidak diminta', function () {
    expect(PenjagaNikDemo::putuskan('production', diminta: false, izinkanMock: false))
        ->toBe(['aktif' => false, 'diabaikan' => false]);
});
