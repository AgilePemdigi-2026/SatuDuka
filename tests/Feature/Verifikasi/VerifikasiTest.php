<?php

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Laporan milik pemohon dengan status tertentu untuk pengujian verifikasi.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanVerifikasi(Pengguna $pemohon, array $timpa = []): LaporanKematian
{
    return LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
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
function lampirkanBerkasVerifikasi(
    LaporanKematian $laporan,
    string $jenis,
    string $namaFile,
): BerkasPendukung {
    $ekstensi = pathinfo($namaFile, PATHINFO_EXTENSION) ?: 'pdf';
    $path = 'berkas_verifikasi/'.$laporan->id_laporan.'/'.Str::random(10).'.'.$ekstensi;
    Storage::disk('local')->put($path, '%PDF-1.4 ISI-DOKUMEN-VERIFIKASI');

    return BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => $jenis,
        'nama_file' => $namaFile,
        'path_file' => $path,
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
    ]);
}

/**
 * Menyimpan catatan perbandingan hasil analisis.
 *
 * @param  array<string, string>  $status
 */
function simpanPerbandinganVerifikasi(LaporanKematian $laporan, array $status = []): void
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

    AnalisisAi::create([
        'id_laporan' => $laporan->id_laporan,
        'id_berkas' => null,
        'jenis' => AnalisisAi::JENIS_PERBANDINGAN,
        'hasil' => [
            'kolom' => $kolom,
            'jumlah_beda' => count(array_filter($status, fn (string $nilai): bool => $nilai === 'beda')),
            'ada_beda' => in_array('beda', $status, true),
            'skor' => 0.9,
        ],
        'skor' => 0.9,
        'model' => 'mock-ai-v1',
        'status' => AnalisisAi::STATUS_BERHASIL,
        'hash_berkas' => null,
        'dibuat_pada' => now(),
    ]);
}

/**
 * Mengaktifkan driver penyedia AI nyata untuk pengujian kegagalan.
 */
function aktifkanDriverOpenAiVerifikasi(): void
{
    config([
        'ai.driver' => 'openai',
        'ai.openai.api_key' => 'kunci-uji',
        'ai.openai.base_url' => 'https://api.openai.test/v1',
        'ai.openai.model' => 'model-uji',
        'ai.timeout' => 5,
    ]);
}

it('menampilkan daftar menunggu verifikasi untuk verifikator', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $menunggu = buatLaporanVerifikasi($pemohon);
    $draf = buatLaporanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.daftar'))
        ->assertOk()
        ->assertSee($menunggu->no_pengajuan)
        ->assertDontSee($draf->no_pengajuan);
});

it('menolak daftar verifikasi untuk pemohon dan pejabat', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();

    $this->actingAs($pemohon)->get(route('verifikasi.daftar'))->assertForbidden();
    $this->actingAs($pejabat)->get(route('verifikasi.daftar'))->assertForbidden();
});

it('membatasi daftar verifikasi sepuluh pengajuan per halaman', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    LaporanKematian::factory()->count(12)->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
    ]);

    $respons = $this->actingAs($verifikator)->get(route('verifikasi.daftar'))->assertOk();
    $halaman = $respons->viewData('daftar');

    expect($halaman->total())->toBe(12)
        ->and(count($halaman->items()))->toBe(10);
});

it('menampilkan detail baca saja beserta pratinjau berkas', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-keterangan.pdf',
    );

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Suharto Wiryo')
        ->assertSee('surat-keterangan.pdf')
        ->assertSee('Baca dokumen (AI)')
        ->assertDontSee('Setujui')
        ->assertDontSee('Tolak');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.berkas', [$laporan, $berkas]))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('menolak pratinjau berkas verifikator yang belum berhasil diunggah', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_KTP_PELAPOR,
        'ktp-pelapor.pdf',
    );

    $berkas->update(['status_unggahan' => BerkasPendukung::STATUS_GAGAL]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.berkas', [$laporan, $berkas]))
        ->assertNotFound();
});

it('menolak detail laporan yang bukan menunggu verifikasi', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $draf = buatLaporanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $draf))
        ->assertNotFound();
});

it('menolak pratinjau berkas lintas laporan', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporanA = buatLaporanVerifikasi($pemohon);
    $laporanB = buatLaporanVerifikasi($pemohon);
    $berkasB = lampirkanBerkasVerifikasi($laporanB, BerkasPendukung::JENIS_KTP_PELAPOR, 'ktp-pelapor.pdf');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.berkas', [$laporanA, $berkasB]))
        ->assertNotFound();
});

it('menolak pratinjau berkas untuk pemohon', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi($laporan, BerkasPendukung::JENIS_KTP_PELAPOR, 'ktp-pelapor.pdf');

    $this->actingAs($pemohon)
        ->get(route('verifikasi.berkas', [$laporan, $berkas]))
        ->assertForbidden();
});

