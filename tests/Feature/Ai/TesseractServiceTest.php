<?php

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Services\Ai\AiTidakTersedia;
use App\Services\Ai\TesseractService;
use App\Services\Dokumen\LayananBerkasContoh;
use App\Services\Dokumen\PembuatPdf;
use App\Support\PenyaringTeksDokumen;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Apakah program tesseract tersedia di lingkungan uji.
 */
function tesseractTersedia(): bool
{
    try {
        return Process::run([
            (string) config('ai.tesseract.binary', 'tesseract'),
            '--version',
        ])->successful();
    } catch (Throwable) {
        return false;
    }
}

it('mengekstrak surat keterangan kematian dari PDF tanpa memanggil jaringan', function () {
    Storage::fake('local');

    $laporan = LaporanKematian::factory()->terkirim()->create([
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-09-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
    ]);

    $berkas = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'path_file' => 'berkas/laporan/uji/surat.pdf',
    ]);

    $layanan = new LayananBerkasContoh(app(PembuatPdf::class));
    expect($layanan->isi($berkas))->toBeTrue();

    Http::fake();

    $hasil = (new TesseractService)->ekstrakSuratKematian($berkas);

    expect($hasil['berhasil'])->toBeTrue();
    expect($hasil['kolom']['nik']['nilai'])->toBe('3471031203559001');
    expect($hasil['kolom']['nama_almarhum']['nilai'])->toBe('Suharto Wiryo');
    expect($hasil['kolom']['tanggal_kematian']['nilai'])->toBe('2026-09-01');

    // Tidak ada panggilan jaringan yang terjadi.
    Http::assertNothingSent();
});

it('membaca KTP dummy lewat tesseract bila program terpasang', function () {
    if (! tesseractTersedia()) {
        $this->markTestSkipped('Program tesseract tidak terpasang di lingkungan uji.');
    }

    Storage::fake('local');

    $laporan = LaporanKematian::factory()->terkirim()->create([
        'nama_almarhum' => 'Siti Aminah',
        'nik_almarhum' => '3471036407609002',
    ]);

    $berkas = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_KTP_ALMARHUM,
        'path_file' => 'berkas/laporan/uji/ktp.png',
    ]);

    $layanan = new LayananBerkasContoh(app(PembuatPdf::class));
    expect($layanan->isi($berkas))->toBeTrue();

    $hasil = (new TesseractService)->ekstrakSuratKematian($berkas);

    expect($hasil['berhasil'])->toBeTrue();
    expect($hasil['kolom']['nik']['nilai'])->toBe('3471036407609002');
    expect($hasil['kolom']['nama_almarhum']['nilai'])->toContain('Siti');
});

it('melempar AiTidakTersedia saat OCR gagal membaca berkas', function () {
    Storage::fake('local');

    $laporan = LaporanKematian::factory()->terkirim()->create();
    $berkas = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'path_file' => 'berkas/laporan/uji/gambar.png',
    ]);

    Storage::disk('local')->put($berkas->path_file, 'bukan gambar');

    (new TesseractService)->ekstrakSuratKematian($berkas);
})->throws(AiTidakTersedia::class);

it('membuat ringkasan deterministik dari data perbandingan', function () {
    $hasil = (new TesseractService)->ringkasKetidaksesuaian([
        'kolom' => [
            ['kunci' => 'nik', 'label' => 'NIK', 'status' => 'beda'],
            ['kunci' => 'tempat_kematian', 'label' => 'Tempat Kematian', 'status' => 'tidak terbaca'],
            ['kunci' => 'nama_almarhum', 'label' => 'Nama Almarhum/Almarhumah', 'status' => 'cocok'],
        ],
    ]);

    expect($hasil['berhasil'])->toBeTrue();
    expect($hasil['ringkasan'])->toContain('1 kolom berbeda');
    expect($hasil['ringkasan'])->toContain('tidak terbaca');
    expect($hasil['saran'])->toContain('Cocokkan NIK pada dokumen asli dengan isian pemohon sebelum melanjutkan.');
    expect(PenyaringTeksDokumen::teks('uji.docx', 'isi'))->toBeNull();
});
