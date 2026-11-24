<?php

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Services\Dokumen\LayananBerkasContoh;
use App\Services\Dokumen\PembuatPdf;
use App\Support\NikDummy;
use App\Support\PenyaringTeksDokumen;
use Illuminate\Support\Facades\Storage;

/**
 * Laporan dummy dengan data almarhum yang berbeda dari data pelapor.
 */
function laporanPemilihanTemplat(): LaporanKematian
{
    return LaporanKematian::factory()->terkirim()->create([
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-09-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
    ]);
}

/**
 * Berkas pendukung dummy dengan jenis dokumen apa adanya (boleh huruf kecil).
 */
function berkasPemilihanTemplat(LaporanKematian $laporan, string $jenis, string $path): BerkasPendukung
{
    return BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => $jenis,
        'path_file' => $path,
    ]);
}

/**
 * Teks PDF hasil generator contoh.
 */
function teksBerkasContoh(BerkasPendukung $berkas): string
{
    $isi = (string) Storage::disk('local')->get($berkas->path_file);

    return (string) PenyaringTeksDokumen::teks($berkas->path_file, $isi);
}

it('memilih templat yang sama tanpa membedakan huruf besar/kecil dan spasi tepi', function (string $jenis) {
    Storage::fake('local');

    $laporan = laporanPemilihanTemplat();
    $berkas = berkasPemilihanTemplat($laporan, $jenis, 'berkas/laporan/uji/kasus.pdf');

    $layanan = new LayananBerkasContoh(app(PembuatPdf::class));

    expect($layanan->isi($berkas))->toBeTrue();

    $teks = teksBerkasContoh($berkas);

    expect($teks)->toContain('SURAT KETERANGAN KEMATIAN')
        ->and($teks)->toContain('3471031203559001')
        ->and($teks)->not->toContain('FOTOKOPI KARTU TANDA PENDUDUK');
})->with([
    'huruf besar di awal kata' => 'Surat Keterangan Kematian',
    'huruf kecil semua' => 'surat keterangan kematian',
    'huruf besar semua' => 'SURAT KETERANGAN KEMATIAN',
    'huruf campuran' => 'SuRaT kEtErAnGaN kEmAtIaN',
    'spasi berlebih di tepi' => '   Surat Keterangan Kematian   ',
]);

it('memilih templat yang benar untuk setiap jenis dokumen valid', function (string $jenis, string $judul, string $sumberNik) {
    Storage::fake('local');

    $laporan = laporanPemilihanTemplat();
    $berkas = berkasPemilihanTemplat($laporan, $jenis, 'berkas/laporan/uji/templat.pdf');

    $layanan = new LayananBerkasContoh(app(PembuatPdf::class));

    expect($layanan->isi($berkas))->toBeTrue();

    $teks = teksBerkasContoh($berkas);

    $nikDiharapkan = match ($sumberNik) {
        'almarhum' => '3471031203559001',
        'kk' => NikDummy::denganUrutan(7000 + (int) $berkas->id_berkas, 10, 10, 1975),
        default => NikDummy::denganUrutan(5000 + (int) $berkas->id_berkas, 5, 6, 1988),
    };

    expect($teks)->toContain($judul)
        ->and($teks)->toContain((string) $nikDiharapkan);
})->with([
    'surat keterangan kematian' => [BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'SURAT KETERANGAN KEMATIAN', 'almarhum'],
    'ktp almarhum' => [BerkasPendukung::JENIS_KTP_ALMARHUM, 'FOTOKOPI KARTU TANDA PENDUDUK', 'almarhum'],
    'ktp pelapor' => [BerkasPendukung::JENIS_KTP_PELAPOR, 'FOTOKOPI KARTU TANDA PENDUDUK', 'pelapor'],
    'kartu keluarga' => [BerkasPendukung::JENIS_KARTU_KELUARGA, 'FOTOKOPI KARTU KELUARGA', 'kk'],
    'surat keterangan kelurahan' => [BerkasPendukung::JENIS_SURAT_KETERANGAN_KELURAHAN, 'SURAT KETERANGAN KELURAHAN', 'almarhum'],
    'sptjm kebenaran data kematian' => [BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN, 'SPTJM KEBENARAN DATA KEMATIAN', 'pelapor'],
    'surat keterangan kepolisian' => [BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN, 'SURAT KETERANGAN KEPOLISIAN', 'almarhum'],
    'ktp saksi 1' => [BerkasPendukung::JENIS_KTP_SAKSI_1, 'FOTOKOPI KARTU TANDA PENDUDUK', 'pelapor'],
    'ktp saksi 2' => [BerkasPendukung::JENIS_KTP_SAKSI_2, 'FOTOKOPI KARTU TANDA PENDUDUK', 'pelapor'],
]);

