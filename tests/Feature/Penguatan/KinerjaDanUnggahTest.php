<?php

/**
 * Pengujian kinerja ringan (Fase 9): jumlah query pada daftar utama dan
 * penolakan unggahan dengan MIME palsu.
 */

use App\Models\BerkasPendukung;
use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile as UnggahanAsli;

/**
 * @return array{0: int, 1: TestResponse}
 */
function hitungQuery(callable $panggil): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $respons = $panggil();

    $jumlah = count(DB::getQueryLog());
    DB::disableQueryLog();

    return [$jumlah, $respons];
}

beforeEach(function () {
    Storage::fake('local');
});

it('membatasi jumlah query pada antrean verifikasi', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    LaporanKematian::factory()->count(5)->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
    ]);

    [$jumlah, $respons] = hitungQuery(fn () => $this->actingAs($verifikator)
        ->get(route('verifikasi.index')));

    $respons->assertOk();

    expect($jumlah)->toBeLessThanOrEqual(60, 'Query antrean verifikasi: '.$jumlah);
});

it('membatasi jumlah query pada antrean rekonsiliasi', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    LaporanKematian::factory()->count(5)->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DALAM_REKONSILIASI,
        'tanggal_pengajuan' => now(),
    ]);

    [$jumlah, $respons] = hitungQuery(fn () => $this->actingAs($verifikator)
        ->get(route('rekonsiliasi.index')));

    $respons->assertOk();

    expect($jumlah)->toBeLessThanOrEqual(40, 'Query antrean rekonsiliasi: '.$jumlah);
});

it('membatasi jumlah query pada antrean tanda tangan', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();

    LaporanKematian::factory()->count(5)->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
        'tanggal_pengajuan' => now(),
    ])->each(function (LaporanKematian $laporan): void {
        DokumenResmi::factory()->create([
            'id_laporan' => $laporan->id_laporan,
            'status_ttd' => DokumenResmi::STATUS_MENUNGGU,
        ]);
    });

    [$jumlah, $respons] = hitungQuery(fn () => $this->actingAs($pejabat)
        ->get(route('tanda_tangan.index')));

    $respons->assertOk();

    expect($jumlah)->toBeLessThanOrEqual(25, 'Query antrean TTE: '.$jumlah);
});

it('membatasi jumlah query pada dasbor setiap peran', function () {
    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();

    LaporanKematian::factory()->count(5)->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
    ]);

    foreach ([$pemohon, $verifikator, $pejabat] as $pengguna) {
        [$jumlah, $respons] = hitungQuery(fn () => $this->actingAs($pengguna)
            ->get(route('dashboard')));

        $respons->assertOk();

        expect($jumlah)->toBeLessThanOrEqual(25, 'Query dasbor '.$pengguna->peran.': '.$jumlah);
    }
});

it('menolak unggahan berkas dengan MIME palsu', function () {
    [$pemohon] = buatAkunDemo();

    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
    ]);

    // Berkas nyata dengan ekstensi .pdf tetapi isinya skrip PHP: MIME harus
    // dibaca dari isi, bukan dari nama atau header klien.
    $jalur = tempnam(sys_get_temp_dir(), 'uji-mime-');
    file_put_contents($jalur, '<?php echo "bukan pdf";');

    $palsu = new UnggahanAsli($jalur, 'surat.pdf', null, null, true);

    $this->actingAs($pemohon)
        ->post(route('laporan.berkas.unggah', $laporan), [
            'berkas_ktp_pelapor' => $palsu,
            'jenis_dokumen' => BerkasPendukung::JENIS_KTP_PELAPOR,
        ])
        ->assertSessionHasErrors();

    expect(BerkasPendukung::query()->count())->toBe(0);

    @unlink($jalur);
});

it('menyimpan unggahan PDF valid di disk privat dengan nama aman', function () {
    [$pemohon] = buatAkunDemo();

    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
    ]);

    $pdf = UploadedFile::fake()->createWithContent('../../KTP Saya.pdf', "%PDF-1.4\nisi uji\n");

    $this->actingAs($pemohon)
        ->post(route('laporan.berkas.unggah', $laporan), [
            'berkas_ktp_pelapor' => $pdf,
            'jenis_dokumen' => BerkasPendukung::JENIS_KTP_PELAPOR,
        ]);

    $berkas = BerkasPendukung::query()->first();

    if ($berkas !== null) {
        expect($berkas->path_file)->toStartWith('berkas_laporan/'.$laporan->id_laporan.'/')
            ->and($berkas->path_file)->not->toContain('..')
            ->and(Storage::disk('public')->exists($berkas->path_file))->toBeFalse()
            ->and(Storage::disk('local')->exists($berkas->path_file))->toBeTrue();
    }
});
