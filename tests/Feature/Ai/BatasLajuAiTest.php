<?php

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\Ai\AiRateLimit;
use App\Services\Ai\LayananAnalisisAi;
use App\Services\Dokumen\LayananBerkasContoh;
use App\Services\Dokumen\PembuatPdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Siapkan laporan draf pemohon dengan surat keterangan kematian bertext-layer.
 *
 * Driver DeepSeek dipakai karena tidak punya fallback permintaan tambahan,
 * sehingga jumlah permintaan HTTP dapat dihitung dengan pasti.
 *
 * @return array{0: Pengguna, 1: LaporanKematian, 2: Pengguna}
 */
function siapkanBatasLajuAi(): array
{
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();

    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'no_pengajuan' => null,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => now()->subDays(2)->toDateString(),
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
    ]);

    $berkas = BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'nama_file' => 'surat-batas-laju.pdf',
        'path_file' => 'berkas/laporan/uji/batas-laju.pdf',
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
    ]);

    (new LayananBerkasContoh(app(PembuatPdf::class)))->isi($berkas, true);

    config([
        'ai.driver' => 'deepseek',
        'ai.deepseek.api_key' => 'kunci-rahasia-uji',
        'ai.deepseek.base_url' => 'https://api.deepseek.test',
        'ai.deepseek.model' => 'model-uji-laju',
        'ai.timeout' => 5,
        'ai.retry.maks' => 2,
        // Jeda 0 agar tes tidak menunggu; perilaku retry tetap diuji.
        'ai.retry.jeda_ms' => 0,
        'ai.retry.anggaran_detik' => 5,
        'ai.retry.batas_eksekusi_php' => 180,
    ]);

    return [$pemohon, $laporan, $verifikator];
}

/**
 * Jawaban model yang berisi JSON hasil ekstraksi sesuai isian laporan.
 *
 * @return array<string, mixed>
 */
function jawabanBatasLajuAi(): array
{
    return [
        'choices' => [[
            'message' => [
                'content' => json_encode([
                    'nama_almarhum' => 'Suharto Wiryo',
                    'nik' => '3471031203559001',
                    'tanggal_kematian' => now()->subDays(2)->toDateString(),
                    'tempat_kematian' => 'RSUD Kota Yogyakarta',
                    'keyakinan' => [
                        'nama_almarhum' => 0.98,
                        'nik' => 0.99,
                        'tanggal_kematian' => 0.97,
                        'tempat_kematian' => 0.95,
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ],
        ]],
    ];
}

it('menandai tertunda, bukan gagal, saat penyedia membalas 429 tanpa Retry-After', function () {
    [$pemohon, $laporan] = siapkanBatasLajuAi();

    Http::fake([
        'api.deepseek.test/chat/completions' => Http::response(['error' => ['message' => 'rate limited']], 429),
    ]);

    $respons = $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan));

    $respons->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertSessionHas('info', fn ($pesan): bool => is_string($pesan) && str_contains($pesan, 'sibuk'))
        ->assertSessionMissing('galat');

    // 1 percobaan awal + maksimal 2 percobaan ulang.
    Http::assertSentCount(3);

    expect(AnalisisAi::query()->where('status', AnalisisAi::STATUS_TERTUNDA)->count())->toBe(1)
        ->and(AnalisisAi::query()->where('status', AnalisisAi::STATUS_GAGAL)->count())->toBe(0)
        ->and(LogAudit::query()->where('aksi', 'menganalisis_dokumen_ai')->where('hasil', 'Tertunda')->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF);

    $this->actingAs($pemohon)
        ->get(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertOk()
        ->assertSee('sibuk');
});

it('menghormati Retry-After dan tidak mengulang bila waktunya melebihi anggaran', function () {
    [$pemohon, $laporan] = siapkanBatasLajuAi();

    Http::fake([
        'api.deepseek.test/chat/completions' => Http::response(
            ['error' => ['message' => 'rate limited']],
            429,
            ['Retry-After' => '120'],
        ),
    ]);

    $respons = $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan));

    $respons->assertRedirect()
        ->assertSessionHas('info', fn ($pesan): bool => is_string($pesan) && str_contains($pesan, '120 detik'));

    // Retry-After 120 detik jauh melebihi anggaran 5 detik: jangan menunggu.
    Http::assertSentCount(1);

    expect(AnalisisAi::query()->where('status', AnalisisAi::STATUS_TERTUNDA)->count())->toBe(1)
        ->and(AnalisisAi::query()->where('status', AnalisisAi::STATUS_GAGAL)->count())->toBe(0);
});