it('menjalankan analisis ai untuk verifikator', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporan), [
            'id_berkas' => $berkas->id_berkas,
            'asal' => 'lama',
        ])
        ->assertRedirect(route('verifikasi.detail', $laporan))
        ->assertSessionHas('analisis_ai');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Hasil bantuan AI, hanya saran')
        ->assertSee('cocok');

    expect(AnalisisAi::query()
        ->where('id_laporan', $laporan->id_laporan)
        ->where('jenis', AnalisisAi::JENIS_EKSTRAKSI)
        ->count())->toBe(1)
        ->and(AnalisisAi::query()
            ->where('id_laporan', $laporan->id_laporan)
            ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
            ->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);

    $audit = LogAudit::query()->where('aksi', 'menganalisis_dokumen_ai')->firstOrFail();

    expect($audit->layanan_eksternal)->toBe(LogAudit::LAYANAN_AI)
        ->and($audit->detail)->toMatchArray([
            'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
            'model' => 'mock-ai-v1',
            'pemicu' => 'verifikator_detail',
        ]);
});

it('mengarahkan aksi ai ke halaman baru saat asal baru', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporan), [
            'id_berkas' => $berkas->id_berkas,
            'asal' => 'baru',
        ])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHas('analisis_ai');

    $this->actingAs($verifikator)
        ->post(route('verifikasi.ringkasan_ai', $laporan), ['asal' => 'baru'])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHas('sukses');

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menolak asal aksi ai yang tidak sah tanpa mengubah status', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->from(route('verifikasi.show', $laporan))
        ->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporan), [
            'id_berkas' => $berkas->id_berkas,
            'asal' => 'tidak-sah',
        ])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHasErrors('asal');

    $this->from(route('verifikasi.show', $laporan))
        ->actingAs($verifikator)
        ->post(route('verifikasi.ringkasan_ai', $laporan), ['asal' => 'tidak-sah'])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHasErrors('asal');

    expect(AnalisisAi::query()->count())->toBe(0)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('mengarahkan kegagalan analisis ai ke halaman baru', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-gagal.pdf',
    );

    Storage::disk('local')->put($berkas->path_file, '%PDF-1.4 GAGAL-'.Str::random(12));

    aktifkanDriverOpenAiVerifikasi();
    Http::fake([
        'https://api.openai.test/v1/responses' => Http::response(['error' => ['message' => 'gagal']], 500),
    ]);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporan), [
            'id_berkas' => $berkas->id_berkas,
            'asal' => 'baru',
        ])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHas('galat', 'Analisis AI tidak tersedia, lanjutkan secara manual');

    expect(AnalisisAi::query()
        ->where('id_laporan', $laporan->id_laporan)
        ->where('jenis', AnalisisAi::JENIS_EKSTRAKSI)
        ->where('status', AnalisisAi::STATUS_GAGAL)
        ->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menolak analisis ai verifikator untuk berkas di luar laporan', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporanA = buatLaporanVerifikasi($pemohon);
    $laporanB = buatLaporanVerifikasi($pemohon);
    $berkasB = lampirkanBerkasVerifikasi(
        $laporanB,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporanA), ['id_berkas' => $berkasB->id_berkas])
        ->assertRedirect(route('verifikasi.detail', $laporanA))
        ->assertSessionHas('galat');

    expect(AnalisisAi::query()->count())->toBe(0);
});

it('menolak analisis ai untuk pemohon dan pejabat', function () {
    Storage::fake('local');
    [$pemohon, , $pejabat] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($pemohon)
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
        ->assertForbidden();

    $this->actingAs($pejabat)
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
        ->assertForbidden();

    expect(AnalisisAi::query()->count())->toBe(0);
});

it('mengurutkan daftar verifikasi berdasarkan tanggal terbaru', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    $terlama = buatLaporanVerifikasi($pemohon, ['tanggal_pengajuan' => now()->subDay()]);
    $terbaru = buatLaporanVerifikasi($pemohon, ['tanggal_pengajuan' => now()]);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.daftar', ['urut' => 'terbaru']))
        ->assertOk();

    $items = $respons->viewData('daftar')->items();

    expect($items[0]->id_laporan)->toBe($terbaru->id_laporan)
        ->and($items[1]->id_laporan)->toBe($terlama->id_laporan);
});

it('menampilkan tombol ringkasan ai pada detail verifikasi', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Buat ringkasan AI');

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('membuat ringkasan ai untuk verifikator tanpa mengubah status', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($verifikator)
        ->post(route('verifikasi.ringkasan_ai', $laporan), ['asal' => 'lama'])
        ->assertRedirect(route('verifikasi.detail', $laporan))
        ->assertSessionHas('sukses');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Hasil bantuan AI, hanya saran')
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

it('mencatat kegagalan ringkasan ai', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );
    simpanPerbandinganVerifikasi($laporan, ['nik' => 'beda']);

    aktifkanDriverOpenAiVerifikasi();
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
        ->assertSee('Analisis AI tidak tersedia, lanjutkan secara manual');

    expect(AnalisisAi::query()
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
    $laporan = buatLaporanVerifikasi($pemohon);

    $this->actingAs($pemohon)
        ->post(route('verifikasi.ringkasan_ai', $laporan))
        ->assertForbidden();

    $this->actingAs($pejabat)
        ->post(route('verifikasi.ringkasan_ai', $laporan))
        ->assertForbidden();
});
