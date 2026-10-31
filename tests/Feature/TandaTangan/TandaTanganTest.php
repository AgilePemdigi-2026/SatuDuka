<?php

/**
 * Pengujian penandatanganan dokumen dengan TTE oleh Pejabat (use case 5.2.8).
 *
 * Seluruh pengujian memakai SQLite in-memory dan driver TTE mock. PIN dummy
 * diatur melalui konfigurasi pengujian; nilai PIN tidak boleh muncul pada
 * sesi, respons, maupun log audit.
 */

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\LayananPenguncian;
use App\Support\MaskerNik;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'satuduka.tte_driver' => 'mock',
        'satuduka.tte_pin_dummy' => '123456',
    ]);

    Cache::flush();
    Storage::fake('local');
});

/**
 * Membuat pengajuan yang sudah disetujui verifikator beserta dokumennya.
 *
 * @param  array<string, mixed>  $timpa
 * @return array{0: LaporanKematian, 1: DokumenResmi}
 */
function buatDokumenMenungguTandaTangan(Pengguna $pemohon, array $timpa = []): array
{
    $laporan = LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
        'tanggal_pengajuan' => now(),
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-10-01',
    ], $timpa));

    $dokumen = DokumenResmi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'id_pengguna' => null,
        'status_ttd' => DokumenResmi::STATUS_MENUNGGU,
        'file_dokumen' => null,
        'alasan_pengembalian' => null,
    ]);

    return [$laporan, $dokumen];
}

it('membatasi seluruh rute tanda tangan hanya untuk pejabat', function () {
    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenMenungguTandaTangan($pemohon);

    $rute = [
        ['get', route('tanda_tangan.index')],
        ['get', route('tanda_tangan.show', $laporan)],
        ['post', route('tanda_tangan.tandatangani', $laporan)],
        ['post', route('tanda_tangan.kembalikan', $laporan)],
    ];

    foreach ($rute as [$metode, $url]) {
        $this->{$metode}($url)->assertRedirect(route('masuk'));
    }

    foreach ([$pemohon, $verifikator] as $pengguna) {
        foreach ($rute as [$metode, $url]) {
            $this->actingAs($pengguna)->{$metode}($url)->assertForbidden();
        }
    }

    $this->actingAs($pejabat)->get(route('tanda_tangan.index'))->assertOk();
});

it('menampilkan daftar dengan NIK termasker dan pratinjau ber-header aman', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenMenungguTandaTangan($pemohon);

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.index'))
        ->assertOk()
        ->assertSee('Menunggu Tindakan')
        ->assertSee(MaskerNik::masker($laporan->nik_almarhum))
        ->assertDontSee($laporan->nik_almarhum);

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.show', $laporan))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        // Detail akta menampilkan NIK utuh seperti pada dokumen resmi (SRS 7.13).
        ->assertSee($laporan->nik_almarhum);
});

it('menandatangani dokumen dengan PIN benar dan mencatat audit tanpa PIN', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenMenungguTandaTangan($pemohon);

    $this->actingAs($pejabat)
        ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertRedirect(route('tanda_tangan.index'))
        ->assertSessionHas('sukses');

    $laporan->refresh();
    $dokumen->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_SELESAI)
        ->and($dokumen->status_ttd)->toBe(DokumenResmi::STATUS_SELESAI)
        ->and($dokumen->id_pengguna)->toBe($pejabat->id_pengguna)
        ->and($dokumen->tanggal_terbit)->not->toBeNull()
        ->and($dokumen->no_dokumen)->not->toBeNull()
        ->and($dokumen->file_dokumen)->not->toBeNull();

    expect(Storage::disk('local')->exists($dokumen->file_dokumen))->toBeTrue();

    $audit = LogAudit::query()->where('aksi', 'menandatangani_dokumen')->firstOrFail();

    expect($audit->layanan_eksternal)->toBe(LogAudit::LAYANAN_TTE)
        ->and($audit->hasil)->toBe(LogAudit::HASIL_SUKSES)
        ->and(json_encode($audit->detail))->not->toContain('123456');

    expect(LayananPenguncian::jumlahGagal('tte:'.$pejabat->id_pengguna))->toBe(0);
});

