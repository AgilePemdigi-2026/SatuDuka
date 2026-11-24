<?php

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Services\Ai\PemilihBerkasAnalisis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Laporan draf milik pemohon untuk pengujian pemilihan berkas AI.
 */
function laporanPemilihanBerkas(Pengguna $pemohon): LaporanKematian
{
    return LaporanKematian::factory()->create([
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
}

/**
 * Melampirkan satu berkas pendukung berstatus unggahan berhasil.
 */
function berkasPemilihan(LaporanKematian $laporan, string $jenis, string $namaFile): BerkasPendukung
{
    $path = 'berkas_laporan/'.$laporan->id_laporan.'/'.Str::random(10).'.pdf';
    // Isi berbeda per berkas agar cache ekstraksi berbasis hash tidak menyatukan
    // beberapa berkas dalam satu panggilan.
    Storage::disk('local')->put($path, '%PDF-1.4 ISI-DOKUMEN-RAHASIA '.$namaFile);

    return BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => $jenis,
        'nama_file' => $namaFile,
        'path_file' => $path,
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
    ]);
}

/**
 * Catat satu baris analisis AI untuk sebuah berkas.
 */
function catatAnalisisPemilihan(BerkasPendukung $berkas, string $status): AnalisisAi
{
    return AnalisisAi::factory()->create([
        'id_laporan' => $berkas->id_laporan,
        'id_berkas' => $berkas->id_berkas,
        'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
        'status' => $status,
        'skor' => $status === AnalisisAi::STATUS_BERHASIL ? 0.9 : null,
    ]);
}

/**
 * Mengaktifkan driver penyedia nyata dengan alamat uji tanpa percobaan ulang.
 */
function aktifkanDriverPemilihan(): void
{
    config([
        'ai.driver' => 'openai',
        'ai.openai.api_key' => 'kunci-uji',
        'ai.openai.base_url' => 'https://api.openai.test/v1',
        'ai.openai.model' => 'model-uji',
        'ai.timeout' => 2,
        'ai.retry.maks' => 0,
    ]);
}

/**
 * Badan respons Responses API berisi JSON hasil ekstraksi.
 *
 * @return array<string, mixed>
 */
function badanResponPemilihan(): array
{
    $data = [
        'nama_almarhum' => 'Suharto Wiryo',
        'nik' => '3471031203559001',
        'tanggal_kematian' => now()->subDays(2)->toDateString(),
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'keyakinan' => [
            'nama_almarhum' => 0.99,
            'nik' => 0.99,
            'tanggal_kematian' => 0.98,
            'tempat_kematian' => 0.97,
        ],
    ];

    return [
        'output' => [[
            'type' => 'message',
            'content' => [[
                'type' => 'output_text',
                'text' => json_encode($data),
            ]],
        ]],
    ];
}

/**
 * Respons palsu Responses API berisi JSON hasil ekstraksi.
 */
function responPemilihan()
{
    return Http::response(badanResponPemilihan(), 200);
}

/**
 * Lima jenis berkas yang dipakai aturan verifikasi.
 *
 * @return list<string>
 */
function jenisRelevanPemilihan(): array
{
    return [
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        BerkasPendukung::JENIS_KTP_ALMARHUM,
        BerkasPendukung::JENIS_KARTU_KELUARGA,
        BerkasPendukung::JENIS_KTP_PELAPOR,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KELURAHAN,
    ];
}

it('membedakan berkas relevan dan tidak relevan pada pemilihan', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    $skk = berkasPemilihan($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'skk.pdf');
    $saksi = berkasPemilihan($laporan, BerkasPendukung::JENIS_KTP_SAKSI_1, 'saksi.pdf');

    $pilihan = app(PemilihBerkasAnalisis::class)->pilih($laporan);

    expect($pilihan['akan_diperiksa'])->toHaveCount(1)
        ->and($pilihan['akan_diperiksa'][0]['berkas']->id_berkas)->toBe($skk->id_berkas)
        ->and($pilihan['akan_diperiksa'][0]['alasan'])->toBe(PemilihBerkasAnalisis::ALASAN_RELEVAN)
        ->and($pilihan['belum_diperiksa'])->toBe([])
        ->and($pilihan['dilewati'])->toHaveCount(1)
        ->and($pilihan['dilewati'][0]['berkas']->id_berkas)->toBe($saksi->id_berkas)
        ->and($pilihan['dilewati'][0]['alasan'])->toBe(PemilihBerkasAnalisis::ALASAN_TIDAK_RELEVAN);
});