it('memilih templat gambar yang sama tanpa membedakan huruf besar/kecil', function () {
    Storage::fake('local');

    $layanan = new LayananBerkasContoh(app(PembuatPdf::class));

    // Model tanpa laporan: seluruh isi gambar hanya bergantung pada jenis
    // dokumen, sehingga dua render dapat dibandingkan byte per byte.
    $buat = function (string $jenis, string $path): BerkasPendukung {
        $berkas = new BerkasPendukung;
        $berkas->jenis_dokumen = $jenis;
        $berkas->path_file = $path;
        $berkas->status_unggahan = BerkasPendukung::STATUS_BERHASIL;

        return $berkas;
    };

    $kecil = $buat('surat keterangan kematian', 'berkas/laporan/uji/kecil.png');
    $baku = $buat('Surat Keterangan Kematian', 'berkas/laporan/uji/baku.png');
    $kepolisian = $buat(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN, 'berkas/laporan/uji/kepolisian.png');

    foreach ([$kecil, $baku, $kepolisian] as $berkas) {
        expect($layanan->isi($berkas))->toBeTrue();
    }

    $isi = fn (BerkasPendukung $berkas): string => (string) Storage::disk('local')->get($berkas->path_file);

    // Huruf kecil harus memakai templat baku, bukan templat default (kepolisian).
    expect($isi($kecil))->toBe($isi($baku))
        ->and($isi($kecil))->not->toBe($isi($kepolisian));
});

it('menolak jenis dokumen tak dikenal dengan pesan yang menyebut nilai dan daftar valid', function () {
    Storage::fake('local');

    $laporan = laporanPemilihanTemplat();
    $berkas = berkasPemilihanTemplat($laporan, 'sertifikat kematian', 'berkas/laporan/uji/asing.pdf');

    $layanan = new LayananBerkasContoh(app(PembuatPdf::class));

    $galat = null;

    try {
        $layanan->isi($berkas);
    } catch (InvalidArgumentException $e) {
        $galat = $e;
    }

    expect($galat)->toBeInstanceOf(InvalidArgumentException::class)
        ->and($galat->getMessage())->toContain('sertifikat kematian')
        ->and($galat->getMessage())->toContain('Nilai valid')
        ->and($galat->getMessage())->toContain(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);

    // Tidak jatuh diam-diam ke templat default dan tidak menulis berkas.
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('perintah berkas:contoh gagal tanpa membuat berkas bila ada jenis tak dikenal', function () {
    Storage::fake('local');

    $laporan = laporanPemilihanTemplat();

    // Berkas sah lebih dulu (id lebih kecil), berkas bermasalah di akhir:
    // tidak boleh ada berkas yang terbuat setengah jadi.
    berkasPemilihanTemplat($laporan, 'surat keterangan kematian', 'berkas/laporan/uji/pertama.pdf');
    berkasPemilihanTemplat($laporan, 'sertifikat kematian', 'berkas/laporan/uji/kedua.pdf');

    $this->artisan('berkas:contoh')
        ->expectsOutputToContain('tidak dikenali')
        ->assertFailed();

    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('perintah berkas:contoh --semua tidak menimpa berkas bila ada jenis tak dikenal', function () {
    Storage::fake('local');

    $laporan = laporanPemilihanTemplat();

    $pertama = berkasPemilihanTemplat($laporan, 'surat keterangan kematian', 'berkas/laporan/uji/pertama.pdf');
    $kedua = berkasPemilihanTemplat($laporan, 'sertifikat kematian', 'berkas/laporan/uji/kedua.pdf');

    Storage::disk('local')->put($pertama->path_file, 'isi lama pertama');
    Storage::disk('local')->put($kedua->path_file, 'isi lama kedua');

    $this->artisan('berkas:contoh', ['--semua' => true])
        ->expectsOutputToContain('tidak dikenali')
        ->assertFailed();

    expect(Storage::disk('local')->get($pertama->path_file))->toBe('isi lama pertama')
        ->and(Storage::disk('local')->get($kedua->path_file))->toBe('isi lama kedua');
});

it('perintah berkas:contoh mengisi berkas huruf kecil dengan templat yang benar', function () {
    Storage::fake('local');

    $laporan = laporanPemilihanTemplat();
    $berkas = berkasPemilihanTemplat($laporan, 'surat keterangan kematian', 'berkas/laporan/uji/perintah.pdf');

    $this->artisan('berkas:contoh')
        ->expectsOutputToContain('Berkas contoh baru: 1')
        ->assertSuccessful();

    expect(Storage::disk('local')->exists($berkas->path_file))->toBeTrue()
        ->and(teksBerkasContoh($berkas))->toContain('SURAT KETERANGAN KEMATIAN');
});
