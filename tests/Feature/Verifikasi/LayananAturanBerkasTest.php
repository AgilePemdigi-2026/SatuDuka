<?php

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Services\Verifikasi\AnomaliAturanBerkas;
use App\Services\Verifikasi\LayananAturanBerkas;
use Illuminate\Support\Carbon;

/**
 * Laporan untuk pengujian aturan berkas.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanAturanBerkas(array $timpa = []): LaporanKematian
{
    return LaporanKematian::factory()->create(array_merge([
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
        'tanggal_kematian' => now()->subDay()->toDateString(),
        'jenis_kasus' => LaporanKematian::JENIS_KASUS_NORMAL,
        'nik_almarhum' => '3471031203559001',
    ], $timpa));
}

function lampirkanBerkasAturanBerkas(
    LaporanKematian $laporan,
    string $jenis,
    string $status = BerkasPendukung::STATUS_BERHASIL,
): BerkasPendukung {
    return BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => $jenis,
        'status_unggahan' => $status,
    ]);
}

function lengkapiBerkasAturanBerkas(LaporanKematian $laporan, ?string $buktiKematian = null): void
{
    $buktiKematian ??= config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0];

    foreach (array_merge([$buktiKematian], config('verifikasi.berkas_wajib.lainnya')) as $jenis) {
        lampirkanBerkasAturanBerkas($laporan, $jenis);
    }
}

/**
 * @param  list<AnomaliAturanBerkas>  $anomali
 * @return list<string>
 */
function kodeAnomaliAturanBerkas(array $anomali): array
{
    return array_map(
        static fn (AnomaliAturanBerkas $item): string => $item->kode,
        $anomali,
    );
}

it('menandai berkas wajib yang belum lengkap', function () {
    $laporan = buatLaporanAturanBerkas();

    $anomali = (new LayananAturanBerkas)->periksa($laporan);

    expect(kodeAnomaliAturanBerkas($anomali))->toContain(LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP);

    $pesan = collect($anomali)->firstWhere('kode', LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP)->pesan;

    expect($pesan)->toContain(BerkasPendukung::JENIS_KTP_ALMARHUM)
        ->and($pesan)->toContain(BerkasPendukung::JENIS_KARTU_KELUARGA)
        ->and($pesan)->toContain(BerkasPendukung::JENIS_KTP_PELAPOR);
});

it('menerima berkas wajib yang lengkap', function () {
    $laporan = buatLaporanAturanBerkas();
    lengkapiBerkasAturanBerkas($laporan);

    $anomali = (new LayananAturanBerkas)->periksa($laporan);

    expect(kodeAnomaliAturanBerkas($anomali))->not->toContain(LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP);
});

it('menghitung berkas gagal sebagai belum ada', function () {
    $laporan = buatLaporanAturanBerkas();

    lampirkanBerkasAturanBerkas($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);
    lampirkanBerkasAturanBerkas($laporan, BerkasPendukung::JENIS_KTP_ALMARHUM, BerkasPendukung::STATUS_GAGAL);
    lampirkanBerkasAturanBerkas($laporan, BerkasPendukung::JENIS_KARTU_KELUARGA);
    lampirkanBerkasAturanBerkas($laporan, BerkasPendukung::JENIS_KTP_PELAPOR);

    $anomali = (new LayananAturanBerkas)->periksa($laporan);

    expect(kodeAnomaliAturanBerkas($anomali))->toContain(LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP)
        ->and(collect($anomali)->firstWhere('kode', LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP)->pesan)
        ->toContain(BerkasPendukung::JENIS_KTP_ALMARHUM);
});

it('tidak mewajibkan KTP saksi secara default', function () {
    $laporan = buatLaporanAturanBerkas();
    lengkapiBerkasAturanBerkas($laporan);

    $anomali = (new LayananAturanBerkas)->periksa($laporan);

    expect(kodeAnomaliAturanBerkas($anomali))->not->toContain(LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP);
});

