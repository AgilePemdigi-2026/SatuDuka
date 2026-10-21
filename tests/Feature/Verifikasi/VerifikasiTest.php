<?php

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Laporan milik pemohon dengan status tertentu untuk pengujian verifikasi.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanVerifikasi(Pengguna $pemohon, array $timpa = []): LaporanKematian
{
    return LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '9999990101900101',
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
    ], $timpa));
}

/**
 * Melampirkan satu berkas pendukung pada penyimpanan lokal palsu.
 */
function lampirkanBerkasVerifikasi(
    LaporanKematian $laporan,
    string $jenis,
    string $namaFile,
): BerkasPendukung {
    $ekstensi = pathinfo($namaFile, PATHINFO_EXTENSION) ?: 'pdf';
    $path = 'berkas_verifikasi/'.$laporan->id_laporan.'/'.Str::random(10).'.'.$ekstensi;
    Storage::disk('local')->put($path, '%PDF-1.4 ISI-DOKUMEN-VERIFIKASI');

    return BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => $jenis,
        'nama_file' => $namaFile,
        'path_file' => $path,
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
    ]);
}

it('menampilkan daftar menunggu verifikasi untuk verifikator', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $menunggu = buatLaporanVerifikasi($pemohon);
    $draf = buatLaporanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.daftar'))
        ->assertOk()
        ->assertSee($menunggu->no_pengajuan)
        ->assertDontSee($draf->no_pengajuan);
});

it('menolak daftar verifikasi untuk pemohon dan pejabat', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();

    $this->actingAs($pemohon)->get(route('verifikasi.daftar'))->assertForbidden();
    $this->actingAs($pejabat)->get(route('verifikasi.daftar'))->assertForbidden();
});

it('membatasi daftar verifikasi sepuluh pengajuan per halaman', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    LaporanKematian::factory()->count(12)->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
    ]);

    $respons = $this->actingAs($verifikator)->get(route('verifikasi.daftar'))->assertOk();
    $halaman = $respons->viewData('daftar');

    expect($halaman->total())->toBe(12)
        ->and(count($halaman->items()))->toBe(10);
});

it('menampilkan detail baca saja beserta pratinjau berkas', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-keterangan.pdf',
    );

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Suharto Wiryo')
        ->assertSee('surat-keterangan.pdf')
        ->assertSee('Baca dokumen (AI)')
        ->assertDontSee('Setujui')
        ->assertDontSee('Tolak');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.berkas', [$laporan, $berkas]))
        ->assertOk();
});

it('menolak detail laporan yang bukan menunggu verifikasi', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $draf = buatLaporanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $draf))
        ->assertNotFound();
});

it('menolak pratinjau berkas lintas laporan', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporanA = buatLaporanVerifikasi($pemohon);
    $laporanB = buatLaporanVerifikasi($pemohon);
    $berkasB = lampirkanBerkasVerifikasi($laporanB, BerkasPendukung::JENIS_KTP_PELAPOR, 'ktp-pelapor.pdf');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.berkas', [$laporanA, $berkasB]))
        ->assertNotFound();
});

it('menolak pratinjau berkas untuk pemohon', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi($laporan, BerkasPendukung::JENIS_KTP_PELAPOR, 'ktp-pelapor.pdf');

    $this->actingAs($pemohon)
        ->get(route('verifikasi.berkas', [$laporan, $berkas]))
        ->assertForbidden();
});

it('menjalankan analisis ai untuk verifikator', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
        ->assertRedirect(route('verifikasi.detail', $laporan))
        ->assertSessionHas('analisis_ai');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.detail', $laporan))
        ->assertOk()
        ->assertSee('Hasil bantuan AI, hanya saran')
        ->assertSee('cocok');

    expect(AnalisisAi::query()
        ->where('id_laporan', $laporan->id_laporan)
        ->where('id_berkas', $berkas->id_berkas)
        ->count())->toBe(2)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);

    $audit = LogAudit::query()->where('aksi', 'menganalisis_dokumen_ai')->firstOrFail();

    expect($audit->layanan_eksternal)->toBe(LogAudit::LAYANAN_AI)
        ->and($audit->detail)->toMatchArray([
            'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
            'model' => 'mock-ai-v1',
            'pemicu' => 'verifikator_detail',
        ]);
});

it('menolak analisis ai verifikator untuk berkas di luar laporan', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporanA = buatLaporanVerifikasi($pemohon);
    $laporanB = buatLaporanVerifikasi($pemohon);
    $berkasB = lampirkanBerkasVerifikasi(
        $laporanB,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporanA), ['id_berkas' => $berkasB->id_berkas])
        ->assertRedirect(route('verifikasi.detail', $laporanA))
        ->assertSessionHas('galat');

    expect(AnalisisAi::query()->count())->toBe(0);
});

it('menolak analisis ai untuk pemohon', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasi(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'surat-cocok.pdf',
    );

    $this->actingAs($pemohon)
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
        ->assertForbidden();
});