it('menganggap berkas saksi relevan saat wajib saksi aktif', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    config(['verifikasi.wajib_saksi' => true]);

    berkasPemilihan($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'skk.pdf');
    berkasPemilihan($laporan, BerkasPendukung::JENIS_KTP_SAKSI_1, 'saksi-1.pdf');
    berkasPemilihan($laporan, BerkasPendukung::JENIS_KTP_SAKSI_2, 'saksi-2.pdf');

    $pilihan = app(PemilihBerkasAnalisis::class)->pilih($laporan);

    expect($pilihan['akan_diperiksa'])->toHaveCount(3)
        ->and($pilihan['dilewati'])->toBe([]);
});

it('melewati berkas yang analisis terakhirnya berhasil', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    $skk = berkasPemilihan($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'skk.pdf');
    catatAnalisisPemilihan($skk, AnalisisAi::STATUS_BERHASIL);

    $pilihan = app(PemilihBerkasAnalisis::class)->pilih($laporan);

    expect($pilihan['akan_diperiksa'])->toBe([])
        ->and($pilihan['dilewati'])->toHaveCount(1)
        ->and($pilihan['dilewati'][0]['alasan'])->toBe(PemilihBerkasAnalisis::ALASAN_SUDAH_DIPERIKSA);
});

it('mengizinkan berkas gagal dan tertunda dianalisis lagi', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    $gagal = berkasPemilihan($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'gagal.pdf');
    $tertunda = berkasPemilihan($laporan, BerkasPendukung::JENIS_KTP_ALMARHUM, 'tertunda.pdf');

    catatAnalisisPemilihan($gagal, AnalisisAi::STATUS_GAGAL);
    catatAnalisisPemilihan($tertunda, AnalisisAi::STATUS_TERTUNDA);

    $pilihan = app(PemilihBerkasAnalisis::class)->pilih($laporan);

    expect($pilihan['akan_diperiksa'])->toHaveCount(2)
        ->and($pilihan['dilewati'])->toBe([]);
});

it('membatasi tiga berkas per permintaan pada pemilihan', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    foreach (jenisRelevanPemilihan() as $urutan => $jenis) {
        berkasPemilihan($laporan, $jenis, 'berkas-'.$urutan.'.pdf');
    }

    $pilihan = app(PemilihBerkasAnalisis::class)->pilih($laporan);

    expect($pilihan['akan_diperiksa'])->toHaveCount(PemilihBerkasAnalisis::BATAS_PER_PERMINTAAN)
        ->and($pilihan['belum_diperiksa'])->toHaveCount(2)
        ->and($pilihan['belum_diperiksa'][0]['alasan'])->toBe(PemilihBerkasAnalisis::ALASAN_BATAS_PERMINTAAN);
});

it('hanya menganalisis berkas relevan dan tidak mengirim berkas tak relevan', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    $skk = berkasPemilihan($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'skk.pdf');
    $saksi = berkasPemilihan($laporan, BerkasPendukung::JENIS_KTP_SAKSI_1, 'saksi.pdf');

    aktifkanDriverPemilihan();
    Http::fake(['https://api.openai.test/v1/responses' => responPemilihan()]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertSessionHas('sukses');

    // Satu panggilan untuk satu berkas relevan; berkas saksi tidak dikirim.
    Http::assertSentCount(1);

    expect(AnalisisAi::query()->where('id_berkas', $skk->id_berkas)->count())->toBe(2)
        ->and(AnalisisAi::query()->where('id_berkas', $saksi->id_berkas)->count())->toBe(0);
});