it('menolak PIN salah tanpa menyimpan PIN pada sesi, respons, atau audit', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenMenungguTandaTangan($pemohon);
    $pinSalah = '999999';

    $response = $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => $pinSalah]);

    $response->assertRedirect(route('tanda_tangan.show', $laporan))
        ->assertSessionHas('galat', 'Kredensial tidak valid. Silakan coba lagi.');

    expect(session()->getOldInput('pin'))->toBeNull()
        ->and(json_encode(session()->all()))->not->toContain($pinSalah)
        ->and($response->getContent())->not->toContain($pinSalah);

    $audit = LogAudit::query()->where('aksi', 'gagal_verifikasi_tte')->firstOrFail();

    expect(json_encode($audit->detail))->not->toContain($pinSalah)
        ->and($audit->detail['percobaan_ke'])->toBe(1);

    expect($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_MENUNGGU)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE);
});

it('tidak menyimpan PIN berformat salah pada old input', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenMenungguTandaTangan($pemohon);

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => 'abc123'])
        ->assertRedirect(route('tanda_tangan.show', $laporan))
        ->assertSessionHasErrors('pin');

    expect(session()->getOldInput('pin'))->toBeNull()
        ->and(json_encode(session()->all()))->not->toContain('abc123');
});

it('mengunci akses setelah tiga kali PIN salah dan mencatat audit', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenMenungguTandaTangan($pemohon);
    $kunci = 'tte:'.$pejabat->id_pengguna;

    $this->actingAs($pejabat);

    for ($i = 1; $i <= 2; $i++) {
        $this->from(route('tanda_tangan.show', $laporan))
            ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '000000'])
            ->assertSessionHas('galat', 'Kredensial tidak valid. Silakan coba lagi.');
    }

    $this->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '000000'])
        ->assertSessionHas('galat', 'Akses TTE dikunci selama 15 menit. Silakan coba lagi nanti.');

    expect(LayananPenguncian::terkunci($kunci))->toBeTrue()
        ->and(LayananPenguncian::jumlahGagal($kunci))->toBe(3);

    expect(LogAudit::query()->where('aksi', 'mengunci_akses')->count())->toBe(1);

    $this->get(route('tanda_tangan.show', $laporan))
        ->assertOk()
        ->assertSee('Akses TTE dikunci');

    expect($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_MENUNGGU);
});

it('menolak PIN benar selama akses terkunci tanpa menambah hitungan gagal', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenMenungguTandaTangan($pemohon);
    $kunci = 'tte:'.$pejabat->id_pengguna;

    $this->actingAs($pejabat);

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '000000']);
    }

    expect(LayananPenguncian::terkunci($kunci))->toBeTrue()
        ->and(LayananPenguncian::jumlahGagal($kunci))->toBe(3);

    // PIN benar tetap ditolak karena penguncian diperiksa lebih dahulu.
    $this->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertSessionHas('galat', 'Akses TTE dikunci selama 15 menit. Silakan coba lagi nanti.');

    expect(LayananPenguncian::jumlahGagal($kunci))->toBe(3)
        ->and($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_MENUNGGU)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE);

    expect(LogAudit::query()->where('aksi', 'percobaan_tte_saat_terkunci')->count())->toBe(1);
});

it('mengizinkan PIN benar setelah masa kunci berakhir', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenMenungguTandaTangan($pemohon);
    $kunci = 'tte:'.$pejabat->id_pengguna;

    $this->actingAs($pejabat);

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '000000']);
    }

    expect(LayananPenguncian::terkunci($kunci))->toBeTrue();

    $this->travel(16)->minutes();

    $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertRedirect(route('tanda_tangan.index'));

    expect($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_SELESAI)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_SELESAI)
        ->and(LayananPenguncian::jumlahGagal($kunci))->toBe(0);
});

