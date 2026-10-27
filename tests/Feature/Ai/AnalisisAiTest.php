<?php

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Laporan draf milik pemohon dengan data almarhum dari data dummy SIAK.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanAi(Pengguna $pemohon, array $timpa = []): LaporanKematian
{
    return LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'no_pengajuan' => null,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
    ], $timpa));
}

/**
 * Melampirkan satu berkas pendukung pada penyimpanan lokal palsu.
 */
function lampirkanBerkasAi(
    LaporanKematian $laporan,
    string $jenis,
    string $namaFile,
    string $isi = '%PDF-1.4 ISI-DOKUMEN-RAHASIA',
): BerkasPendukung {
    $ekstensi = pathinfo($namaFile, PATHINFO_EXTENSION) ?: 'pdf';
    $path = 'berkas_laporan/'.$laporan->id_laporan.'/'.Str::random(10).'.'.$ekstensi;
    Storage::disk('local')->put($path, $isi);

    return BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => $jenis,
        'nama_file' => $namaFile,
        'path_file' => $path,
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
    ]);
}

/**
 * Melengkapi satu bukti kematian dan berkas wajib lainnya.
 */
function lengkapiBerkasAi(LaporanKematian $laporan, string $varian = 'cocok'): void
{
    $jenisWajib = array_merge(
        [config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0]],
        config('verifikasi.berkas_wajib.lainnya'),
    );

    foreach ($jenisWajib as $urutan => $jenis) {
        lampirkanBerkasAi($laporan, $jenis, 'berkas-'.$urutan.'-'.$varian.'.pdf');
    }
}

/**
 * Mengaktifkan driver penyedia nyata dengan alamat uji.
 */
function aktifkanDriverOpenAi(): void
{
    config([
        'ai.driver' => 'openai',
        'ai.openai.api_key' => 'kunci-uji',
        'ai.openai.base_url' => 'https://api.openai.test/v1',
        'ai.openai.model' => 'model-uji',
        'ai.timeout' => 2,
    ]);
}

/**
 * Respons palsu Responses API yang berisi JSON hasil ekstraksi.
 *
 * @param  array<string, mixed>|null  $kolom
 */
function responOpenAi(?array $kolom = null)
{
    $data = $kolom ?? [
        'nama_almarhum' => 'Suharto Wiryo',
        'nik' => '3471031203559001',
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'keyakinan' => [
            'nama_almarhum' => 0.99,
            'nik' => 0.99,
            'tanggal_kematian' => 0.98,
            'tempat_kematian' => 0.97,
        ],
    ];

    return Http::response([
        'output' => [[
            'type' => 'message',
            'content' => [[
                'type' => 'output_text',
                'text' => json_encode($data),
            ]],
        ]],
    ], 200);
}

it('menampilkan tombol periksa dokumen ai pada langkah empat', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-cocok.pdf');

    $this->actingAs($pemohon)
        ->get(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertOk()
        ->assertSee('Periksa dokumen (AI)')
        ->assertSee('Hasil bantuan AI, hanya saran');
});

it('menampilkan perbandingan berdampingan saat data cocok', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-cocok.pdf');

    $respons = $this->actingAs($pemohon)
        ->post(route('laporan.periksa_ai', $laporan));

    $respons->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertSessionHas('analisis_ai');

    $this->get(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertOk()
        ->assertSee('Hasil bantuan AI, hanya saran')
        ->assertSee('Suharto Wiryo')
        ->assertSee('cocok');

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF)
        ->and(AnalisisAi::query()->where('jenis', AnalisisAi::JENIS_EKSTRAKSI)->count())->toBe(1)
        ->and(AnalisisAi::query()->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)->count())->toBe(1);

    $audit = LogAudit::query()->where('aksi', 'menganalisis_dokumen_ai')->firstOrFail();
    expect($audit->layanan_eksternal)->toBe(LogAudit::LAYANAN_AI)
        ->and($audit->detail)->toMatchArray([
            'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
            'model' => 'mock-ai-v1',
            'pemicu' => 'pemohon_review',
        ])
        ->and(json_encode($audit->detail))->not->toContain($laporan->nik_almarhum)
        ->and(json_encode($audit->detail))->not->toContain('ISI-DOKUMEN-RAHASIA');
});

