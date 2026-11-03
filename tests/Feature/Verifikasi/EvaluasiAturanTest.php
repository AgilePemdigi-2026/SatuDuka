<?php

/**
 * Pengujian evaluasi mesin aturan Fase 6.8.
 *
 * Dataset kasus uji bersifat fiktif dan labelnya berasal dari rancangan aturan.
 * Pengujian memakai SQLite in-memory tanpa memanggil SIAK atau AI nyata.
 */

use App\Console\Commands\EvaluasiAturan;
use App\Models\BerkasPendukung;
use Database\Seeders\ReferensiWilayahSeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Memuat berkas kasus uji.
 *
 * @return array<string, mixed>
 */
function muatDataKasusUji(): array
{
    return json_decode(
        (string) file_get_contents(database_path('data/kasus_uji_aturan.json')),
        true,
    );
}

it('menyediakan minimal enam puluh kasus uji berlabel yang lengkap', function () {
    $data = muatDataKasusUji();

    expect($data)->toBeArray()
        ->and($data['kasus'])->toBeArray();

    $kasus = $data['kasus'];

    expect(count($kasus))->toBeGreaterThanOrEqual(60)
        ->and($data['jumlah_kasus'])->toBe(count($kasus));

    $bersih = array_filter($kasus, fn (array $item): bool => $item['kategori'] === 'bersih');
    $ambigu = array_filter($kasus, fn (array $item): bool => $item['kategori'] === 'ambigu');

    expect(count($bersih))->toBeGreaterThanOrEqual(15)
        ->and(count($ambigu))->toBeGreaterThanOrEqual(3);

    foreach (array_keys((array) config('verifikasi.taksonomi_anomali')) as $kode) {
        $jumlah = count(array_filter(
            $kasus,
            fn (array $item): bool => in_array($kode, $item['kode_harus_muncul'], true),
        ));

        expect($jumlah)->toBeGreaterThanOrEqual(3, 'Kode '.$kode.' kurang dari tiga kasus.');
    }

    foreach ($kasus as $item) {
        expect($item)->toHaveKeys([
            'id',
            'kategori',
            'deskripsi',
            'pengajuan',
            'siak',
            'siak_tersedia',
            'berkas',
            'analisis_ai',
            'pengajuan_ganda',
            'kode_harus_muncul',
            'kategori_nama_harapan',
        ]);

        expect($item['pengajuan']['nik_almarhum'])->toMatch('/^\d{16}$/')
            ->and($item['berkas'])->toBeArray();

        foreach ($item['berkas'] as $jenis) {
            expect(BerkasPendukung::DAFTAR_JENIS)->toContain($jenis);
        }
    }
});

it('menyebar alamat kasus sesuai kecamatan dan menandai kasus luar kota', function () {
    $data = muatDataKasusUji();
    $sebaran = array_count_values(array_column($data['kasus'], 'kecamatan_alamat'));

    $kecamatan = [
        'Tegalrejo', 'Jetis', 'Gondokusuman', 'Danurejan', 'Gedongtengen',
        'Ngampilan', 'Wirobrajan', 'Mantrijeron', 'Kraton', 'Gondomanan',
        'Pakualaman', 'Mergangsan', 'Umbulharjo', 'Kotagede',
    ];

    foreach ($kecamatan as $nama) {
        expect($sebaran[$nama] ?? 0)->toBeGreaterThan(0, 'Kecamatan '.$nama.' belum terwakili.');
    }

    expect($sebaran['Luar Kota Yogyakarta'] ?? 0)->toBeGreaterThanOrEqual(3)
        ->and(array_sum($sebaran))->toBe(count($data['kasus']));
});

it('menghitung metrik per kode anomali dan matriks nama tanpa kesalahan', function () {
    $this->seed(ReferensiWilayahSeeder::class);

    $perintah = app(EvaluasiAturan::class);
    $kasus = $perintah->muatKasus();
    $hasil = $perintah->evaluasi($kasus);

    expect($hasil['total'])->toBe(count($kasus))
        ->and($hasil['akurasi'])->toBe(1.0)
        ->and($hasil['sesuai_persis'])->toBe(count($kasus));

    foreach ($hasil['per_kode'] as $kode => $metrik) {
        expect($metrik['dukungan'])->toBeGreaterThanOrEqual(3, 'Dukungan '.$kode)
            ->and($metrik['benar_positif'])->toBe($metrik['dukungan'], 'TP '.$kode)
            ->and($metrik['positif_palsu'])->toBe(0, 'FP '.$kode)
            ->and($metrik['negatif_palsu'])->toBe(0, 'FN '.$kode)
            ->and($metrik['presisi'])->toBe(1.0, 'Presisi '.$kode)
            ->and($metrik['recall'])->toBe(1.0, 'Recall '.$kode);
    }

    $matriks = $hasil['matriks_nama'];

    expect(array_sum($matriks['PERSIS']))->toBeGreaterThan(0)
        ->and(array_sum($matriks['BEDA_TIPIS']))->toBeGreaterThan(0)
        ->and(array_sum($matriks['TIDAK_COCOK']))->toBeGreaterThan(0)
        ->and(array_sum($matriks['NA']))->toBeGreaterThan(0)
        ->and($matriks['PERSIS']['BEDA_TIPIS'])->toBe(0)
        ->and($matriks['PERSIS']['TIDAK_COCOK'])->toBe(0)
        ->and($matriks['BEDA_TIPIS']['TIDAK_COCOK'])->toBe(0)
        ->and($matriks['TIDAK_COCOK']['PERSIS'])->toBe(0)
        ->and($matriks['TIDAK_COCOK']['BEDA_TIPIS'])->toBe(0);

    $jumlahMatriks = array_sum(array_map('array_sum', $matriks));

    expect($jumlahMatriks)->toBe(count($kasus));
});

it('mengukur tiga puluh sembilan pasangan nama dan mempertahankan ambang', function () {
    $hasil = app(EvaluasiAturan::class)->kalibrasiNama();

    expect($hasil['jumlah'])->toBe(39)
        ->and($hasil['jumlah_sesuai'])->toBe(39)
        ->and($hasil['tidak_sesuai'])->toBe([])
        ->and($hasil['meragukan'])->not->toBeEmpty()
        ->and($hasil['batas_beda_tipis'])->toBe(0.8)
        ->and($hasil['batas_persis'])->toBe(1.0)
        ->and($hasil['rekomendasi']['ubah'])->toBeFalse()
        ->and($hasil['rekomendasi']['batas_dipertahankan'])->toBe(0.8);
});

it('menjalankan perintah aturan:evaluasi dan menampilkan laporan lengkap', function () {
    $this->seed(ReferensiWilayahSeeder::class);

    $status = Artisan::call('aturan:evaluasi', ['--dry-run' => true]);
    $keluaran = Artisan::output();

    expect($status)->toBe(0)
        ->and($keluaran)->toContain('Evaluasi Aturan Verifikasi')
        ->and($keluaran)->toContain('Kecocokan himpunan kode persis: 62 / 62')
        ->and($keluaran)->toContain('Matriks Kebingungan Kategori Nama')
        ->and($keluaran)->toContain('Kalibrasi Ambang Nama (39 Pasangan)')
        ->and($keluaran)->toContain('Ambang **tidak diubah**')
        ->and($keluaran)->toContain('BUKAN akurasi terhadap data kependudukan nyata');
});
