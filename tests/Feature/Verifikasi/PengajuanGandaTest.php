<?php

use App\Http\Controllers\LaporanController;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;

/**
 * Draf dengan berkas wajib lengkap untuk pengujian pengajuan ganda.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatDrafPengajuanGanda(Pengguna $pemohon, array $timpa = []): LaporanKematian
{
    $laporan = LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'no_pengajuan' => null,
        'tanggal_pengajuan' => null,
        'tanggal_kematian' => now()->subDay()->toDateString(),
        'nama_almarhum' => 'Budi Santoso',
        'nik_almarhum' => '3471036407609002',
        'jenis_kasus' => LaporanKematian::JENIS_KASUS_NORMAL,
    ], $timpa));

    $jenisWajib = array_merge(
        [config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0]],
        config('verifikasi.berkas_wajib.lainnya'),
    );

    foreach ($jenisWajib as $jenis) {
        BerkasPendukung::factory()->create([
            'id_laporan' => $laporan->id_laporan,
            'jenis_dokumen' => $jenis,
            'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
        ]);
    }

    return $laporan;
}

/**
 * Isian pengiriman dengan NIK almarhum tertentu.
 *
 * @param  array<string, mixed>  $timpa
 * @return array<string, mixed>
 */
function dataKirimPengajuanGanda(string $nik, array $timpa = []): array
{
    return array_merge([
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Siti Aminah',
        'nik_almarhum' => $nik,
        'tanggal_kematian' => now()->subDay()->toDateString(),
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
        'pernyataan' => '1',
    ], $timpa);
}

function buatPengajuanLainPengajuanGanda(string $nik, string $status): LaporanKematian
{
    return LaporanKematian::factory()->create([
        'status_pengajuan' => $status,
        'tanggal_pengajuan' => $status === LaporanKematian::STATUS_DRAF ? null : now(),
        'tanggal_kematian' => now()->subDay()->toDateString(),
        'nik_almarhum' => $nik,
    ]);
}

it('mengizinkan simpan draf meskipun NIK sudah ada pada pengajuan aktif', function () {
    [$pemohon] = buatAkunDemo();
    $nik = '3471031203559001';

    buatPengajuanLainPengajuanGanda($nik, LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);

    $this->actingAs($pemohon)
        ->post(route('laporan.draf'), dataKirimPengajuanGanda($nik))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $draf = LaporanKematian::query()->where('id_pengguna', $pemohon->id_pengguna)->firstOrFail();

    expect($draf->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF)
        ->and($draf->nik_almarhum)->toBe($nik);

    $this->post(route('laporan.draf.ubah', $draf), dataKirimPengajuanGanda($nik))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($draf->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF);
});

it('menolak kirim bila NIK sudah ada pada pengajuan aktif atau selesai', function (string $status) {
    [$pemohon] = buatAkunDemo();
    $nik = '3471031203559001';

    buatPengajuanLainPengajuanGanda($nik, $status);
    $laporan = buatDrafPengajuanGanda($pemohon);

    $respons = $this->actingAs($pemohon)
        ->post(route('laporan.kirim', $laporan), dataKirimPengajuanGanda($nik));

    $respons->assertRedirect(route('laporan.ubah', $laporan).'?langkah=2')
        ->assertSessionHasErrors('nik_almarhum');

    expect($respons->getSession()->get('errors')->first('nik_almarhum'))
        ->toBe(LaporanController::PESAN_PENGAJUAN_GANDA)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DRAF)
        ->and($laporan->fresh()->no_pengajuan)->toBeNull()
        ->and(LogAudit::query()->where('aksi', 'mengajukan_laporan')->count())->toBe(0);
})->with([
    'menunggu verifikasi' => [LaporanKematian::STATUS_MENUNGGU_VERIFIKASI],
    'dalam rekonsiliasi' => [LaporanKematian::STATUS_DALAM_REKONSILIASI],
    'menunggu TTE' => [LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE],
    'menunggu perbaikan' => [LaporanKematian::STATUS_MENUNGGU_PERBAIKAN],
    'selesai' => [LaporanKematian::STATUS_SELESAI],
]);

it('mengizinkan kirim bila NIK hanya ada pada pengajuan yang tidak aktif', function (string $status) {
    [$pemohon] = buatAkunDemo();
    $nik = '3471031203559001';

    buatPengajuanLainPengajuanGanda($nik, $status);
    $laporan = buatDrafPengajuanGanda($pemohon);

    $this->actingAs($pemohon)
        ->post(route('laporan.kirim', $laporan), dataKirimPengajuanGanda($nik))
        ->assertRedirect(route('laporan.lihat', $laporan));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->fresh()->no_pengajuan)->not->toBeNull();
})->with([
    'draf' => [LaporanKematian::STATUS_DRAF],
    'ditolak butuh revisi' => [LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI],
    'dikembalikan' => [LaporanKematian::STATUS_DIKEMBALIKAN],
]);
