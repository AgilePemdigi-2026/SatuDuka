<?php

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Services\Ai\LayananSkorKecurigaan;

/**
 * Laporan menunggu verifikasi dengan data almarhum dari data dummy SIAK.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanSkor(Pengguna $pemohon, array $timpa = []): LaporanKematian
{
    return LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now()->startOfDay(),
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '9999990101900101',
        'tanggal_kematian' => now()->subDays(2)->toDateString(),
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
    ], $timpa));
}

/**
 * Melampirkan seluruh berkas wajib pada laporan.
 */
function lengkapiBerkasSkor(LaporanKematian $laporan): void
{
    foreach (BerkasPendukung::DAFTAR_JENIS as $urutan => $jenis) {
        BerkasPendukung::factory()->create([
            'id_laporan' => $laporan->id_laporan,
            'jenis_dokumen' => $jenis,
            'nama_file' => 'berkas-'.$urutan.'.pdf',
            'path_file' => 'berkas_skor/'.$laporan->id_laporan.'/berkas-'.$urutan.'.pdf',
            'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
            'tanggal_unggah' => now(),
        ]);
    }
}

/**
 * Menyusun struktur hasil perbandingan untuk pengujian skor.
 *
 * @param  array<string, string>  $status
 * @return array<string, mixed>
 */
function susunPerbandinganSkor(array $status = []): array
{
    $label = [
        'nama_almarhum' => 'Nama Almarhum/Almarhumah',
        'nik' => 'NIK',
        'tanggal_kematian' => 'Tanggal Kematian',
        'tempat_kematian' => 'Tempat Kematian',
    ];

    $status = array_merge([
        'nama_almarhum' => 'cocok',
        'nik' => 'cocok',
        'tanggal_kematian' => 'cocok',
        'tempat_kematian' => 'cocok',
    ], $status);

    $kolom = [];

    foreach ($status as $kunci => $nilai) {
        $kolom[] = [
            'kunci' => $kunci,
            'label' => $label[$kunci],
            'nilai_pemohon' => null,
            'nilai_ekstraksi' => null,
            'nilai_siak' => null,
            'status' => $nilai,
            'catatan' => [],
            'keyakinan' => 0.9,
        ];
    }

    return [
        'kolom' => $kolom,
        'jumlah_beda' => count(array_filter($status, fn (string $nilai): bool => $nilai === 'beda')),
        'ada_beda' => in_array('beda', $status, true),
        'skor' => 0.9,
    ];
}

it('memberi skor nol untuk data lengkap dan cocok', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanSkor($pemohon);
    lengkapiBerkasSkor($laporan);

    $hasil = app(LayananSkorKecurigaan::class)->hitung($laporan, susunPerbandinganSkor());

    expect($hasil['skor'])->toBe(0)
        ->and($hasil['alasan'])->toBe([])
        ->and($hasil['kategori'])->toBe('rendah');
});

it('memberi bobot tiga puluh untuk nik yang berbeda', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanSkor($pemohon);
    lengkapiBerkasSkor($laporan);

    $hasil = app(LayananSkorKecurigaan::class)->hitung(
        $laporan,
        susunPerbandinganSkor(['nik' => 'beda']),
    );

    $alasan = collect($hasil['alasan'])->firstWhere('kode', 'NIK_TIDAK_COCOK');

    expect($hasil['skor'])->toBe(LayananSkorKecurigaan::BOBOT_NIK_TIDAK_COCOK)
        ->and($alasan)->not->toBeNull()
        ->and($alasan['bobot'])->toBe(30);
});

it('menjumlahkan bobot nama tanggal dan tempat yang berbeda', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanSkor($pemohon);
    lengkapiBerkasSkor($laporan);

    $hasil = app(LayananSkorKecurigaan::class)->hitung($laporan, susunPerbandinganSkor([
        'nama_almarhum' => 'beda',
        'tanggal_kematian' => 'beda',
        'tempat_kematian' => 'beda',
    ]));

    $kode = collect($hasil['alasan'])->pluck('kode')->all();

    expect($hasil['skor'])->toBe(45)
        ->and($kode)->toContain('NAMA_TIDAK_COCOK')
        ->and($kode)->toContain('TANGGAL_TIDAK_COCOK')
        ->and($kode)->toContain('TEMPAT_TIDAK_COCOK');
});

it('memberi bobot untuk berkas kurang dan dokumen tidak terbaca', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanSkor($pemohon);

    BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'nama_file' => 'surat.pdf',
        'path_file' => 'berkas_skor/surat.pdf',
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
        'tanggal_unggah' => now(),
    ]);

    $hasil = app(LayananSkorKecurigaan::class)->hitung(
        $laporan,
        susunPerbandinganSkor(['nik' => 'tidak terbaca']),
    );

    $kode = collect($hasil['alasan'])->pluck('kode')->all();

    expect($hasil['skor'])->toBe(20)
        ->and($kode)->toContain('BERKAS_KURANG')
        ->and($kode)->toContain('DOKUMEN_TIDAK_TERBACA');
});

