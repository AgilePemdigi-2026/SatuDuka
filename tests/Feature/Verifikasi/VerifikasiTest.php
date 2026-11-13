<?php

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use Database\Seeders\DatabaseSeeder;
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
        'nik_almarhum' => '9999990101900101',
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
 * Melampirkan seluruh jenis berkas wajib pada laporan verifikasi.
 */
function lengkapiBerkasVerifikasi(LaporanKematian $laporan): void
{
    foreach (BerkasPendukung::DAFTAR_JENIS as $urutan => $jenis) {
        lampirkanBerkasVerifikasi($laporan, $jenis, 'lengkap-'.$urutan.'.pdf');
    }
}

/**
 * Menyimpan catatan perbandingan hasil analisis untuk pengujian skor.
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
        'skor_kecurigaan' => null,
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

it('menyediakan pengajuan demo dengan semua dokumen wajib untuk disetujui', function () {
    $this->seed(DatabaseSeeder::class);

    $laporan = LaporanKematian::query()
        ->where('nama_pelapor', 'Pemohon Demo - Skenario Berkas Lengkap')
        ->firstOrFail();
    $verifikator = Pengguna::query()
        ->where('email', 'verifikator@demo.satuduka.id')
        ->firstOrFail();

    expect($laporan->status_pengajuan)
        ->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->berkasPendukung()
            ->where('status_unggahan', BerkasPendukung::STATUS_BERHASIL)
            ->pluck('jenis_dokumen')
            ->unique()
            ->all())
        ->toEqual(BerkasPendukung::DAFTAR_JENIS);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk();

    $respons
        ->assertSee('Informasi Pemohon &amp; Almarhum', false)
        ->assertSee('No. Pengajuan:')
        ->assertSee('Kembali')
        ->assertDontSee('Kembali ke Antrean')
        ->assertSee('Pilihan Keputusan')
        ->assertSee('Kelola Profil')
        ->assertSee('Keluar');

    $this->post(route('verifikasi.setujui', $laporan))
        ->assertRedirect(route('verifikasi.index'));

    expect($laporan->fresh()->status_pengajuan)
        ->toBe(LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE);
});

it('mengambil antrean verifikasi berkas dari database dan mendukung pencarian', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    buatLaporanVerifikasi($pemohon, [
        'nama_almarhum' => 'Nama Lain',
        'no_pengajuan' => 'PENGAJUAN-LAIN',
    ]);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.index', ['cari' => 'wiryo']))
        ->assertOk()
        ->assertSee($laporan->nama_almarhum)
        ->assertDontSee('Nama Lain');

    expect($respons->viewData('pengajuan')->total())->toBe(1);
});

it('menampilkan seluruh status dengan ringkasan, filter, dan urutan terbaru', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $selesai = buatLaporanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
        'tanggal_pengajuan' => now()->subDay(),
        'nama_almarhum' => 'Pemohon Status Selesai',
    ]);
    $menunggu = buatLaporanVerifikasi($pemohon, [
        'no_pengajuan' => 'PENGAJUAN-TERBARU',
        'tanggal_pengajuan' => now(),
        'nama_almarhum' => 'Pemohon Status Menunggu',
    ]);
    $dikembalikan = buatLaporanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DIKEMBALIKAN,
        'tanggal_pengajuan' => now()->subHours(2),
        'nama_almarhum' => 'Pemohon Status Dikembalikan',
    ]);
    $draf = buatLaporanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
        'nama_almarhum' => 'Pemohon Status Draf',
    ]);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.index'))
        ->assertOk()
        ->assertSee($menunggu->nama_almarhum)
        ->assertSee($dikembalikan->nama_almarhum)
        ->assertSee($selesai->nama_almarhum)
        ->assertSee('Total Permohonan')
        ->assertSee('Anomali Perlu Rekonsiliasi')
        ->assertSee('Kelola Profil')
        ->assertSee('Keluar')
        ->assertDontSee($draf->nama_almarhum);

    $pengajuan = $respons->viewData('pengajuan');
    expect($pengajuan->items()[0]->id_laporan)->toBe($menunggu->id_laporan)
        ->and($pengajuan->total())->toBe(3)
        ->and($respons->viewData('ringkasan')['selesai'])->toBe(1)
        ->and($respons->viewData('ringkasan')['total'])->toBe(3);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.index', ['status' => 'menunggu']))
        ->assertOk()
        ->assertSee($menunggu->nama_almarhum)
        ->assertDontSee($dikembalikan->nama_almarhum);
});

it('menampilkan dan menerima alasan saat menolak berkas', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk()
        ->assertSee('Alasan Penolakan')
        ->assertSee('Tolak Berkas')
        ->assertSee('<option value="setujui" selected>Setujui Berkas</option>', false)
        ->assertSee('data-action-setujui="'.route('verifikasi.setujui', $laporan).'"', false)
        ->assertSee('data-action-tolak="'.route('verifikasi.revisi', $laporan).'"', false)
        ->assertSee('data-action-anomali="'.route('verifikasi.anomali', $laporan).'"', false);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.revisi', $laporan), [
            'catatan' => 'Dokumen identitas tidak terbaca.',
        ])
        ->assertRedirect(route('verifikasi.index'));

    expect($laporan->fresh()->status_pengajuan)
        ->toBe(LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI)
        ->and($laporan->fresh()->catatan_revisi)
        ->toBe('Dokumen identitas tidak terbaca.');
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
        ->assertOk();
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
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
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
        ->and(AnalisisAi::query()
            ->where('id_laporan', $laporan->id_laporan)
            ->where('jenis', AnalisisAi::JENIS_SKOR)
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

it('menolak analisis ai untuk pemohon', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($pemohon)
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
        ->assertForbidden();
});

it('menampilkan skor kecurigaan pada daftar verifikasi', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    lengkapiBerkasVerifikasi($laporan);
    simpanPerbandinganVerifikasi($laporan, ['nik' => 'beda']);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.daftar'))
        ->assertOk()
        ->assertSee('Skor')
        ->assertSee('/100');

    $skor = $respons->viewData('skorPerLaporan')[$laporan->id_laporan];

    expect($skor['skor'])->toBe(30)
        ->and($skor['kategori'])->toBe('sedang')
        ->and(AnalisisAi::query()
            ->where('id_laporan', $laporan->id_laporan)
            ->where('jenis', AnalisisAi::JENIS_SKOR)
            ->count())->toBe(1);
});

it('memfilter daftar verifikasi berdasarkan prioritas tinggi', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();

    $tinggi = buatLaporanVerifikasi($pemohon);
    lengkapiBerkasVerifikasi($tinggi);
    simpanPerbandinganVerifikasi($tinggi, [
        'nik' => 'beda',
        'nama_almarhum' => 'beda',
        'tanggal_kematian' => 'beda',
        'tempat_kematian' => 'beda',
    ]);

    $rendah = buatLaporanVerifikasi($pemohon);
    lengkapiBerkasVerifikasi($rendah);
    simpanPerbandinganVerifikasi($rendah);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.daftar', ['prioritas' => 'tinggi']))
        ->assertOk()
        ->assertSee($tinggi->no_pengajuan)
        ->assertDontSee($rendah->no_pengajuan);
});

it('mengurutkan daftar verifikasi berdasarkan skor tertinggi', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();

    $rendah = buatLaporanVerifikasi($pemohon, ['tanggal_pengajuan' => now()->subDay()]);
    lengkapiBerkasVerifikasi($rendah);
    simpanPerbandinganVerifikasi($rendah);

    $tinggi = buatLaporanVerifikasi($pemohon, ['tanggal_pengajuan' => now()]);
    lengkapiBerkasVerifikasi($tinggi);
    simpanPerbandinganVerifikasi($tinggi, [
        'nik' => 'beda',
        'nama_almarhum' => 'beda',
        'tanggal_kematian' => 'beda',
        'tempat_kematian' => 'beda',
    ]);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.daftar', ['urut' => 'prioritas']))
        ->assertOk();

    $items = $respons->viewData('daftar')->items();

    expect($items[0]->id_laporan)->toBe($tinggi->id_laporan)
        ->and($items[1]->id_laporan)->toBe($rendah->id_laporan);
});

it('menampilkan skor dan tombol ringkasan ai pada detail verifikasi', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Skor Kecurigaan')
        ->assertSee('Buat ringkasan AI');

    expect($respons->viewData('skor')['skor'])->toBeInt()
        ->and($respons->viewData('skor')['skor'])->toBeGreaterThanOrEqual(0)
        ->and($respons->viewData('skor')['skor'])->toBeLessThanOrEqual(100)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
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
        ->post(route('verifikasi.ringkasan_ai', $laporan))
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

it('menampilkan skor aturan saat ringkasan ai gagal', function () {
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
        ->assertSee('Skor Kecurigaan')
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