<?php

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Services\Dokumen\LayananBerkasContoh;
use App\Services\Dokumen\PembuatPdf;
use App\Support\PenyaringTeksDokumen;
use Illuminate\Support\Facades\Storage;

it('membuat isi PDF teks nyata untuk berkas surat dan dapat dibaca parser', function () {
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
    expect(Storage::disk('local')->exists($berkas->path_file))->toBeTrue();

    $teks = PenyaringTeksDokumen::teks($berkas->path_file, (string) Storage::disk('local')->get($berkas->path_file));

    expect($teks)->not->toBeNull();
    expect($teks)->toContain('SURAT KETERANGAN KEMATIAN');
    expect($teks)->toContain('3471031203559001');
    expect($teks)->toContain('Suharto Wiryo');
});

it('membuat gambar PNG dummy untuk KTP dan Kartu Keluarga', function () {
    Storage::fake('local');

    $laporan = LaporanKematian::factory()->terkirim()->create([
        'nama_almarhum' => 'Siti Aminah',
        'nik_almarhum' => '3471036407609002',
    ]);

    $layanan = new LayananBerkasContoh(app(PembuatPdf::class));

    $ktp = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_KTP_ALMARHUM,
        'path_file' => 'berkas/laporan/uji/ktp.png',
    ]);

    $kk = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_KARTU_KELUARGA,
        'path_file' => 'berkas/laporan/uji/kk.jpg',
    ]);

    expect($layanan->isi($ktp))->toBeTrue();
    expect($layanan->isi($kk))->toBeTrue();

    $png = (string) Storage::disk('local')->get($ktp->path_file);
    $jpg = (string) Storage::disk('local')->get($kk->path_file);

    expect(str_starts_with($png, "\x89PNG"))->toBeTrue();
    expect(str_starts_with($jpg, "\xFF\xD8"))->toBeTrue();
});

it('idempoten: berkas yang sudah ada dipakai ulang', function () {
    Storage::fake('local');

    $laporan = LaporanKematian::factory()->terkirim()->create();
    $berkas = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'path_file' => 'berkas/laporan/uji/ada.pdf',
    ]);

    $layanan = new LayananBerkasContoh(app(PembuatPdf::class));

    Storage::disk('local')->put($berkas->path_file, 'isi lama');

    expect($layanan->isi($berkas))->toBeTrue();
    expect(Storage::disk('local')->get($berkas->path_file))->toBe('isi lama');
});

it('perintah berkas:contoh mengisi berkas yang belum ada', function () {
    Storage::fake('local');

    $laporan = LaporanKematian::factory()->terkirim()->create();
    BerkasPendukung::factory()->count(3)->create(['id_laporan' => $laporan->id_laporan]);

    $this->artisan('berkas:contoh')->assertSuccessful();

    $berkas = BerkasPendukung::query()->where('id_laporan', $laporan->id_laporan)->get();

    expect($berkas->every(
        fn (BerkasPendukung $satuan): bool => Storage::disk('local')->exists($satuan->path_file),
    ))->toBeTrue();

    // Jalankan ulang: tidak ada yang dibuat lagi.
    $this->artisan('berkas:contoh')
        ->expectsOutputToContain('sudah ada: 3')
        ->assertSuccessful();
});

it('seeder berkas dummy mengisi seluruh laporan contoh', function () {
    Storage::fake('local');

    // Berkas contoh dari beberapa jenis dan ekstensi acak ala factory.
    BerkasPendukung::factory()->count(6)->create();

    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\BerkasDummySeeder']);

    $berkas = BerkasPendukung::query()->get();

    expect($berkas->isNotEmpty())->toBeTrue();
    expect($berkas->every(
        fn (BerkasPendukung $satuan): bool => Storage::disk('local')->exists($satuan->path_file),
    ))->toBeTrue();
});