it('memberi bobot untuk selisih tanggal kematian yang janggal', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanSkor($pemohon, [
        'tanggal_kematian' => now()->subDays(40)->toDateString(),
    ]);
    lengkapiBerkasSkor($laporan);

    $hasil = app(LayananSkorKecurigaan::class)->hitung($laporan, susunPerbandinganSkor());

    $alasan = collect($hasil['alasan'])->firstWhere('kode', 'SELISIH_TANGGAL_JANGGAL');

    expect($hasil['skor'])->toBe(LayananSkorKecurigaan::BOBOT_SELISIH_TANGGAL_JANGGAL)
        ->and($alasan)->not->toBeNull()
        ->and($alasan['bobot'])->toBe(15);
});

it('memberi bobot saat tanggal kematian setelah pengajuan', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanSkor($pemohon, [
        'tanggal_kematian' => now()->addDays(2)->toDateString(),
    ]);
    lengkapiBerkasSkor($laporan);

    $hasil = app(LayananSkorKecurigaan::class)->hitung($laporan, susunPerbandinganSkor());

    $alasan = collect($hasil['alasan'])->firstWhere('kode', 'TANGGAL_KEMATIAN_MASA_DEPAN');

    expect($hasil['skor'])->toBe(LayananSkorKecurigaan::BOBOT_TANGGAL_TIDAK_WAJAR)
        ->and($alasan)->not->toBeNull()
        ->and($alasan['bobot'])->toBe(20);
});

it('membatasi skor maksimal seratus', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanSkor($pemohon, [
        'tanggal_kematian' => now()->addDays(2)->toDateString(),
    ]);

    $hasil = app(LayananSkorKecurigaan::class)->hitung($laporan, susunPerbandinganSkor([
        'nik' => 'beda',
        'nama_almarhum' => 'beda',
        'tanggal_kematian' => 'beda',
        'tempat_kematian' => 'beda',
    ]));

    expect($hasil['skor'])->toBe(100)
        ->and($hasil['kategori'])->toBe('tinggi');
});

it('menghitung skor tanpa hasil perbandingan memakai data siak', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanSkor($pemohon, ['nik_almarhum' => '9999990101909999']);
    lengkapiBerkasSkor($laporan);

    $hasil = app(LayananSkorKecurigaan::class)->hitung($laporan);

    $alasan = collect($hasil['alasan'])->firstWhere('kode', 'NIK_TIDAK_DITEMUKAN');

    expect($hasil['skor'])->toBe(LayananSkorKecurigaan::BOBOT_NIK_TIDAK_DITEMUKAN)
        ->and($alasan)->not->toBeNull();
});

it('mendokumentasikan bobot aturan skor', function () {
    expect(LayananSkorKecurigaan::BOBOT_NIK_TIDAK_COCOK)->toBe(30)
        ->and(LayananSkorKecurigaan::BOBOT_NAMA_TIDAK_COCOK)->toBe(20)
        ->and(LayananSkorKecurigaan::BOBOT_TANGGAL_TIDAK_COCOK)->toBe(15)
        ->and(LayananSkorKecurigaan::BOBOT_TEMPAT_TIDAK_COCOK)->toBe(10)
        ->and(LayananSkorKecurigaan::BOBOT_DOKUMEN_TIDAK_TERBACA)->toBe(10)
        ->and(LayananSkorKecurigaan::BOBOT_BERKAS_KURANG)->toBe(10)
        ->and(LayananSkorKecurigaan::BOBOT_SELISIH_TANGGAL_JANGGAL)->toBe(15)
        ->and(LayananSkorKecurigaan::BOBOT_TANGGAL_TIDAK_WAJAR)->toBe(20)
        ->and(LayananSkorKecurigaan::BOBOT_NIK_TIDAK_DITEMUKAN)->toBe(20);
});

it('menentukan kategori prioritas dari skor', function () {
    expect(LayananSkorKecurigaan::kategori(0))->toBe('rendah')
        ->and(LayananSkorKecurigaan::kategori(29))->toBe('rendah')
        ->and(LayananSkorKecurigaan::kategori(30))->toBe('sedang')
        ->and(LayananSkorKecurigaan::kategori(59))->toBe('sedang')
        ->and(LayananSkorKecurigaan::kategori(60))->toBe('tinggi')
        ->and(LayananSkorKecurigaan::kategori(100))->toBe('tinggi');
});