it('menampilkan peringatan saat hasil ekstraksi berbeda', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-beda.pdf');

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();

    $this->get(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertOk()
        ->assertSee('beda')
        ->assertSee('Ada kolom yang perlu diperiksa')
        ->assertSee('Hasil ekstraksi berbeda dengan isian pemohon.');
});

it('menampilkan status tidak terbaca saat dokumen tidak terbaca', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-tidak-terbaca.pdf');

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();

    $this->get(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertOk()
        ->assertSee('tidak terbaca')
        ->assertSee('Dokumen tidak terbaca oleh layanan AI.');
});

it('menampilkan pesan manual saat layanan ai gagal', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-cocok.pdf');

    aktifkanDriverOpenAi();
    Http::fake([
        'https://api.openai.test/v1/responses' => Http::response(['error' => ['message' => 'gagal']], 500),
    ]);

    $respons = $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan));

    $respons->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertSessionHas('galat', 'Analisis AI tidak tersedia, lanjutkan secara manual');

    $this->get(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertOk()
        ->assertSee('Analisis AI tidak tersedia, lanjutkan secara manual');

    expect(AnalisisAi::query()->where('status', AnalisisAi::STATUS_GAGAL)->count())->toBe(1)
        ->and(LogAudit::query()->where('aksi', 'menganalisis_dokumen_ai')->where('hasil', 'Gagal')->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF);
});

it('menangani waktu tunggu layanan ai tanpa menolak pengajuan', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lengkapiBerkasAi($laporan);

    aktifkanDriverOpenAi();
    Http::fake(fn () => throw new ConnectionException('Waktu habis'));

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect()
        ->assertSessionHas('galat', 'Analisis AI tidak tersedia, lanjutkan secara manual');

    $this->actingAs($pemohon)->post(route('laporan.kirim', $laporan), [
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
        'pernyataan' => '1',
    ])->assertRedirect(route('laporan.lihat', $laporan));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->fresh()->no_pengajuan)->not->toBeNull();
});

it('mengirim permintaan penyedia nyata tanpa nik di luar dokumen', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-cocok.pdf');

    aktifkanDriverOpenAi();
    Http::fake(['https://api.openai.test/v1/responses' => responOpenAi()]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();

    Http::assertSent(function (Request $request) use ($laporan): bool {
        $body = $request->body();

        return str_contains($body, 'input_file')
            && str_contains($body, 'ekstraksi_surat_kematian')
            && ! str_contains($body, $laporan->nik_almarhum);
    });

    expect(AnalisisAi::query()->where('model', 'model-uji')->where('status', AnalisisAi::STATUS_BERHASIL)->count())->toBe(2);
});

it('menggunakan cache untuk berkas dengan hash yang sama', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-cocok.pdf');

    aktifkanDriverOpenAi();
    Http::fake(['https://api.openai.test/v1/responses' => responOpenAi()]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();
    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();

    Http::assertSentCount(1);
});

it('membatasi laju pemanggilan layanan ai per pengguna', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-cocok.pdf');

    config(['ai.rate_limit.max' => 1]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect()
        ->assertSessionHas('galat', 'Analisis AI tidak tersedia, lanjutkan secara manual');
});

it('menolak akses ai untuk pemohon lain', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $pemohonLain = Pengguna::factory()->pemohon()->create();
    $laporan = buatLaporanAi($pemohonLain);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-cocok.pdf');

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertNotFound();
});

it('menolak akses ai untuk peran lain', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAi($pemohon);
    lampirkanBerkasAi($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'surat-cocok.pdf');

    $this->actingAs($verifikator)->post(route('laporan.periksa_ai', $laporan))->assertForbidden();
});
