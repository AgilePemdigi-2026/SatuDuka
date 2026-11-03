<?php

use App\Services\Verifikasi\HasilPencocokanNama;
use App\Services\Verifikasi\LayananNormalisasiNama;
use App\Services\Verifikasi\LayananPencocokNama;
use Tests\TestCase;

uses(TestCase::class);

dataset('pasangan nama', function () {
    $jalur = __DIR__.'/../../../database/data/pasangan_nama_uji.json';
    $data = json_decode((string) file_get_contents($jalur), true);

    return collect($data)
        ->mapWithKeys(fn (array $baris): array => [
            $baris['nama'] => [$baris['nama_pertama'], $baris['nama_kedua'], $baris['kategori_harapan']],
        ])
        ->all();
});

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

it('memperlakukan skor tepat di ambang secara konsisten setelah pembulatan', function () {
    // "Ahmad" vs "Ahmas" menghasilkan skor tepat 0,8.
    $pencocok = new LayananPencocokNama(batasBedaTipis: 0.8);

    $tepat = $pencocok->cocokkan('Ahmad', 'Ahmas');

    expect($tepat->skor)->toBe(0.8)
        ->and($tepat->kategori)->toBe(HasilPencocokanNama::KATEGORI_BEDA_TIPIS);

    // Ambang yang terkena galat floating-point dibulatkan ke presisi skor.
    $hampir = new LayananPencocokNama(batasBedaTipis: 0.8000000001);
    $hasilHampir = $hampir->cocokkan('Ahmad', 'Ahmas');

    expect($hasilHampir->skor)->toBe(0.8)
        ->and($hasilHampir->kategori)->toBe(HasilPencocokanNama::KATEGORI_BEDA_TIPIS);

    // Selisih 0,0001 tetap dianggap di bawah ambang, bukan dibulatkan naik.
    $diBawah = new LayananPencocokNama(batasBedaTipis: 0.8001);

    expect($diBawah->cocokkan('Ahmad', 'Ahmas')->kategori)
        ->toBe(HasilPencocokanNama::KATEGORI_TIDAK_COCOK);
});