it('mewajibkan KTP saksi saat konfigurasi mengaktifkannya', function () {
    config(['verifikasi.wajib_saksi' => true]);

    $laporan = buatLaporanAturanBerkas();
    lengkapiBerkasAturanBerkas($laporan);

    $anomali = (new LayananAturanBerkas)->periksa($laporan);
    $pesan = collect($anomali)->firstWhere('kode', LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP)?->pesan;

    expect($pesan)->not->toBeNull()
        ->and($pesan)->toContain(BerkasPendukung::JENIS_KTP_SAKSI_1)
        ->and($pesan)->toContain(BerkasPendukung::JENIS_KTP_SAKSI_2);
});

it('menerima tepat 30 hari dan menandai 31 hari tanpa SPTJM', function (int $selisihHari, bool $harusTerlambat) {
    $pengajuan = Carbon::parse('2026-10-31 10:00:00');

    $laporan = buatLaporanAturanBerkas([
        'tanggal_pengajuan' => $pengajuan,
        'tanggal_kematian' => $pengajuan->copy()->subDays($selisihHari)->toDateString(),
    ]);

    lengkapiBerkasAturanBerkas($laporan);

    $kode = kodeAnomaliAturanBerkas((new LayananAturanBerkas)->periksa($laporan));

    if ($harusTerlambat) {
        expect($kode)->toContain(LayananAturanBerkas::KODE_TERLAMBAT_TANPA_SPTJM);
    } else {
        expect($kode)->not->toContain(LayananAturanBerkas::KODE_TERLAMBAT_TANPA_SPTJM);
    }
})->with([
    'tepat 30 hari' => [30, false],
    '31 hari' => [31, true],
]);

it('menerima keterlambatan bila SPTJM tersedia sebagai pengganti surat dokter', function () {
    $pengajuan = Carbon::parse('2026-10-31 10:00:00');

    $laporan = buatLaporanAturanBerkas([
        'tanggal_pengajuan' => $pengajuan,
        'tanggal_kematian' => $pengajuan->copy()->subDays(31)->toDateString(),
    ]);

    lengkapiBerkasAturanBerkas($laporan, BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN);

    $kode = kodeAnomaliAturanBerkas((new LayananAturanBerkas)->periksa($laporan));

    expect($kode)->not->toContain(LayananAturanBerkas::KODE_TERLAMBAT_TANPA_SPTJM)
        ->and($kode)->not->toContain(LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP);
});

it('menandai tanggal kematian setelah tanggal pengajuan', function () {
    $pengajuan = Carbon::parse('2026-10-31 10:00:00');

    $laporan = buatLaporanAturanBerkas([
        'tanggal_pengajuan' => $pengajuan,
        'tanggal_kematian' => $pengajuan->copy()->addDay()->toDateString(),
    ]);

    lengkapiBerkasAturanBerkas($laporan);

    expect(kodeAnomaliAturanBerkas((new LayananAturanBerkas)->periksa($laporan)))
        ->toContain(LayananAturanBerkas::KODE_TANGGAL_TIDAK_KONSISTEN);
});

it('menandai kasus khusus tanpa surat kepolisian', function () {
    $laporan = buatLaporanAturanBerkas([
        'jenis_kasus' => LaporanKematian::JENIS_KASUS_TIDAK_DIKENAL,
    ]);
    lengkapiBerkasAturanBerkas($laporan);

    $anomali = (new LayananAturanBerkas)->periksa($laporan);
    $kode = kodeAnomaliAturanBerkas($anomali);

    expect($kode)->toContain(LayananAturanBerkas::KODE_KASUS_KHUSUS)
        ->and($kode)->toContain(LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP)
        ->and(collect($anomali)->firstWhere('kode', LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP)->pesan)
        ->toContain(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN);
});

it('menerima kasus khusus yang sudah memiliki surat kepolisian', function () {
    $laporan = buatLaporanAturanBerkas([
        'jenis_kasus' => LaporanKematian::JENIS_KASUS_TIDAK_DITEMUKAN,
    ]);
    lengkapiBerkasAturanBerkas($laporan);
    lampirkanBerkasAturanBerkas($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN);

    $kode = kodeAnomaliAturanBerkas((new LayananAturanBerkas)->periksa($laporan));

    expect($kode)->toContain(LayananAturanBerkas::KODE_KASUS_KHUSUS)
        ->and($kode)->not->toContain(LayananAturanBerkas::KODE_BERKAS_TIDAK_LENGKAP);
});

