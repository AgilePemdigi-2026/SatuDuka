<?php

use App\Http\Controllers\LaporanController;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Isian formulir pengajuan yang lengkap.
 *
 * @param  array<string, mixed>  $timpa
 * @return array<string, mixed>
 */
function dataPengajuanLengkap(array $timpa = []): array
{
    return array_merge([
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Budi Santoso',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => now()->subDay()->toDateString(),
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
    ], $timpa);
}

/**
 * Draf kosong milik seorang pemohon.
 */
function buatDrafLaporan(Pengguna $pemohon): LaporanKematian
{
    return LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'no_pengajuan' => null,
        'tanggal_pengajuan' => null,
        'tanggal_kematian' => now()->subDay()->toDateString(),
    ]);
}

/**
 * Unggah satu berkas pendukung lewat rute unggah.
 */
function unggahBerkasLaporan(object $test, LaporanKematian $laporan, string $jenis, ?File $berkas = null)
{
    $berkas ??= UploadedFile::fake()->create('berkas.pdf', 100, 'application/pdf');

    return $test->post(route('laporan.berkas.unggah', $laporan), [
        'jenis_dokumen' => $jenis,
        LaporanController::INPUT_BERKAS[$jenis] => $berkas,
    ]);
}

/**
 * Unggah satu bukti kematian dan seluruh berkas wajib lainnya.
 */
function unggahBerkasWajibLaporan(object $test, LaporanKematian $laporan, ?string $buktiKematian = null): void
{
    $buktiKematian ??= config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0];

    foreach (array_merge([$buktiKematian], config('verifikasi.berkas_wajib.lainnya')) as $jenis) {
        unggahBerkasLaporan($test, $laporan, $jenis)
            ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=3');
    }
}

it('berhasil mengajukan laporan kematian dengan data lengkap', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->get(route('laporan.baru'))
        ->assertOk()
        ->assertSee('Ajukan Laporan Kematian')
        ->assertSee('Data Pelapor')
        ->assertSee('Lanjut')
        ->assertDontSee('Data Almarhum/Almarhumah');

    $this->post(route('laporan.draf'), dataPengajuanLengkap())->assertRedirect();

    $laporan = LaporanKematian::firstOrFail();

    unggahBerkasWajibLaporan($this, $laporan);

    expect($laporan->berkasPendukung()->count())->toBe(4);

    $this->get(route('laporan.ubah', $laporan).'?langkah=4')
        ->assertOk()
        ->assertSee('Review Pengajuan')
        ->assertSee('Kirim Pengajuan')
        ->assertSee('Budi Santoso');

    $respons = $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']));

    $respons->assertRedirect(route('laporan.lihat', $laporan));

    $laporan->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->no_pengajuan)->toMatch('/^SD-\d{8}-\d{4}$/')
        ->and($laporan->tanggal_pengajuan)->not->toBeNull();

    $respons->assertSessionHas('sukses', fn (string $pesan) => str_contains($pesan, 'Pengajuan berhasil dikirim!')
        && str_contains($pesan, $laporan->no_pengajuan));

    expect(LogAudit::query()->where('aksi', 'mengajukan_laporan')->count())->toBe(1);

    $this->get(route('laporan.lihat', $laporan))
        ->assertOk()
        ->assertSee($laporan->no_pengajuan)
        ->assertSee(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ajukan Laporan Baru')
        ->assertSee($laporan->no_pengajuan)
        ->assertSee(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menolak pengajuan dengan data tidak lengkap atau tidak valid', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);

    $this->actingAs($pemohon);

    unggahBerkasWajibLaporan($this, $laporan);

    $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap([
        'nama_almarhum' => '',
        'nik_almarhum' => '123',
        'tanggal_kematian' => now()->addDay()->toDateString(),
        'tempat_kematian' => '',
        'sebab_kematian' => '',
        'pernyataan' => '1',
    ]))
        ->assertSessionHasErrors(['nama_almarhum', 'nik_almarhum', 'tanggal_kematian', 'tempat_kematian', 'sebab_kematian']);

    $laporan->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF)
        ->and($laporan->no_pengajuan)->toBeNull();

    $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']))
        ->assertRedirect(route('laporan.lihat', $laporan));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menolak pengiriman bila berkas pendukung belum lengkap', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);

    $this->actingAs($pemohon);

    unggahBerkasLaporan($this, $laporan, BerkasPendukung::JENIS_KTP_PELAPOR);

    $respons = $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']))
        ->assertSessionHasErrors('berkas');

    expect($respons->getSession()->get('errors')->first('berkas'))->toContain('Berkas wajib belum lengkap')
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF);
});

it('menerima Surat Keterangan Kelurahan sebagai bukti kematian tanpa Surat Keterangan Kematian', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);

    $this->actingAs($pemohon);

    unggahBerkasWajibLaporan($this, $laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KELURAHAN);

    $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']))
        ->assertRedirect(route('laporan.lihat', $laporan));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->berkasPendukung()->where('jenis_dokumen', BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN)->exists())->toBeFalse();
});

