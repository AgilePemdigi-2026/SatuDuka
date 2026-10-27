<?php

use App\Http\Controllers\LaporanController;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Isian formulir pengajuan yang lengkap.
 *
 * @param  array<string, mixed>  $timpa
 * @return array<string, mixed>
 */
function dataPerbaikanLengkap(array $timpa = []): array
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
 * Unggah satu berkas pendukung lewat rute unggah.
 */
function unggahBerkasPerbaikan(object $test, LaporanKematian $laporan, string $jenis)
{
    return $test->post(route('laporan.berkas.unggah', $laporan), [
        'jenis_dokumen' => $jenis,
        LaporanController::INPUT_BERKAS[$jenis] => UploadedFile::fake()->create('berkas.pdf', 100, 'application/pdf'),
    ]);
}

/**
 * Unggah satu bukti kematian dan seluruh berkas wajib lainnya.
 */
function unggahBerkasWajibPerbaikan(object $test, LaporanKematian $laporan, ?string $buktiKematian = null): void
{
    $buktiKematian ??= config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0];

    foreach (array_merge([$buktiKematian], config('verifikasi.berkas_wajib.lainnya')) as $jenis) {
        unggahBerkasPerbaikan($test, $laporan, $jenis)
            ->assertRedirect(route('laporan.ubah', $laporan).'?langkah=3');
    }
}

/**
 * Pengajuan berstatus revisi milik seorang pemohon.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanRevisi(
    Pengguna $pemohon,
    string $status = LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
    array $timpa = [],
): LaporanKematian {
    return LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'no_pengajuan' => 'SD-20260910-0001',
        'status_pengajuan' => $status,
        'tanggal_pengajuan' => Carbon::parse('2026-09-10 09:00'),
        'tanggal_kematian' => '2026-09-09',
        'catatan_revisi' => 'Surat keterangan kematian kurang jelas.',
        'nama_almarhum' => 'Budi Santoso',
    ], $timpa));
}

it('memperbaiki dan mengirim ulang pengajuan berstatus revisi', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanRevisi($pemohon);

    $this->actingAs($pemohon);

    $this->get(route('laporan.ubah', $laporan))
        ->assertOk()
        ->assertSee('Perbaiki Pengajuan');

    unggahBerkasWajibPerbaikan($this, $laporan);

    $this->post(route('laporan.draf.ubah', $laporan), dataPerbaikanLengkap([
        'nama_almarhum' => 'Budi Santoso Revisi',
        'langkah' => 4,
    ]))->assertRedirect(route('laporan.ubah', $laporan).'?langkah=4');

    $this->post(route('laporan.kirim', $laporan), dataPerbaikanLengkap([
        'nama_almarhum' => 'Budi Santoso Revisi',
        'pernyataan' => '1',
    ]))->assertRedirect(route('laporan.lihat', $laporan));

    $laporan->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->no_pengajuan)->toBe('SD-20260910-0001')
        ->and($laporan->tanggal_pengajuan->toDateTimeString())->toBe('2026-09-10 09:00:00')
        ->and($laporan->nama_almarhum)->toBe('Budi Santoso Revisi')
        ->and($laporan->catatan_revisi)->toBe('Surat keterangan kematian kurang jelas.');

    $audit = LogAudit::query()->where('aksi', 'mengirim_ulang_laporan')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->detail['status_sebelumnya'])->toBe(LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI)
        ->and($audit->detail['no_pengajuan'])->toBe('SD-20260910-0001')
        ->and(LogAudit::query()->where('aksi', 'mengajukan_laporan')->count())->toBe(0);
});

it('mengizinkan perbaikan untuk status menunggu perbaikan berkas pelapor', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanRevisi($pemohon, LaporanKematian::STATUS_MENUNGGU_PERBAIKAN);

    $this->actingAs($pemohon)->get(route('laporan.ubah', $laporan))
        ->assertOk()
        ->assertSee('Perbaiki Pengajuan');
});

it('menolak perubahan untuk status yang tidak dapat diubah', function (string $status) {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanRevisi($pemohon, $status);

    $this->actingAs($pemohon);

    $this->get(route('laporan.ubah', $laporan))->assertForbidden();
    $this->post(route('laporan.draf.ubah', $laporan), dataPerbaikanLengkap())->assertForbidden();
    $this->post(route('laporan.kirim', $laporan), dataPerbaikanLengkap(['pernyataan' => '1']))->assertForbidden();
})->with([
    'Selesai' => [LaporanKematian::STATUS_SELESAI],
    'Menunggu Verifikasi' => [LaporanKematian::STATUS_MENUNGGU_VERIFIKASI],
]);

it('menolak akses ke pengajuan milik pemohon lain', function () {
    [$pemohon] = buatAkunDemo();
    $pemohonLain = Pengguna::factory()->pemohon()->create();
    $laporan = buatLaporanRevisi($pemohonLain);

    $this->actingAs($pemohon);

    $this->get(route('laporan.ubah', $laporan))->assertNotFound();
    $this->post(route('laporan.draf.ubah', $laporan), dataPerbaikanLengkap())->assertNotFound();
    $this->post(route('laporan.kirim', $laporan), dataPerbaikanLengkap(['pernyataan' => '1']))->assertNotFound();
});

it('tidak menampilkan catatan revisi setelah pengajuan dikirim ulang', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanRevisi($pemohon);

    $this->actingAs($pemohon);

    unggahBerkasWajibPerbaikan($this, $laporan);

    $this->post(route('laporan.kirim', $laporan), dataPerbaikanLengkap(['pernyataan' => '1']))
        ->assertRedirect(route('laporan.lihat', $laporan));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->fresh()->catatan_revisi)->toBe('Surat keterangan kematian kurang jelas.');

    $this->get(route('pengajuan.detail', $laporan))
        ->assertOk()
        ->assertDontSee('Catatan Revisi')
        ->assertDontSee('Surat keterangan kematian kurang jelas.');
});

it('menampilkan catatan revisi dan tombol perbaikan pada detail pengajuan', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanRevisi($pemohon);

    $this->actingAs($pemohon)->get(route('pengajuan.detail', $laporan))
        ->assertOk()
        ->assertSee('Surat keterangan kematian kurang jelas.')
        ->assertSee('Perbaiki dan Kirim Ulang')
        ->assertSee(route('laporan.ubah', $laporan));
});
