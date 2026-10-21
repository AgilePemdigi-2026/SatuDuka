<?php

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\Ai\AIService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Laporan menunggu verifikasi dengan data almarhum dari data dummy SIAK.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanRingkasan(Pengguna $pemohon, array $timpa = []): LaporanKematian
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
 * Melampirkan Surat Keterangan Kematian pada penyimpanan lokal palsu.
 */
function lampirkanSuratRingkasan(LaporanKematian $laporan, string $namaFile = 'surat-cocok.pdf'): BerkasPendukung
{
    Storage::fake('local');

    $path = 'berkas_ringkasan/'.$laporan->id_laporan.'/'.Str::random(10).'.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 ISI-DOKUMEN-RINGKASAN');

    return BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'nama_file' => $namaFile,
        'path_file' => $path,
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
    ]);
}

/**
 * Menyusun struktur hasil perbandingan lengkap dengan nilai NIK asli.
 *
 * @param  array<string, string>  $status
 * @return array<string, mixed>
 */
function susunPerbandinganRingkasan(array $status = []): array
{
    $label = [
        'nama_almarhum' => 'Nama Almarhum/Almarhumah',
        'nik' => 'NIK',
        'tanggal_kematian' => 'Tanggal Kematian',
        'tempat_kematian' => 'Tempat Kematian',
    ];

    $nilai = [
        'nama_almarhum' => [
            'pemohon' => 'Suharto Wiryo',
            'ekstraksi' => 'Suharto Wiryo',
            'siak' => 'Suharto Wiryo',
        ],
        'nik' => [
            'pemohon' => '9999990101900101',
            'ekstraksi' => '9999990101900101',
            'siak' => '9999990101900101',
        ],
        'tanggal_kematian' => [
            'pemohon' => '2026-10-01',
            'ekstraksi' => '2026-10-01',
            'siak' => null,
        ],
        'tempat_kematian' => [
            'pemohon' => 'RSUD Kota Yogyakarta',
            'ekstraksi' => 'RSUD Kota Yogyakarta',
            'siak' => null,
        ],
    ];

    $status = array_merge([
        'nama_almarhum' => 'cocok',
        'nik' => 'cocok',
        'tanggal_kematian' => 'cocok',
        'tempat_kematian' => 'cocok',
    ], $status);

    $kolom = [];

    foreach ($status as $kunci => $nilaiStatus) {
        $kolom[] = [
            'kunci' => $kunci,
            'label' => $label[$kunci],
            'nilai_pemohon' => $nilai[$kunci]['pemohon'],
            'nilai_ekstraksi' => $nilai[$kunci]['ekstraksi'],
            'nilai_siak' => $nilai[$kunci]['siak'],
            'status' => $nilaiStatus,
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

/**
 * Menyimpan catatan perbandingan untuk laporan.
 *
 * @param  array<string, string>  $status
 */
function simpanPerbandinganRingkasan(LaporanKematian $laporan, array $status = []): void
{
    $hasil = susunPerbandinganRingkasan($status);

    AnalisisAi::create([
        'id_laporan' => $laporan->id_laporan,
        'id_berkas' => null,
        'jenis' => AnalisisAi::JENIS_PERBANDINGAN,
        'hasil' => $hasil,
        'skor' => 0.9,
        'skor_kecurigaan' => null,
        'model' => 'mock-ai-v1',
        'status' => AnalisisAi::STATUS_BERHASIL,
        'hash_berkas' => null,
        'dibuat_pada' => now(),
    ]);
}

/**
 * Mengaktifkan driver penyedia nyata dengan alamat uji.
 */
function aktifkanDriverOpenAiRingkasan(): void
{
    config([
        'ai.driver' => 'openai',
        'ai.openai.api_key' => 'kunci-uji',
        'ai.openai.base_url' => 'https://api.openai.test/v1',
        'ai.openai.model' => 'model-uji',
        'ai.timeout' => 5,
    ]);
}

/**
 * Respons palsu ringkasan ketidaksesuaian.
 */
function responRingkasanOpenAi(
    string $ringkasan = 'Ditemukan perbedaan pada NIK yang perlu diperiksa.',
    array $saran = ['Cocokkan NIK pada dokumen asli dengan data SIAK.'],
) {
    return Http::response([
        'output' => [[
            'type' => 'message',
            'content' => [[
                'type' => 'output_text',
                'text' => json_encode(['ringkasan' => $ringkasan, 'saran' => $saran]),
            ]],
        ]],
    ], 200);
}

it('menghasilkan ringkasan dan saran dengan mock saat data cocok', function () {
    $hasil = app(AIService::class)->ringkasKetidaksesuaian(susunPerbandinganRingkasan());

    expect($hasil['berhasil'])->toBeTrue()
        ->and($hasil['ringkasan'])->toContain('Tidak ditemukan ketidaksesuaian')
        ->and($hasil['saran'])->not->toBeEmpty();
});

it('menyebut kolom berbeda pada ringkasan mock', function () {
    $hasil = app(AIService::class)->ringkasKetidaksesuaian(susunPerbandinganRingkasan([
        'nik' => 'beda',
        'nama_almarhum' => 'tidak terbaca',
    ]));

    expect($hasil['ringkasan'])->toContain('NIK')
        ->and($hasil['ringkasan'])->toContain('Nama Almarhum')
        ->and($hasil['ringkasan'])->toContain('tidak terbaca')
        ->and(implode(' ', $hasil['saran']))->toContain('NIK');
});

it('memasker nik pada permintaan penyedia ringkasan', function () {
    aktifkanDriverOpenAiRingkasan();
    Http::fake(['https://api.openai.test/v1/responses' => responRingkasanOpenAi()]);

    $hasil = app(AIService::class)->ringkasKetidaksesuaian(susunPerbandinganRingkasan());

    expect($hasil['berhasil'])->toBeTrue()
        ->and($hasil['ringkasan'])->toContain('NIK');

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return str_contains($body, '999999010190****')
            && ! str_contains($body, '9999990101900101');
    });
});

it('membuat ringkasan ai untuk verifikator', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanRingkasan($pemohon);
    lampirkanSuratRingkasan($laporan);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.ringkasan_ai', $laporan))
        ->assertRedirect(route('verifikasi.detail', $laporan))
        ->assertSessionHas('sukses');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Ringkasan Ketidaksesuaian (AI)')
        ->assertSee('Tidak ditemukan ketidaksesuaian');

    expect(AnalisisAi::query()
        ->where('id_laporan', $laporan->id_laporan)
        ->where('jenis', AnalisisAi::JENIS_RINGKASAN)
        ->where('status', AnalisisAi::STATUS_BERHASIL)
        ->count())->toBe(1)
        ->and(LogAudit::query()
            ->where('aksi', 'membuat_ringkasan_ai')
            ->where('hasil', 'Sukses')
            ->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menampilkan skor aturan dan mencatat kegagalan ringkasan ai', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanRingkasan($pemohon);
    lampirkanSuratRingkasan($laporan);
    simpanPerbandinganRingkasan($laporan, ['nik' => 'beda']);

    aktifkanDriverOpenAiRingkasan();
    Http::fake([
        'https://api.openai.test/v1/responses' => Http::response(['error' => ['message' => 'gagal']], 500),
    ]);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.ringkasan_ai', $laporan))
        ->assertRedirect(route('verifikasi.detail', $laporan))
        ->assertSessionHas('galat', 'Analisis AI tidak tersedia, lanjutkan secara manual');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Skor Kecurigaan')
        ->assertSee('Analisis AI tidak tersedia, lanjutkan secara manual');

    $skor = AnalisisAi::query()
        ->where('id_laporan', $laporan->id_laporan)
        ->where('jenis', AnalisisAi::JENIS_SKOR)
        ->latest('id_analisis')
        ->first();

    expect($skor)->not->toBeNull()
        ->and((int) $skor->skor_kecurigaan)->toBe(40)
        ->and(AnalisisAi::query()
            ->where('id_laporan', $laporan->id_laporan)
            ->where('jenis', AnalisisAi::JENIS_RINGKASAN)
            ->where('status', AnalisisAi::STATUS_GAGAL)
            ->count())->toBe(1)
        ->and(LogAudit::query()
            ->where('aksi', 'membuat_ringkasan_ai')
            ->where('hasil', 'Gagal')
            ->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menolak ringkasan ai untuk pemohon dan pejabat', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    $laporan = buatLaporanRingkasan($pemohon);

    $this->actingAs($pemohon)
        ->post(route('verifikasi.ringkasan_ai', $laporan))
        ->assertForbidden();

    $this->actingAs($pejabat)
        ->post(route('verifikasi.ringkasan_ai', $laporan))
        ->assertForbidden();
});