it('menerima SPTJM saja sebagai bukti kematian', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);

    $this->actingAs($pemohon);

    unggahBerkasWajibLaporan($this, $laporan, BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN);

    $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']))
        ->assertRedirect(route('laporan.lihat', $laporan));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->berkasPendukung()->where('jenis_dokumen', BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN)->exists())->toBeFalse();
});

it('menolak pengiriman bila tidak ada satu pun bukti kematian', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);

    $this->actingAs($pemohon);

    foreach (config('verifikasi.berkas_wajib.lainnya') as $jenis) {
        unggahBerkasLaporan($this, $laporan, $jenis)
            ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=3');
    }

    $respons = $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']))
        ->assertSessionHasErrors('berkas');

    expect($respons->getSession()->get('errors')->first('berkas'))
        ->toContain('Berkas wajib belum lengkap')
        ->toContain('Bukti kematian')
        ->toContain(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF);
});

it('menolak pengiriman terlambat lebih dari 30 hari tanpa SPTJM keterlambatan', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);
    $tanggalKematian = now()->subDays(31)->toDateString();

    $laporan->update(['tanggal_kematian' => $tanggalKematian]);

    $this->actingAs($pemohon);

    unggahBerkasWajibLaporan($this, $laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);

    $respons = $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap([
        'tanggal_kematian' => $tanggalKematian,
        'pernyataan' => '1',
    ]))->assertSessionHasErrors('berkas');

    expect($respons->getSession()->get('errors')->first('berkas'))
        ->toContain('Berkas wajib belum lengkap')
        ->toContain(BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF);
});

it('menyimpan pengajuan sebagai draf dan dapat dilanjutkan', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon);

    $respons = $this->post(route('laporan.draf'), [
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Budi Santoso',
    ]);

    $laporan = LaporanKematian::firstOrFail();

    $respons->assertRedirect(route('laporan.ubah', $laporan).'?langkah=1');

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF)
        ->and($laporan->no_pengajuan)->toBeNull()
        ->and($laporan->nik_almarhum)->toBeNull()
        ->and($laporan->tanggal_kematian)->toBeNull();

    expect(LogAudit::query()->where('aksi', 'menyimpan_draf_laporan')->count())->toBe(1);

    $this->get(route('laporan.ubah', $laporan))
        ->assertOk()
        ->assertSee('Data Pelapor')
        ->assertSee('Pemohon Demo');

    $this->get(route('laporan.ubah', $laporan).'?langkah=2')
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('Data Almarhum/Almarhumah');

    $this->get(route('laporan.ubah', $laporan).'?langkah=3')
        ->assertOk()
        ->assertSee('Unggah Berkas Pendukung');

    $this->post(route('laporan.draf.ubah', $laporan), dataPengajuanLengkap([
        'tempat_kematian' => 'RS Sardjito',
        'langkah' => 4,
    ]))
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4');

    expect($laporan->fresh()->tempat_kematian)->toBe('RS Sardjito')
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF);
});

it('menolak berkas dengan format atau ukuran tidak sesuai', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);
    $jenis = BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN;
    $kolom = LaporanController::INPUT_BERKAS[$jenis];

    $this->actingAs($pemohon);

    $this->from(route('laporan.ubah', $laporan).'?langkah=3')
        ->post(route('laporan.berkas.unggah', $laporan), [
            'jenis_dokumen' => $jenis,
            $kolom => UploadedFile::fake()->create('catatan.txt', 10, 'text/plain'),
        ])
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=3')
        ->assertSessionHasErrors([$kolom => LaporanController::PESAN_BERKAS]);

    unggahBerkasLaporan($this, $laporan, $jenis, UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'))
        ->assertSessionHasErrors([$kolom => LaporanController::PESAN_BERKAS]);

    expect($laporan->berkasPendukung()->count())->toBe(0);

    unggahBerkasLaporan($this, $laporan, $jenis)
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=3');

    $berkas = $laporan->berkasPendukung()->firstOrFail();

    expect($berkas->status_unggahan)->toBe(BerkasPendukung::STATUS_BERHASIL)
        ->and($berkas->path_file)->toStartWith('berkas_laporan/'.$laporan->id_laporan.'/');

    Storage::disk('local')->assertExists($berkas->path_file);

    $this->get(route('laporan.ubah', $laporan).'?langkah=3')
        ->assertOk()
        ->assertSee('Terunggah')
        ->assertSee($berkas->nama_file);

    $this->get(route('laporan.berkas', [$laporan, $berkas]))->assertOk();

    expect(LogAudit::query()->where('aksi', 'mengunggah_berkas')->count())->toBe(1);
});

