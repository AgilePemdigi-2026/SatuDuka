<?php

/**
 * Pengujian tanda tangan visual (gambar) pada penandatanganan TTE.
 *
 * Gambar tanda tangan dibuat lewat GD di tests/Pest.php (pngTandaTangan).
 * Tanda tangan tidak boleh bocor ke sesi, log audit, atau respons.
 */

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\Dokumen\LayananDokumenResmi;
use App\Services\LayananPenguncian;
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
 * Pengajuan Menunggu TTE beserta dokumen Menunggu.
 *
 * @param  array<string, mixed>  $timpa
 * @return array{0: LaporanKematian, 1: DokumenResmi}
 */
function buatDokumenUntukTandaTanganVisual(Pengguna $pemohon, array $timpa = []): array
{
    $laporan = LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
        'tanggal_pengajuan' => now(),
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-10-01',
    ], $timpa));

    $dokumen = DokumenResmi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'status_ttd' => DokumenResmi::STATUS_MENUNGGU,
        'file_dokumen' => null,
        'alasan_pengembalian' => null,
    ]);

    return [$laporan, $dokumen];
}

it('menyimpan tanda tangan visual, hash, dan menempelkannya di PDF', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenUntukTandaTanganVisual($pemohon);

    $ttd = pngTandaTangan();

    $this->actingAs($pejabat)
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => $ttd,
        ])
        ->assertRedirect(route('tanda_tangan.index'));

    $dokumen->refresh();

    expect($dokumen->status_ttd)->toBe(DokumenResmi::STATUS_SELESAI)
        ->and($dokumen->tanda_tangan_visual)->not->toBeNull()
        ->and($dokumen->tanda_tangan_hash)->toBe(hash('sha256', base64_decode($dokumen->tanda_tangan_visual, true)))
        ->and($dokumen->file_dokumen)->not->toBeNull();

    $pdf = Storage::disk('local')->get($dokumen->file_dokumen);

    expect($pdf)->toStartWith('%PDF-')
        ->and($pdf)->toContain('/Subtype /Image');

    $audit = LogAudit::query()->where('aksi', 'menandatangani_dokumen')->firstOrFail();

    expect($audit->detail['tanda_tangan_visual'])->toBeTrue()
        ->and($audit->detail['tanda_tangan_hash'])->toBe($dokumen->tanda_tangan_hash);

    // Gambar tanda tangan tidak boleh muncul di audit.
    expect(json_encode($audit->detail))->not->toContain(substr($dokumen->tanda_tangan_visual, 0, 60));
});

it('menolak penandatanganan tanpa tanda tangan', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenUntukTandaTanganVisual($pemohon);

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertRedirect(route('tanda_tangan.show', $laporan))
        ->assertSessionHasErrors('tanda_tangan');

    expect($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_MENUNGGU)
        ->and($dokumen->fresh()->tanda_tangan_visual)->toBeNull();
});

it('menolak gambar yang bukan PNG', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenUntukTandaTanganVisual($pemohon);

    $gif = imagecreatetruecolor(10, 10);
    ob_start();
    imagegif($gif);
    $biner = (string) ob_get_clean();
    imagedestroy($gif);

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => 'data:image/png;base64,'.base64_encode($biner),
        ])
        ->assertSessionHasErrors('tanda_tangan');
});

it('menolak gambar kosong tanpa goresan', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenUntukTandaTanganVisual($pemohon);

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => pngKosongTandaTangan(),
        ])
        ->assertSessionHasErrors('tanda_tangan');
});

it('menolak data URL yang rusak', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenUntukTandaTanganVisual($pemohon);

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => 'data:image/png;base64,%%%bukan-base64%%%',
        ])
        ->assertSessionHasErrors('tanda_tangan');

    $this->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => 'bukan-data-url',
        ])
        ->assertSessionHasErrors('tanda_tangan');
});

it('menolak tanda tangan melebihi 150 KB', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenUntukTandaTanganVisual($pemohon);

    $besar = 'data:image/png;base64,'.base64_encode(str_repeat('A', 160 * 1024));

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => $besar,
        ])
        ->assertSessionHasErrors('tanda_tangan');
});

it('menolak dimensi gambar melebihi batas', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenUntukTandaTanganVisual($pemohon);

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => pngTandaTangan(null, 1300, 200),
        ])
        ->assertSessionHasErrors('tanda_tangan');
});

it('tidak menambah hitungan PIN salah saat tanda tangan tidak valid', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenUntukTandaTanganVisual($pemohon);
    $kunci = 'tte:'.$pejabat->id_pengguna;

    $this->actingAs($pejabat);

    for ($i = 0; $i < 3; $i++) {
        $this->from(route('tanda_tangan.show', $laporan))
            ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '999999'])
            ->assertSessionHasErrors('tanda_tangan');
    }

    expect(LayananPenguncian::jumlahGagal($kunci))->toBe(0)
        ->and(LayananPenguncian::terkunci($kunci))->toBeFalse();

    expect(LogAudit::query()->where('aksi', 'gagal_verifikasi_tte')->count())->toBe(0);
});