it('berhasil saat percobaan ulang setelah 429 akhirnya dilayani', function () {
    [$pemohon, $laporan] = siapkanBatasLajuAi();

    Http::fakeSequence('api.deepseek.test/chat/completions')
        ->push(['error' => ['message' => 'rate limited']], 429)
        ->push(jawabanBatasLajuAi(), 200);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertSessionHas('sukses');

    Http::assertSentCount(2);

    expect(AnalisisAi::query()
        ->where('jenis', AnalisisAi::JENIS_EKSTRAKSI)
        ->where('status', AnalisisAi::STATUS_BERHASIL)
        ->count())->toBe(1)
        ->and(AnalisisAi::query()->where('status', AnalisisAi::STATUS_TERTUNDA)->count())->toBe(0)
        ->and(AnalisisAi::query()->where('status', AnalisisAi::STATUS_GAGAL)->count())->toBe(0);
});

it('tetap menandai gagal saat penyedia membalas 5xx', function () {
    [$pemohon, $laporan] = siapkanBatasLajuAi();

    Http::fake([
        'api.deepseek.test/chat/completions' => Http::response(['error' => ['message' => 'galat server']], 500),
    ]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect()
        ->assertSessionHas('galat', 'Analisis AI tidak tersedia, lanjutkan secara manual')
        ->assertSessionMissing('info');

    // 5xx bukan batas laju: tidak diulang.
    Http::assertSentCount(1);

    expect(AnalisisAi::query()->where('status', AnalisisAi::STATUS_GAGAL)->count())->toBe(1)
        ->and(AnalisisAi::query()->where('status', AnalisisAi::STATUS_TERTUNDA)->count())->toBe(0);
});

it('menandai gagal tanpa mengulang saat waktu tunggu habis', function () {
    [$pemohon, $laporan] = siapkanBatasLajuAi();

    Http::fake([
        'api.deepseek.test/chat/completions' => Http::failedConnection('Waktu habis'),
    ]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect()
        ->assertSessionHas('galat', 'Analisis AI tidak tersedia, lanjutkan secara manual');

    Http::assertSentCount(1);

    expect(AnalisisAi::query()->where('status', AnalisisAi::STATUS_GAGAL)->count())->toBe(1)
        ->and(AnalisisAi::query()->where('status', AnalisisAi::STATUS_TERTUNDA)->count())->toBe(0);
});

it('mencatat batas laju tanpa kunci api, nik, atau isi dokumen', function () {
    [$pemohon, $laporan] = siapkanBatasLajuAi();

    Http::fake([
        'api.deepseek.test/chat/completions' => Http::response(['error' => ['message' => 'rate limited']], 429),
    ]);

    Log::spy();

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();

    Log::shouldHaveReceived('warning')
        ->withArgs(function (string $pesan, array $konteks) use ($laporan): bool {
            expect($pesan)->toBe('Layanan AI sibuk (batas laju)')
                ->and($konteks['pengecualian'])->toBe(AiRateLimit::class)
                ->and($konteks['status_http'])->toBe(429)
                ->and($konteks['retry_after'])->toBeNull()
                ->and($konteks['model'])->toBe('model-uji-laju')
                ->and($konteks['pemicu'])->toBe('pemohon_review')
                ->and($konteks['tertunda'])->toBeTrue();

            $teks = json_encode($konteks);

            expect($teks)->not->toContain((string) $laporan->nik_almarhum)
                ->and($teks)->not->toContain('kunci-rahasia-uji')
                ->and($teks)->not->toContain('Suharto Wiryo');

            return true;
        })
        ->once();
});

it('menandai ringkasan tertunda saat penyedia sibuk', function () {
    [, $laporan, $verifikator] = siapkanBatasLajuAi();

    $laporan->update(['status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI]);

    Http::fake([
        'api.deepseek.test/chat/completions' => Http::response(['error' => ['message' => 'rate limited']], 429),
    ]);

    $layanan = app(LayananAnalisisAi::class);
    $hasil = $layanan->ringkas($laporan->fresh(), ['kolom' => []], $verifikator, 'verifikator_detail');

    expect($hasil['berhasil'])->toBeFalse()
        ->and($hasil['tertunda'])->toBeTrue()
        ->and($hasil['pesan'])->toContain('sibuk');

    expect(AnalisisAi::query()
        ->where('jenis', AnalisisAi::JENIS_RINGKASAN)
        ->where('status', AnalisisAi::STATUS_TERTUNDA)
        ->count())->toBe(1)
        ->and(AnalisisAi::query()->where('status', AnalisisAi::STATUS_GAGAL)->count())->toBe(0);
});
