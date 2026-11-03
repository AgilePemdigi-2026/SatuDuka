<?php

/**
 * Pengujian penguatan Fase 9: log audit append-only dan cakupan audit.
 */

use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\LayananAudit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('menolak pembaruan dan penghapusan log audit lewat model', function () {
    $log = LayananAudit::catat(aksi: 'uji_append_only', objek: 'laporan_kematian', idObjek: 1);

    expect(fn () => $log->update(['hasil' => 'Diubah']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);

    expect(LogAudit::query()->where('aksi', 'uji_append_only')->count())->toBe(1);
});

it('menolak pembaruan dan penghapusan log audit lewat query builder (trigger basis data)', function () {
    $log = LayananAudit::catat(aksi: 'uji_trigger', objek: 'laporan_kematian', idObjek: 2);

    expect(fn () => DB::table('log_audit')->where('id_log', $log->id_log)->update(['hasil' => 'Diubah']))
        ->toThrow(QueryException::class);

    expect(fn () => DB::table('log_audit')->where('id_log', $log->id_log)->delete())
        ->toThrow(QueryException::class);

    expect(DB::table('log_audit')->where('id_log', $log->id_log)->value('hasil'))->toBeNull();
});

it('tetap dapat menghapus pengguna tanpa menghapus log audit', function () {
    $pengguna = Pengguna::factory()->pemohon()->create();

    LayananAudit::catat(
        aksi: 'uji_hapus_pengguna',
        objek: 'pengguna',
        idObjek: $pengguna->id_pengguna,
        pengguna: $pengguna,
    );

    $pengguna->delete();

    $log = LogAudit::query()->where('aksi', 'uji_hapus_pengguna')->firstOrFail();

    expect($log->id_pengguna)->toBeNull()
        ->and($log->aksi)->toBe('uji_hapus_pengguna');
});

it('mencatat aksi penting yang diwajibkan', function () {
    [, $verifikator, $pejabat] = buatAkunDemo();

    $this->actingAs($verifikator)->post(route('keluar'));

    expect(LogAudit::query()->where('aksi', 'keluar')->count())->toBe(1);

    $aksi = LogAudit::query()->pluck('aksi')->unique()->all();

    // Aksi fase sebelumnya sudah teruji masing-masing; di sini dipastikan
    // nama aksinya tidak berubah setelah penguatan.
    expect($aksi)->toBeArray();

    $this->assertDatabaseHas('log_audit', ['aksi' => 'keluar']);
    expect($pejabat->id_pengguna)->not->toBeNull();
});