it('tidak menyimpan tanda tangan saat PIN salah', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenUntukTandaTanganVisual($pemohon);

    $ttd = pngTandaTangan();

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '999999',
            'tanda_tangan' => $ttd,
        ])
        ->assertSessionHas('galat', 'Kredensial tidak valid. Silakan coba lagi.');

    expect($dokumen->fresh()->tanda_tangan_visual)->toBeNull()
        ->and($dokumen->fresh()->tanda_tangan_hash)->toBeNull()
        ->and($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_MENUNGGU);
});

it('menolak pejabat terkunci walau PIN dan tanda tangan valid', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenUntukTandaTanganVisual($pemohon);
    $kunci = 'tte:'.$pejabat->id_pengguna;

    LayananPenguncian::kunci($kunci, $pejabat);

    $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => pngTandaTangan(),
        ])
        ->assertSessionHas('galat', 'Akses TTE dikunci selama 15 menit. Silakan coba lagi nanti.');

    expect($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_MENUNGGU)
        ->and($dokumen->fresh()->tanda_tangan_visual)->toBeNull();
});

it('tidak membocorkan tanda tangan ke sesi, respons, atau audit', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenUntukTandaTanganVisual($pemohon);

    $ttd = pngTandaTangan();
    $potongan = substr(explode(',', $ttd, 2)[1], 0, 80);

    $respons = $this->actingAs($pejabat)
        ->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '999999',
            'tanda_tangan' => $ttd,
        ]);

    $respons->assertSessionHas('galat', 'Kredensial tidak valid. Silakan coba lagi.');

    expect(json_encode(session()->all()))->not->toContain($potongan)
        ->and($respons->getContent())->not->toContain($potongan);

    foreach (LogAudit::all() as $audit) {
        expect(json_encode($audit->detail))->not->toContain($potongan);
    }
});

it('merender dokumen lama tanpa tanda tangan tanpa galat', function () {
    [$pemohon] = buatAkunDemo();

    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
        'tanggal_pengajuan' => now(),
    ]);

    $dokumen = DokumenResmi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'status_ttd' => DokumenResmi::STATUS_SELESAI,
        'no_dokumen' => 'SKD-2026-000001',
        'file_dokumen' => null,
        'tanggal_terbit' => now(),
        'tanda_tangan_visual' => null,
    ]);

    expect(app(LayananDokumenResmi::class)->terbitkan($dokumen))->toBeTrue();

    $dokumen->refresh();

    expect(Storage::disk('local')->exists($dokumen->file_dokumen))->toBeTrue()
        ->and(Storage::disk('local')->get($dokumen->file_dokumen))->toStartWith('%PDF-');
});

it('memakai tanda tangan tersimpan saat berkas dipulihkan', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenUntukTandaTanganVisual($pemohon);

    $this->actingAs($pejabat)
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => pngTandaTangan(),
        ])
        ->assertRedirect(route('tanda_tangan.index'));

    $dokumen->refresh();

    $nomor = $dokumen->no_dokumen;
    $hash = $dokumen->tanda_tangan_hash;
    $jalurLama = $dokumen->file_dokumen;

    Storage::disk('local')->delete($jalurLama);

    $this->artisan('dokumen:terbitkan', ['--laporan' => $laporan->id_laporan])
        ->assertSuccessful();

    $dokumen->refresh();

    expect($dokumen->no_dokumen)->toBe($nomor)
        ->and($dokumen->tanda_tangan_hash)->toBe($hash)
        ->and($dokumen->file_dokumen)->not->toBeNull()
        ->and(Storage::disk('local')->get($dokumen->file_dokumen))->toContain('/Subtype /Image');
});

it('mengembalikan dokumen tanpa meminta tanda tangan atau PIN', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenUntukTandaTanganVisual($pemohon);

    $this->actingAs($pejabat)
        ->post(route('tanda_tangan.kembalikan', $laporan), [
            'alasan' => 'Tanggal meninggal perlu diperbaiki.',
        ])
        ->assertRedirect(route('tanda_tangan.index'))
        ->assertSessionHas('sukses');

    expect($dokumen->fresh()->status_ttd)->toBe(DokumenResmi::STATUS_DIKEMBALIKAN);
});

it('mengaktifkan kanvas dan menampilkan pesan galat PIN di modal', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan] = buatDokumenUntukTandaTanganVisual($pemohon);

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.show', $laporan))
        ->assertOk()
        ->assertSee('kanvas-tanda-tangan')
        ->assertSee('Tanda tangan di sini')
        ->assertSee('Hapus/Ulangi')
        ->assertSee('touch-none');

    $this->from(route('tanda_tangan.show', $laporan))
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '999999',
            'tanda_tangan' => pngTandaTangan(),
        ]);

    $this->get(route('tanda_tangan.show', $laporan))
        ->assertOk()
        ->assertSee('bukaModalTte');
});