it('menandai pengajuan ganda pada NIK yang sama', function () {
    $lain = buatLaporanAturanBerkas(['nik_almarhum' => '3471031203559001']);

    $laporan = buatLaporanAturanBerkas([
        'nik_almarhum' => '3471031203559001',
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
    ]);
    lengkapiBerkasAturanBerkas($laporan);

    expect(kodeAnomaliAturanBerkas((new LayananAturanBerkas)->periksa($laporan)))
        ->toContain(LayananAturanBerkas::KODE_PENGAJUAN_GANDA)
        ->and($lain->id_laporan)->not->toBe($laporan->id_laporan);
});

it('menganggap pengajuan aktif atau selesai sebagai pengajuan ganda', function (string $status) {
    buatLaporanAturanBerkas([
        'nik_almarhum' => '3471031203559001',
        'status_pengajuan' => $status,
        'tanggal_pengajuan' => now(),
    ]);

    $laporan = buatLaporanAturanBerkas(['nik_almarhum' => '3471031203559001']);
    lengkapiBerkasAturanBerkas($laporan);

    expect(kodeAnomaliAturanBerkas((new LayananAturanBerkas)->periksa($laporan)))
        ->toContain(LayananAturanBerkas::KODE_PENGAJUAN_GANDA);
})->with([
    'menunggu verifikasi' => [LaporanKematian::STATUS_MENUNGGU_VERIFIKASI],
    'dalam rekonsiliasi' => [LaporanKematian::STATUS_DALAM_REKONSILIASI],
    'menunggu TTE' => [LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE],
    'menunggu perbaikan' => [LaporanKematian::STATUS_MENUNGGU_PERBAIKAN],
    'selesai' => [LaporanKematian::STATUS_SELESAI],
]);

it('tidak menganggap draf, ditolak, atau dikembalikan sebagai pengajuan ganda', function (string $status) {
    buatLaporanAturanBerkas([
        'nik_almarhum' => '3471031203559001',
        'status_pengajuan' => $status,
        'tanggal_pengajuan' => $status === LaporanKematian::STATUS_DRAF ? null : now(),
    ]);

    $laporan = buatLaporanAturanBerkas(['nik_almarhum' => '3471031203559001']);
    lengkapiBerkasAturanBerkas($laporan);

    expect(kodeAnomaliAturanBerkas((new LayananAturanBerkas)->periksa($laporan)))
        ->not->toContain(LayananAturanBerkas::KODE_PENGAJUAN_GANDA);
})->with([
    'draf' => [LaporanKematian::STATUS_DRAF],
    'ditolak butuh revisi' => [LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI],
    'dikembalikan' => [LaporanKematian::STATUS_DIKEMBALIKAN],
]);

it('tidak menandai laporan itu sendiri sebagai pengajuan ganda', function () {
    $laporan = buatLaporanAturanBerkas(['nik_almarhum' => '3471031203559001']);
    lengkapiBerkasAturanBerkas($laporan);

    expect(kodeAnomaliAturanBerkas((new LayananAturanBerkas)->periksa($laporan)))
        ->not->toContain(LayananAturanBerkas::KODE_PENGAJUAN_GANDA);
});

it('tidak mengubah status laporan saat memeriksa anomali', function () {
    $laporan = buatLaporanAturanBerkas(['status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI]);

    (new LayananAturanBerkas)->periksa($laporan);

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('tetap memeriksa banyak laporan factory dengan NIK serupa tanpa mengubah data', function () {
    LaporanKematian::factory()->count(12)->denganStatus(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)->create([
        'nik_almarhum' => '3471031203559001',
    ]);

    $laporan = buatLaporanAturanBerkas(['nik_almarhum' => '3471031203559001']);
    lengkapiBerkasAturanBerkas($laporan);

    expect((new LayananAturanBerkas)->adaPengajuanGanda('3471031203559001', (int) $laporan->id_laporan))->toBeTrue()
        ->and(LaporanKematian::query()->where('nik_almarhum', '3471031203559001')->count())->toBe(13);
});
