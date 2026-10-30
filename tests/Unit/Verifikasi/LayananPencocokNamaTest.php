<?php

use App\Services\Verifikasi\HasilPencocokanNama;
use App\Services\Verifikasi\LayananNormalisasiNama;
use App\Services\Verifikasi\LayananPencocokNama;
use Tests\TestCase;

uses(TestCase::class);

dataset('pasangan nama', [
    'ejaan lama oe' => ['Soeharto', 'Suharto', HasilPencocokanNama::KATEGORI_PERSIS],
    'ejaan lama dj' => ['Djoko', 'Joko', HasilPencocokanNama::KATEGORI_PERSIS],
    'ejaan lama tj' => ['Tjokro', 'Cokro', HasilPencocokanNama::KATEGORI_PERSIS],
    'ejaan lama dibalik' => ['Joko', 'Djoko', HasilPencocokanNama::KATEGORI_PERSIS],
    'ejaan lama soekarno' => ['Soekarno', 'Sukarno', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar depan dr' => ['Dr. Ahmad', 'Ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar depan h' => ['H. Ahmad', 'Ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar depan hj' => ['Hj. Siti', 'Siti', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar depan kh' => ['KH. Ahmad', 'Ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar depan k.h' => ['K.H. Ahmad', 'Ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar depan ir' => ['Ir. Soekarno', 'Soekarno', HasilPencocokanNama::KATEGORI_PERSIS],
    'beberapa gelar depan' => ['Prof. Dr. Ir. Budi', 'Budi', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar belakang s.pd' => ['Ahmad, S.Pd.', 'Ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar belakang tanpa titik' => ['Ahmad S.Pd', 'Ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'gelar belakang m.pd' => ['Ahmad M.Pd.', 'Ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'inisial m lawan mohammad' => ['M. Farhan', 'Mohammad Farhan', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'inisial m lawan muhammad' => ['M. Farhan', 'Muhammad Farhan', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'inisial r sama persis' => ['R. Farhan', 'R. Farhan', HasilPencocokanNama::KATEGORI_PERSIS],
    'inisial r lawan raden' => ['R. Farhan', 'Raden Farhan', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'inisial r lawan raden ahmad' => ['R. Ahmad', 'Raden Ahmad', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'r sebagai gelar bila dua token' => ['R. Mas Farhan', 'Mas Farhan', HasilPencocokanNama::KATEGORI_PERSIS],
    'inisial r dengan nama pendek' => ['R. Farhan', 'Farhan', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'inisial di tengah gelar pendek' => ['R. M. Farhan', 'Raden Muhammad Farhan', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'nama tunggal lawan nama panjang' => ['Suharto', 'Suharto Wijaya', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'nama tunggal perempuan' => ['Siti', 'Siti Aminah', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'nama tunggal budi' => ['Budi', 'Budi Santoso', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'nama panjang memuat nama tunggal' => ['Ahmad Farhan', 'Ahmad Farhan Wijaya', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'inisial tanpa nama pendek' => ['Farhan', 'M. Farhan', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'urutan token terbalik' => ['Ahmad Farhan', 'Farhan Ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'urutan token perempuan' => ['Siti Aminah', 'Aminah Siti', HasilPencocokanNama::KATEGORI_PERSIS],
    'salah satu huruf' => ['Ahmad', 'Ahmas', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'salah satu huruf nama panjang' => ['Suharto Wijaya', 'Suharto Wijoyo', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
    'nama berbeda total' => ['Ahmad', 'Budi', HasilPencocokanNama::KATEGORI_TIDAK_COCOK],
    'nama panjang berbeda total' => ['Siti Aminah', 'Budi Santoso', HasilPencocokanNama::KATEGORI_TIDAK_COCOK],
    'huruf kapital berbeda' => ['AHMAD', 'ahmad', HasilPencocokanNama::KATEGORI_PERSIS],
    'spasi ganda' => ['  Ahmad   Farhan  ', 'Ahmad Farhan', HasilPencocokanNama::KATEGORI_PERSIS],
    'tanda hubung' => ['Ahmad-Farhan', 'Ahmad Farhan', HasilPencocokanNama::KATEGORI_PERSIS],
    'diakritik' => ['José', 'Jose', HasilPencocokanNama::KATEGORI_PERSIS],
    'nama mirip satu huruf' => ['Muhammad Farhan', 'Mohamad Farhan', HasilPencocokanNama::KATEGORI_BEDA_TIPIS],
]);

it('mencocokkan pasangan nama sesuai tabel', function (string $namaPertama, string $namaKedua, string $kategori) {
    $pencocok = new LayananPencocokNama;

    $hasil = $pencocok->cocokkan($namaPertama, $namaKedua);

    expect($hasil->kategori)->toBe($kategori);

    if ($kategori === HasilPencocokanNama::KATEGORI_PERSIS) {
        expect($hasil->skor)->toBe(1.0);
    } elseif ($kategori === HasilPencocokanNama::KATEGORI_BEDA_TIPIS) {
        expect($hasil->skor)
            ->toBeGreaterThanOrEqual((float) config('verifikasi.nama_beda_tipis'))
            ->toBeLessThan((float) config('verifikasi.nama_cocok_persis'));
    } else {
        expect($hasil->skor)->toBeLessThan((float) config('verifikasi.nama_beda_tipis'));
    }

    $kebalikan = $pencocok->cocokkan($namaKedua, $namaPertama);

    expect($kebalikan->kategori)->toBe($kategori)
        ->and($kebalikan->skor)->toBe($hasil->skor);
})->with('pasangan nama');

it('menormalkan gelar, inisial, ejaan lama, dan spasi', function (string $nama, string $harapan) {
    expect((new LayananNormalisasiNama)->normalisasi($nama))->toBe($harapan);
})->with([
    'ejaan lama' => ['Soeharto', 'suharto'],
    'dj menjadi j' => ['Djoko', 'joko'],
    'tj menjadi c' => ['Tjokro', 'cokro'],
    'inisial m tetap' => ['M. Farhan', 'm farhan'],
    'inisial r tetap bila satu token' => ['R. Farhan', 'r farhan'],
    'r menjadi gelar bila dua token' => ['R. Mas Farhan', 'mas farhan'],
    'semua gelar dibuang' => ['Prof. Dr. Ir. H. Ahmad, S.Pd.', 'ahmad'],
    'spasi dan tanda baca' => ['  Ahmad---Farhan  ', 'ahmad farhan'],
    'diakritik' => ['José', 'jose'],
]);

it('memakai daftar gelar dari konfigurasi', function () {
    config(['verifikasi.nama_gelar_depan' => ['budi']]);

    expect((new LayananNormalisasiNama)->normalisasi('Budi Santoso'))->toBe('santoso');
});

it('memakai ambang kategori dari konfigurasi', function () {
    config(['verifikasi.nama_beda_tipis' => 0.90]);

    $hasil = (new LayananPencocokNama)->cocokkan('Ahmad', 'Ahmas');

    expect($hasil->skor)->toBe(0.8)
        ->and($hasil->kategori)->toBe(HasilPencocokanNama::KATEGORI_TIDAK_COCOK);
});