it('mencegah pemohon lain melihat atau mengubah laporan milik orang lain', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();

    $pemohonLain = Pengguna::factory()->pemohon()->create([
        'email' => 'wsihombing@example.com',
        'kata_sandi' => 'password123',
    ]);

    $laporan = buatDrafLaporan($pemohon);

    $berkas = $laporan->berkasPendukung()->create([
        'jenis_dokumen' => BerkasPendukung::JENIS_KTP_ALMARHUM,
        'nama_file' => 'ktp.pdf',
        'path_file' => 'berkas_laporan/'.$laporan->id_laporan.'/ktp.pdf',
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
        'tanggal_unggah' => now(),
    ]);

    Storage::disk('local')->put($berkas->path_file, 'contoh');

    $this->actingAs($pemohonLain)
        ->get(route('laporan.lihat', $laporan))->assertNotFound();
    $this->get(route('laporan.ubah', $laporan))->assertNotFound();
    $this->get(route('laporan.berkas', [$laporan, $berkas]))->assertNotFound();
    $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']))->assertNotFound();
    unggahBerkasLaporan($this, $laporan, BerkasPendukung::JENIS_KTP_PELAPOR)->assertNotFound();

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF)
        ->and($laporan->fresh()->no_pengajuan)->toBeNull();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Belum ada pengajuan')
        ->assertDontSee('Budi Santoso');

    $this->actingAs($pemohon)
        ->get(route('laporan.lihat', $laporan))->assertOk();
});

it('menolak perubahan pada pengajuan yang sudah dikirim', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();

    $laporan = LaporanKematian::factory()->terkirim()->create([
        'id_pengguna' => $pemohon->id_pengguna,
    ]);

    $this->actingAs($pemohon)
        ->get(route('laporan.ubah', $laporan))->assertForbidden();

    unggahBerkasLaporan($this, $laporan, BerkasPendukung::JENIS_KTP_PELAPOR)->assertForbidden();
});

it('menyimpan draf otomatis saat menekan Lanjut dan membuka langkah berikutnya', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon);

    $respons = $this->post(route('laporan.draf'), [
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'langkah' => 2,
    ]);

    $laporan = LaporanKematian::firstOrFail();

    $respons->assertRedirect(route('laporan.ubah', $laporan).'?langkah=2');

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF)
        ->and($laporan->hubungan_pelapor)->toBe('Anak Kandung');

    $this->get(route('laporan.ubah', $laporan).'?langkah=2')
        ->assertOk()
        ->assertSee('Data Almarhum/Almarhumah')
        ->assertDontSee('Nama Lengkap Pelapor');

    $this->post(route('laporan.draf.ubah', $laporan), dataPengajuanLengkap(['langkah' => 3]))
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=3');

    expect($laporan->fresh()->nik_almarhum)->toBe('3471031203559001');
});

it('mengembalikan pengguna ke langkah yang tepat setelah galat validasi', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);

    $this->actingAs($pemohon);

    unggahBerkasWajibLaporan($this, $laporan);

    $this->from(route('laporan.ubah', $laporan).'?langkah=4')
        ->post(route('laporan.kirim', $laporan), dataPengajuanLengkap([
            'nik_almarhum' => '123',
            'pernyataan' => '1',
        ]))
        ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=2');

    $this->get(route('laporan.ubah', $laporan).'?langkah=2')
        ->assertOk()
        ->assertSee('NIK almarhum harus terdiri dari 16 digit.')
        ->assertSee('value="123"', false)
        ->assertSee('Data Almarhum/Almarhumah');
});

it('menyediakan formulir multipart untuk unggah berkas', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);

    $this->actingAs($pemohon);

    $this->get(route('laporan.baru'))
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false);

    $respons = $this->get(route('laporan.ubah', $laporan).'?langkah=3')->assertOk();

    expect(substr_count($respons->getContent(), 'enctype="multipart/form-data"'))
        ->toBe(count($respons->viewData('daftarJenis')));
});

it('menolak pengiriman ganda pada pengajuan yang sama', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);

    $this->actingAs($pemohon);

    unggahBerkasWajibLaporan($this, $laporan);

    $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']))
        ->assertRedirect(route('laporan.lihat', $laporan));

    $nomor = $laporan->fresh()->no_pengajuan;

    $this->post(route('laporan.kirim', $laporan), dataPengajuanLengkap(['pernyataan' => '1']))
        ->assertForbidden();

    expect($laporan->fresh()->no_pengajuan)->toBe($nomor)
        ->and(LogAudit::query()->where('aksi', 'mengajukan_laporan')->count())->toBe(1);
});

it('menolak hubungan pelapor di luar pilihan', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatDrafLaporan($pemohon);
    $laporan->update(['hubungan_pelapor' => null]);

    $this->actingAs($pemohon)
        ->post(route('laporan.draf.ubah', $laporan), ['hubungan_pelapor' => 'Tetangga'])
        ->assertSessionHasErrors('hubungan_pelapor');

    expect($laporan->fresh()->hubungan_pelapor)->toBeNull();
});
