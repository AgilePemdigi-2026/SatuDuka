<?php

use App\Services\Verifikasi\LayananValidasiNik;
use App\Services\Verifikasi\PelanggaranNik;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

dataset('kasus validasi nik', [
    'valid laki-laki' => ['3471031203559001', 'L', '1955-03-12', '2020-01-01', true, []],
    'valid perempuan' => ['3471036407609002', 'P', '1960-07-24', '2020-01-01', true, []],
    'panjang 15 digit' => ['347103120355900', 'L', null, null, true, ['NIK_PANJANG_SALAH']],
    'panjang 17 digit' => ['34710312035590011', 'L', null, null, true, ['NIK_PANJANG_SALAH']],
    'mengandung huruf' => ['34710312035590AB', 'L', '1955-03-12', '2020-01-01', true, ['NIK_PANJANG_SALAH']],
    'provinsi tidak dikenal' => ['2071031203559001', 'L', '1955-03-12', '2020-01-01', true, ['NIK_PROVINSI_TIDAK_DIKENAL']],
    '31 februari' => ['3471033102559001', 'L', null, null, true, ['NIK_TANGGAL_TIDAK_VALID']],
    'dd 71 perempuan tanggal 31' => ['3471037101900001', 'P', '1990-01-31', '2020-01-01', true, []],
    'dd 72' => ['3471037201900001', 'P', null, null, true, ['NIK_TANGGAL_TIDAK_VALID']],
    'bulan 13' => ['3471030113900001', 'L', null, null, true, ['NIK_TANGGAL_TIDAK_VALID']],
    'jenis kelamin tidak cocok' => ['3471031203559001', 'P', '1955-03-12', '2020-01-01', true, ['NIK_JENIS_KELAMIN_TIDAK_COCOK']],
    'tahun tidak cocok' => ['3471031203569001', 'L', '1955-03-12', '2020-01-01', true, ['NIK_TAHUN_TIDAK_COCOK']],
    'nomor urut 0000' => ['3471031203550000', 'L', '1955-03-12', '2020-01-01', true, ['NIK_URUT_NOL']],
    'lahir setelah meninggal' => ['3471031203559001', 'L', '1955-03-12', '1950-01-01', true, ['TANGGAL_TIDAK_KONSISTEN']],
    'usia 121 tahun' => ['3471031203009001', 'L', '1900-03-12', '2022-01-01', true, ['TANGGAL_TIDAK_KONSISTEN']],
    'nik demo aktif' => ['9999991203559001', 'L', '1955-03-12', '2020-01-01', true, []],
    'nik demo nonaktif' => ['9999991203559001', 'L', '1955-03-12', '2020-01-01', false, ['NIK_PROVINSI_TIDAK_DIKENAL']],
    'tahun dua digit 1924' => ['3471031203249001', 'L', '1924-03-12', '2020-01-01', true, []],
    'tahun dua digit 2024' => ['3471031203249001', 'L', '2024-03-12', '2030-01-01', true, []],
    'tahun dua digit tidak cocok' => ['3471031203249001', 'L', '1925-03-12', '2020-01-01', true, ['NIK_TAHUN_TIDAK_COCOK']],
    'nik kosong' => ['', 'L', null, null, true, ['NIK_PANJANG_SALAH']],
    '29 februari tahun kabisat' => ['3471032902249001', 'L', '2024-02-29', '2025-01-01', true, []],
    '29 februari bukan kabisat' => ['3471032902239001', 'L', null, null, true, ['NIK_TANGGAL_TIDAK_VALID']],
    'hari 40' => ['3471034001900001', 'L', null, null, true, ['NIK_TANGGAL_TIDAK_VALID']],
    'hari 41 berarti tanggal 1 perempuan' => ['3471034101900001', 'P', '1990-01-01', '2020-01-01', true, []],
    'hari 00' => ['3471030001900001', 'L', null, null, true, ['NIK_TANGGAL_TIDAK_VALID']],
    'bulan 00' => ['3471031200900001', 'L', null, null, true, ['NIK_TANGGAL_TIDAK_VALID']],
    'usia tepat 120 tahun' => ['3471031203009001', 'L', '1900-03-12', '2020-03-12', true, []],
    'lahir sama dengan meninggal' => ['3471031203559001', 'L', '1955-03-12', '1955-03-12', true, []],
    'tanpa tanggal lengkap' => ['3471031203559001', null, null, null, true, []],
    'jenis kelamin huruf kecil' => ['3471036407609002', 'p', '1960-07-24', '2020-01-01', true, []],
    'jenis kelamin perempuan lengkap' => ['3471036407609002', 'Perempuan', '1960-07-24', '2020-01-01', true, []],
    'jenis kelamin laki-laki lengkap' => ['3471031203559001', 'Laki-laki', '1955-03-12', '2020-01-01', true, []],
    'semua segmen nol' => ['0000000000000000', null, null, null, true, ['NIK_PROVINSI_TIDAK_DIKENAL', 'NIK_TANGGAL_TIDAK_VALID', 'NIK_URUT_NOL']],
    'tahun dua digit 2000' => ['3471031203009001', 'L', '2000-03-12', '2020-01-01', true, []],
    'huruf dengan tanggal lengkap' => ['34710312035590AB', 'L', '1955-03-12', '2020-01-01', true, ['NIK_PANJANG_SALAH']],
]);

