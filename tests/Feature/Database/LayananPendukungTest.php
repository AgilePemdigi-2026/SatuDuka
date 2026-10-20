<?php

use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\LayananAudit;
use App\Services\LayananNomorPengajuan;
use App\Services\LayananPenguncian;
use Illuminate\Support\Facades\Cache;

/**
 * Pengujian layanan pendukung Fase 1: audit, penguncian, penomoran pengajuan,
 * dan pencarian NIK/NIP.
 */
it('mencatat jejak audit melalui layanan audit', function () {
    $pengguna = Pengguna::factory()->create();

    $log = LayananAudit::catat(
        aksi: 'mengajukan_laporan',
        objek: 'laporan_kematian',
        idObjek: 42,
        detail: ['sumber' => 'formulir pengajuan'],
        pengguna: $pengguna,
    );

    expect($log)->toBeInstanceOf(LogAudit::class)
        ->and($log->aksi)->toBe('mengajukan_laporan')
        ->and($log->objek)->toBe('laporan_kematian')
        ->and($log->id_objek)->toBe(42)
        ->and($log->detail)->toBe(['sumber' => 'formulir pengajuan'])
        ->and($log->pengguna->is($pengguna))->toBeTrue()
        ->and($log->dibuat_pada)->not->toBeNull();
});

it('mencatat hasil pemanggilan layanan eksternal', function () {
    $log = LayananAudit::catatLayanan(
        layanan: LogAudit::LAYANAN_SIAK,
        hasil: LogAudit::HASIL_GAGAL,
        detail: ['nik' => '9999990101900101'],
    );

    expect($log->layanan_eksternal)->toBe('SIAK')
        ->and($log->hasil)->toBe('Gagal')
        ->and($log->aksi)->toBe('memeriksa_layanan_eksternal');
});

it('menyimpan penghitung kegagalan kunci pada cache dan mencatat penguncian ke log audit', function () {
    Cache::flush();

    expect(LayananPenguncian::terkunci('nik-9999990101900001'))->toBeFalse();

    LayananPenguncian::catatGagal('nik-9999990101900001');
    LayananPenguncian::catatGagal('nik-9999990101900001');

    expect(LayananPenguncian::jumlahGagal('nik-9999990101900001'))->toBe(2)
        ->and(LayananPenguncian::terkunci('nik-9999990101900001'))->toBeFalse();

    $jumlah = LayananPenguncian::catatGagalDanKunciBilaPerlu(
        kunci: 'nik-9999990101900001',
        maksGagal: LayananPenguncian::MAKS_GAGAL_TTE,
    );

    expect($jumlah)->toBe(3)
        ->and(LayananPenguncian::terkunci('nik-9999990101900001'))->toBeTrue()
        ->and(LayananPenguncian::sisaKunci('nik-9999990101900001'))->toBeGreaterThan(0);

    expect(LogAudit::where('aksi', 'mengunci_akses')->count())->toBe(1);

    LayananPenguncian::buka('nik-9999990101900001');

    expect(LayananPenguncian::terkunci('nik-9999990101900001'))->toBeFalse()
        ->and(LayananPenguncian::jumlahGagal('nik-9999990101900001'))->toBe(0);
});

it('mencari pengguna berdasarkan nik dan nip', function () {
    $pemohon = Pengguna::factory()->pemohon()->create();
    $verifikator = Pengguna::factory()->verifikator()->create();

    expect(Pengguna::cariByNik($pemohon->nik)?->is($pemohon))->toBeTrue()
        ->and(Pengguna::cariByNip($verifikator->nip)?->is($verifikator))->toBeTrue()
        ->and(Pengguna::cariByNik('0000000000000000'))->toBeNull()
        ->and(Pengguna::cariDenganNikAtauNip($pemohon->nik)?->is($pemohon))->toBeTrue()
        ->and(Pengguna::cariDenganNikAtauNip($verifikator->nip)?->is($verifikator))->toBeTrue()
        ->and(Pengguna::cariDenganNikAtauNip('12345'))->toBeNull();
});

it('membuat nomor pengajuan dengan pola SD-YYYYMMDD-NNNN', function () {
    $nomor = LayananNomorPengajuan::berikutnya();

    expect($nomor)->toMatch('/^SD-\d{8}-\d{4}$/');
});

it('membuat nomor pengajuan yang unik saat disimpan', function () {
    $pertama = LayananNomorPengajuan::buat(function (string $nomor) {
        return LaporanKematian::factory()->create(['no_pengajuan' => $nomor]);
    });

    $kedua = LayananNomorPengajuan::buat(function (string $nomor) {
        return LaporanKematian::factory()->create(['no_pengajuan' => $nomor]);
    });

    expect($pertama->no_pengajuan)->not->toBe($kedua->no_pengajuan)
        ->and(LaporanKematian::count())->toBe(2);
});