it('tidak memanggil ai saat semua berkas relevan sudah diperiksa', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    $skk = berkasPemilihan($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'skk.pdf');
    catatAnalisisPemilihan($skk, AnalisisAi::STATUS_BERHASIL);

    aktifkanDriverPemilihan();
    Http::fake();

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertSessionHas('info', 'Semua berkas relevan sudah diperiksa. Tidak ada berkas yang perlu dianalisis.');

    Http::assertNothingSent();

    expect(AnalisisAi::query()->count())->toBe(1);
});

it('membatasi tiga berkas dan memberi pesan sisa', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    foreach (jenisRelevanPemilihan() as $urutan => $jenis) {
        berkasPemilihan($laporan, $jenis, 'berkas-'.$urutan.'.pdf');
    }

    aktifkanDriverPemilihan();
    Http::fake(['https://api.openai.test/v1/responses' => responPemilihan()]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertSessionHas('sukses', fn ($pesan): bool => is_string($pesan)
            && str_contains($pesan, '2 berkas belum diperiksa, klik lagi untuk melanjutkan.'));

    Http::assertSentCount(3);
});

it('melanjutkan sisa berkas pada permintaan kedua', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    foreach (jenisRelevanPemilihan() as $urutan => $jenis) {
        berkasPemilihan($laporan, $jenis, 'berkas-'.$urutan.'.pdf');
    }

    aktifkanDriverPemilihan();
    Http::fake(['https://api.openai.test/v1/responses' => responPemilihan()]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect()
        ->assertSessionHas('sukses', fn ($pesan): bool => is_string($pesan)
            && ! str_contains($pesan, 'belum diperiksa'));

    // Lima berkas relevan: tiga pada permintaan pertama, dua sisanya berikutnya.
    Http::assertSentCount(5);
});

it('menandai tertunda saat penyedia membatasi laju dan tetap menganalisis berkas lain', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    $skk = berkasPemilihan($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN, 'skk.pdf');
    $ktp = berkasPemilihan($laporan, BerkasPendukung::JENIS_KTP_ALMARHUM, 'ktp.pdf');

    aktifkanDriverPemilihan();
    Http::fakeSequence('https://api.openai.test/v1/responses')
        ->push(['error' => ['message' => 'rate limited']], 429)
        ->push(badanResponPemilihan(), 200);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect()
        ->assertSessionHas('sukses');

    expect(AnalisisAi::query()
        ->where('id_berkas', $skk->id_berkas)
        ->where('status', AnalisisAi::STATUS_TERTUNDA)
        ->count())->toBe(1)
        ->and(AnalisisAi::query()
            ->where('id_berkas', $ktp->id_berkas)
            ->where('status', AnalisisAi::STATUS_BERHASIL)
            ->count())->toBe(2);
});

it('menampilkan tiga status berkas pada panel langkah empat', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = laporanPemilihanBerkas($pemohon);

    foreach (jenisRelevanPemilihan() as $urutan => $jenis) {
        berkasPemilihan($laporan, $jenis, 'berkas-'.$urutan.'.pdf');
    }

    // Berkas relevan yang sudah berhasil dianalisis sebelumnya.
    $sudah = berkasPemilihan($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN, 'sudah.pdf');
    catatAnalisisPemilihan($sudah, AnalisisAi::STATUS_BERHASIL);

    // Berkas yang tidak dipakai aturan verifikasi.
    berkasPemilihan($laporan, BerkasPendukung::JENIS_KTP_SAKSI_1, 'saksi.pdf');

    aktifkanDriverPemilihan();
    Http::fake(['https://api.openai.test/v1/responses' => responPemilihan()]);

    $this->actingAs($pemohon)->post(route('laporan.periksa_ai', $laporan))->assertRedirect();

    $this->actingAs($pemohon)
        ->get(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertOk()
        ->assertSee('Status Pemeriksaan Berkas')
        ->assertSee('sudah diperiksa')
        ->assertSee('belum diperiksa')
        ->assertSee('dilewati (tidak relevan)')
        ->assertSee('saksi.pdf')
        ->assertSee('Hasil bantuan AI, hanya saran');
});
