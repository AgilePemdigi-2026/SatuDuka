<?php

use App\Models\AnomaliDokumen;
use App\Models\BerkasPendukung;
use App\Models\DokumenResmi;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Pengujian relasi basis data sesuai Tabel 8.2 SRS.
 *
 * Pengujian memakai SQLite in-memory (lihat phpunit.xml) sehingga tidak pernah
 * menyentuh basis data Supabase.
 */
it('menyimpan pengguna dengan kolom dan peran yang benar', function () {
    $pengguna = Pengguna::factory()->pemohon()->create([
        'nama_lengkap' => 'Warga Contoh',
        'email' => 'warga.contoh@demo.satuduka.id',
    ]);

    expect($pengguna->id_pengguna)->toBeInt()
        ->and($pengguna->getTable())->toBe('pengguna')
        ->and($pengguna->getKeyName())->toBe('id_pengguna')
        ->and($pengguna->peran)->toBe(Pengguna::PERAN_PEMOHON)
        ->and($pengguna->nik)->not->toBeNull()
        ->and($pengguna->nip)->toBeNull();
});

it('menyimpan kata sandi sebagai hash dan mengenali kolom autentikasi', function () {
    $pengguna = Pengguna::factory()->create([
        'kata_sandi' => 'password123',
    ]);

    expect($pengguna->getAuthIdentifierName())->toBe('id_pengguna')
        ->and($pengguna->getAuthPasswordName())->toBe('kata_sandi')
        ->and($pengguna->getAuthPassword())->not->toBe('password123')
        ->and(Hash::check('password123', $pengguna->getAuthPassword()))->toBeTrue();
});

it('memiliki relasi pengguna satu ke banyak laporan kematian', function () {
    $pengguna = Pengguna::factory()->pemohon()->create();
    $laporan = LaporanKematian::factory()->create(['id_pengguna' => $pengguna->id_pengguna]);

    expect($pengguna->laporanKematian)->toHaveCount(1)
        ->and($laporan->pemohon->is($pengguna))->toBeTrue();
});

it('memiliki relasi laporan kematian satu ke banyak berkas pendukung', function () {
    $laporan = LaporanKematian::factory()->create();
    $berkas = BerkasPendukung::factory()->count(3)->create(['id_laporan' => $laporan->id_laporan]);

    expect($laporan->berkasPendukung)->toHaveCount(3)
        ->and($berkas->first()->laporanKematian->is($laporan))->toBeTrue();
});

it('memiliki relasi laporan kematian satu ke banyak hasil verifikasi', function () {
    $laporan = LaporanKematian::factory()->create();
    $hasil = HasilVerifikasi::factory()->count(2)->create(['id_laporan' => $laporan->id_laporan]);

    expect($laporan->hasilVerifikasi)->toHaveCount(2)
        ->and($hasil->first()->laporanKematian->is($laporan))->toBeTrue();
});

it('memiliki relasi pengguna satu ke banyak hasil verifikasi', function () {
    $verifikator = Pengguna::factory()->verifikator()->create();
    $hasil = HasilVerifikasi::factory()->create(['id_pengguna' => $verifikator->id_pengguna]);

    expect($verifikator->hasilVerifikasi)->toHaveCount(1)
        ->and($hasil->first()->verifikator->is($verifikator))->toBeTrue();
});

it('memiliki relasi hasil verifikasi satu ke banyak anomali dokumen', function () {
    $hasil = HasilVerifikasi::factory()->create();
    $anomali = AnomaliDokumen::factory()->count(2)->create(['id_verifikasi' => $hasil->id_verifikasi]);

    expect($hasil->anomaliDokumen)->toHaveCount(2)
        ->and($anomali->first()->hasilVerifikasi->is($hasil))->toBeTrue();
});

it('memiliki relasi laporan kematian satu ke satu dokumen resmi', function () {
    $laporan = LaporanKematian::factory()->create();
    $dokumen = DokumenResmi::factory()->create(['id_laporan' => $laporan->id_laporan]);

    expect($laporan->dokumenResmi->is($dokumen))->toBeTrue()
        ->and($dokumen->laporanKematian->is($laporan))->toBeTrue();
});

it('memiliki relasi pengguna satu ke banyak dokumen resmi', function () {
    $pejabat = Pengguna::factory()->pejabat()->create();
    $dokumen = DokumenResmi::factory()->count(2)->create(['id_pengguna' => $pejabat->id_pengguna]);

    expect($pejabat->dokumenResmi)->toHaveCount(2)
        ->and($dokumen->first()->pejabat->is($pejabat))->toBeTrue();
});

it('menyimpan log audit tanpa kolom ubah dan menolak pembaruan', function () {
    $pengguna = Pengguna::factory()->create();
    $log = LogAudit::factory()->create([
        'id_pengguna' => $pengguna->id_pengguna,
        'aksi' => 'mengajukan_laporan',
        'objek' => 'laporan_kematian',
        'detail' => ['sumber' => 'formulir pengajuan'],
    ]);

    expect($log->getTable())->toBe('log_audit')
        ->and($log->getKeyName())->toBe('id_log')
        ->and($log->dibuat_pada)->not->toBeNull()
        ->and($log->detail)->toBe(['sumber' => 'formulir pengajuan'])
        ->and($log->pengguna->is($pengguna))->toBeTrue()
        ->and($pengguna->logAudit)->toHaveCount(1);

    expect(fn () => $log->update(['aksi' => 'diubah']))->toThrow(LogicException::class);
    expect(fn () => $log->delete())->toThrow(LogicException::class);
});

it('menyimpan kolom waktu domain dengan benar', function () {
    $laporan = LaporanKematian::factory()->create([
        'tanggal_kematian' => '2026-01-15',
        'tanggal_pengajuan' => '2026-01-16 09:30:00',
    ]);

    expect($laporan->tanggal_kematian->toDateString())->toBe('2026-01-15')
        ->and($laporan->tanggal_pengajuan->format('Y-m-d H:i:s'))->toBe('2026-01-16 09:30:00')
        ->and($laporan->perlu_verifikasi_manual)->toBeFalse();
});

it('menyimpan serta memperbarui kolom waktu buat dan ubah pada tabel pengajuan', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-01 08:00:00'));

    $laporan = LaporanKematian::factory()->create();

    expect($laporan->dibuat_pada->toDateTimeString())->toBe('2026-02-01 08:00:00')
        ->and($laporan->diperbarui_pada->toDateTimeString())->toBe('2026-02-01 08:00:00');

    Carbon::setTestNow(Carbon::parse('2026-02-01 09:30:00'));

    $laporan->update(['sebab_kematian' => 'Penyakit Jantung Koroner']);
    $laporan->refresh();

    expect($laporan->dibuat_pada->toDateTimeString())->toBe('2026-02-01 08:00:00')
        ->and($laporan->diperbarui_pada->toDateTimeString())->toBe('2026-02-01 09:30:00');

    Carbon::setTestNow();
});

it('menyimpan kolom waktu buat dan ubah pada tabel verifikasi, anomali, dan dokumen', function () {
    $hasil = HasilVerifikasi::factory()->create();
    $anomali = AnomaliDokumen::factory()->create();
    $dokumen = DokumenResmi::factory()->create();

    expect($hasil->dibuat_pada)->not->toBeNull()
        ->and($hasil->diperbarui_pada)->not->toBeNull()
        ->and($anomali->dibuat_pada)->not->toBeNull()
        ->and($anomali->diperbarui_pada)->not->toBeNull()
        ->and($dokumen->dibuat_pada)->not->toBeNull()
        ->and($dokumen->diperbarui_pada)->not->toBeNull();
});
