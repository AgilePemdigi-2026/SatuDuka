<?php

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Services\Ai\AiTidakTersedia;
use App\Services\Ai\DeepSeekService;
use App\Services\Dokumen\LayananBerkasContoh;
use App\Services\Dokumen\PembuatPdf;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('mengirim teks dokumen ke DeepSeek dan mengurai hasil JSON', function () {
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

    // Berkas nyata di disk fake: PDF dummy teks yang dapat dibaca parser.
    expect((new LayananBerkasContoh(app(PembuatPdf::class)))->isi($berkas))->toBeTrue();

    $layanan = new DeepSeekService(
        apiKey: 'kunci-uji',
        baseUrl: 'https://api.deepseek.com',
        model: 'deepseek-chat',
        timeout: 10,
    );

    Http::fake([
        'api.deepseek.com/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '{"nama_almarhum": "Suharto Wiryo", "nik": "3471031203559001", '
                        .'"tanggal_kematian": "2026-09-01", "tempat_kematian": "RSUD Kota Yogyakarta", '
                        .'"keyakinan": {"nama_almarhum": 0.98, "nik": 0.99, "tanggal_kematian": 0.97, "tempat_kematian": 0.95}}',
                ],
            ]],
        ]),
    ]);

    $hasil = $layanan->ekstrakSuratKematian($berkas);

    expect($hasil['berhasil'])->toBeTrue();
    expect($hasil['kolom']['nik']['nilai'])->toBe('3471031203559001');
    expect($hasil['kolom']['nama_almarhum']['nilai'])->toBe('Suharto Wiryo');
    expect($hasil['skor'])->toBeGreaterThan(0.9);

    Http::assertSent(function (Request $request): bool {
        expect($request->header('Authorization')[0])->toBe('Bearer kunci-uji');
        expect($request->data()['model'])->toBe('deepseek-chat');
        expect($request->data()['response_format'])->toBe(['type' => 'json_object']);
        expect($request->data()['messages'][0]['content'])->toContain('surat keterangan kematian');

        return true;
    });
});

it('memilih teks PDF nyata dari berkas yang tersimpan', function () {
    Storage::fake('local');

    $laporan = LaporanKematian::factory()->terkirim()->create([
        'nama_almarhum' => 'Siti Aminah',
        'nik_almarhum' => '3471036407609002',
    ]);

    $berkas = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'path_file' => 'berkas/laporan/uji/suratum.pdf',
    ]);

    $layanan = new DeepSeekService('kunci-uji', 'https://api.deepseek.com', 'deepseek-chat', 10);

    $isiDokumen = app(PembuatPdf::class)->hasil('dokumen.berkas_contoh_pdf', [
        'judul' => 'SURAT KETERANGAN KEMATIAN',
        'instansi' => 'RSUD Kota Yogyakarta',
        'alamatInstansi' => 'Jl. Marhaen No. 4',
        'nomor' => 'SKK-2026-0099',
        'baris' => [
            ['label' => 'Nama Almarhum/Almarhumah', 'nilai' => 'Siti Aminah'],
            ['label' => 'NIK', 'nilai' => '3471036407609002'],
            ['label' => 'Tanggal Kematian', 'nilai' => '24 Juli 2026'],
            ['label' => 'Tempat Kematian', 'nilai' => 'RSUD Kota Yogyakarta'],
        ],
        'paragraf' => ['Dokumen simulasi.'],
        'ttd' => ['kota' => 'Yogyakarta', 'tanggal' => '24 Juli 2026', 'jabatan' => 'Dokter', 'nama' => 'dr. Contoh', 'nip' => '-'],
    ]);

    Storage::disk('local')->put($berkas->path_file, $isiDokumen);

    Http::fake([
        'api.deepseek.com/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => '{"nama_almarhum": "Siti Aminah", "nik": "3471036407609002", "tanggal_kematian": "2026-07-24", "tempat_kematian": "RSUD Kota Yogyakarta", "keyakinan": {"nama_almarhum": 0.9, "nik": 0.9, "tanggal_kematian": 0.9, "tempat_kematian": 0.9}}']]],
        ]),
    ]);

    $layanan->ekstrakSuratKematian($berkas);

    Http::assertSent(fn (Request $request): bool => str_contains(
        (string) $request->data()['messages'][0]['content'],
        '3471036407609002',
    ));
});

it('membuang pagar markdown pada jawaban model', function () {
    $layanan = new DeepSeekService('kunci-uji', 'https://api.deepseek.com', 'deepseek-chat', 10);

    Http::fake([
        'api.deepseek.com/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => "```json\n{\"ringkasan\": \"Ada 1 kolom berbeda.\", \"saran\": [\"Cocokkan NIK.\"]}\n```"]],
            ]]),
    ]);

    $hasil = $layanan->ringkasKetidaksesuaian(['kolom' => []]);

    expect($hasil['berhasil'])->toBeTrue();
    expect($hasil['ringkasan'])->toBe('Ada 1 kolom berbeda.');
    expect($hasil['saran'])->toBe(['Cocokkan NIK.']);
});

it('melempar AiTidakTersedia saat kunci kosong atau layanan gagal', function () {
    $tanpaKunci = new DeepSeekService('', 'https://api.deepseek.com', 'deepseek-chat', 10);

    $laporan = LaporanKematian::factory()->terkirim()->create();
    $berkas = BerkasPendukung::factory()->create(['id_laporan' => $laporan->id_laporan]);

    $tanpaKunci->ekstrakSuratKematian($berkas);
})->throws(AiTidakTersedia::class, 'Kunci layanan AI belum dikonfigurasi.');

it('melempar AiTidakTersedia saat dokumen gagal diubah ke teks', function () {
    Storage::fake('local');

    $laporan = LaporanKematian::factory()->terkirim()->create();
    $berkas = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'path_file' => 'berkas/laporan/uji/gambar.png',
    ]);

    Storage::disk('local')->put($berkas->path_file, 'bukan gambar sah');

    $layanan = new DeepSeekService('kunci-uji', 'https://api.deepseek.com', 'deepseek-chat', 10);

    $layanan->ekstrakSuratKematian($berkas);
})->throws(AiTidakTersedia::class);