it('memeriksa NIK sesuai tabel kasus', function (
    string $nik,
    ?string $jenisKelamin,
    ?string $tanggalLahir,
    ?string $tanggalKematian,
    bool $izinkanDemo,
    array $kodeDiharapkan,
) {
    config(['verifikasi.izinkan_nik_demo' => $izinkanDemo]);

    $hasil = (new LayananValidasiNik)->periksa(
        $nik,
        $jenisKelamin,
        $tanggalLahir === null ? null : Carbon::parse($tanggalLahir),
        $tanggalKematian === null ? null : Carbon::parse($tanggalKematian),
    );

    expect($hasil->kodePelanggaran())->toEqualCanonicalizing($kodeDiharapkan)
        ->and($hasil->valid)->toBe($kodeDiharapkan === []);

    foreach ($hasil->pelanggaran as $pelanggaran) {
        expect($pelanggaran)->toBeInstanceOf(PelanggaranNik::class)
            ->and($pelanggaran->keparahan)->not->toBe('')
            ->and($pelanggaran->pesan)->not->toBe('');
    }
})->with('kasus validasi nik');

it('mengekstrak seluruh bagian NIK yang valid', function () {
    $hasil = (new LayananValidasiNik(izinkanNikDemo: false))->periksa(
        '3471036407609002',
        'P',
        Carbon::parse('1960-07-24'),
        Carbon::parse('2020-01-01'),
    );

    expect($hasil->valid)->toBeTrue()
        ->and($hasil->provinsi)->toBe('34')
        ->and($hasil->namaProvinsi)->toBe('DI Yogyakarta')
        ->and($hasil->kabupaten)->toBe('71')
        ->and($hasil->kecamatan)->toBe('03')
        ->and($hasil->hari)->toBe(24)
        ->and($hasil->bulan)->toBe(7)
        ->and($hasil->tahun)->toBe('60')
        ->and($hasil->jenisKelamin)->toBe('P')
        ->and($hasil->nomorUrut)->toBe('9002');
});

it('tidak mengekstrak bagian NIK yang panjangnya salah', function () {
    $hasil = (new LayananValidasiNik)->periksa('347103120355900');

    expect($hasil->valid)->toBeFalse()
        ->and($hasil->provinsi)->toBeNull()
        ->and($hasil->kabupaten)->toBeNull()
        ->and($hasil->kecamatan)->toBeNull()
        ->and($hasil->hari)->toBeNull()
        ->and($hasil->bulan)->toBeNull()
        ->and($hasil->tahun)->toBeNull()
        ->and($hasil->jenisKelamin)->toBeNull()
        ->and($hasil->nomorUrut)->toBeNull();
});

it('memakai batas usia dari konfigurasi', function () {
    config(['verifikasi.batas_usia' => 100]);

    $hasil = (new LayananValidasiNik)->periksa(
        '3471031203009001',
        'L',
        Carbon::parse('1900-03-12'),
        Carbon::parse('2005-01-01'),
    );

    expect($hasil->punyaPelanggaran('TANGGAL_TIDAK_KONSISTEN'))->toBeTrue();
});