it('mereset hitungan gagal setelah penandatanganan berhasil', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenMenungguTandaTangan($pemohon);
    $kunci = 'tte:'.$pejabat->id_pengguna;

    $this->actingAs($pejabat);

    $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '000000']);
    $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '000000']);

    expect(LayananPenguncian::jumlahGagal($kunci))->toBe(2);

    $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertRedirect(route('tanda_tangan.index'));

    expect(LayananPenguncian::jumlahGagal($kunci))->toBe(0);
});

it('menolak aksi tanda tangan ganda pada dokumen yang sama', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenMenungguTandaTangan($pemohon);

    $this->actingAs($pejabat);

    $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertRedirect(route('tanda_tangan.index'));

    $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertStatus(409);

    expect(LogAudit::query()->where('aksi', 'menandatangani_dokumen')->count())->toBe(1);
});

it('menolak tanda tangan untuk pengajuan yang belum disetujui verifikator', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenMenungguTandaTangan($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
    ]);

    $this->actingAs($pejabat);

    $this->get(route('tanda_tangan.show', $laporan))->assertNotFound();

    $this->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertStatus(409);

    $this->post(route('tanda_tangan.kembalikan', $laporan), [
        'alasan' => 'Dokumen belum disetujui verifikator.',
    ])->assertStatus(409);

    expect($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_MENUNGGU)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menolak detail dokumen yang tidak ada atau milik alur lain', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();

    $tanpaDokumen = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
        'tanggal_pengajuan' => now(),
    ]);

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.show', $tanpaDokumen))
        ->assertNotFound();

    $this->post(route('tanda_tangan.tandatangani', $tanpaDokumen), ['pin' => '123456'])
        ->assertStatus(409);
});

it('mengembalikan dokumen dengan alasan dan meneruskannya ke verifikator', function () {
    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenMenungguTandaTangan($pemohon);
    $alasan = 'Tanggal meninggal perlu diperbaiki pada surat keterangan.';

    $this->actingAs($pejabat)
        ->post(route('tanda_tangan.kembalikan', $laporan), [])
        ->assertSessionHasErrors('alasan');

    expect($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_MENUNGGU);

    $this->post(route('tanda_tangan.kembalikan', $laporan), ['alasan' => $alasan])
        ->assertRedirect(route('tanda_tangan.index'))
        ->assertSessionHas('sukses');

    $laporan->refresh();
    $dokumen->refresh();

    expect($dokumen->status_ttd)->toBe(DokumenResmi::STATUS_DIKEMBALIKAN)
        ->and($dokumen->alasan_pengembalian)->toBe($alasan)
        ->and($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_DIKEMBALIKAN);

    expect(LogAudit::query()->where('aksi', 'mengembalikan_dokumen')->count())->toBe(1);

    // Verifikator tetap dapat melihat dan menindaklanjuti berkas yang dikembalikan.
    $this->actingAs($verifikator)
        ->get(route('verifikasi.index'))
        ->assertOk()
        ->assertSee($laporan->no_pengajuan);

    $this->get(route('verifikasi.show', $laporan))
        ->assertOk()
        ->assertSee('Dokumen dikembalikan oleh Pejabat')
        ->assertSee($alasan);

    // Pemohon dapat memperbaiki dan mengirim ulang (SRS 5.2.8 alur 3.a.5).
    expect($laporan->fresh()->bolehDiubah())->toBeTrue();
});

it('menangani pengajuan selesai tanpa berkas dokumen tanpa galat 500', function () {
    [$pemohon] = buatAkunDemo();

    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
        'tanggal_pengajuan' => now(),
        'no_pengajuan' => 'SD-20261020-0009',
    ]);

    DokumenResmi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'status_ttd' => DokumenResmi::STATUS_SELESAI,
        'file_dokumen' => null,
        'tanggal_terbit' => now(),
    ]);

    $this->actingAs($pemohon)
        ->get(route('pengajuan.detail', $laporan))
        ->assertOk()
        ->assertSee('Dokumen resmi sedang disiapkan')
        ->assertDontSee(route('pengajuan.unduh', $laporan));

    $this->get(route('pengajuan.unduh', $laporan))
        ->assertRedirect(route('pengajuan.detail', $laporan))
        ->assertSessionHas('galat', 'Dokumen resmi sedang disiapkan.');
});
