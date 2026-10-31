<?php

/**
 * Pengujian pembuatan PDF Kutipan Akta Kematian (dokumen resmi).
 *
 * Seluruh pengujian memakai SQLite in-memory, disk `local` palsu, dan driver
 * TTE mock. Berkas PDF tidak boleh berada di disk publik.
 */

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\Dokumen\LayananDokumenResmi;
use App\Services\Dokumen\LayananNomorDokumen;
use App\Services\Dokumen\PembuatPdf;
use App\Services\Dokumen\PembuatPdfDompdf;
use App\Support\MaskerNik;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'satuduka.tte_driver' => 'mock',
        'satuduka.tte_pin_dummy' => '123456',
    ]);

    Cache::flush();
    Storage::fake('local');
    Storage::fake('public');
});

/**
 * Pengajuan Selesai beserta dokumen resmi yang sudah ditandatangani.
 *
 * @param  array<string, mixed>  $timpaLaporan
 * @param  array<string, mixed>  $timpaDokumen
 * @return array{0: LaporanKematian, 1: DokumenResmi}
 */
function buatDokumenSelesai(Pengguna $pemohon, array $timpaLaporan = [], array $timpaDokumen = []): array
{
    $laporan = LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
        'tanggal_pengajuan' => now(),
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
    ], $timpaLaporan));

    $dokumen = DokumenResmi::factory()->create(array_merge([
        'id_laporan' => $laporan->id_laporan,
        'status_ttd' => DokumenResmi::STATUS_SELESAI,
        'no_dokumen' => null,
        'file_dokumen' => null,
        'tanggal_terbit' => now(),
    ], $timpaDokumen));

    return [$laporan, $dokumen];
}

/**
 * Data tampilan untuk merender Blade PDF tanpa membuat berkas.
 *
 * @return array<string, mixed>
 */
function dataTampilanAkta(LaporanKematian $laporan, DokumenResmi $dokumen): array
{
    return [
        'nomorDokumen' => $dokumen->no_dokumen ?: 'SKD-2026-000001',
        'laporan' => $laporan,
        'dokumen' => $dokumen,
        'pejabat' => null,
        'siak' => [],
        'tanggalLahir' => '12 Maret 1955',
        'umur' => 71,
        'tanggalKematian' => '1 Oktober 2026',
        'tanggalTerbit' => '5 Oktober 2026',
        'alamat' => 'Jl. Malioboro No. 12, Yogyakarta',
        'mock' => config('satuduka.tte_driver') === 'mock',
    ];
}

it('membuat PDF saat dokumen ditandatangani dan menyimpannya di disk privat', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
    ], [
        'status_ttd' => DokumenResmi::STATUS_MENUNGGU,
        'tanggal_terbit' => null,
    ]);

    $this->actingAs($pejabat)
        ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertRedirect(route('tanda_tangan.index'));

    $dokumen->refresh();

    expect($dokumen->status_ttd)->toBe(DokumenResmi::STATUS_SELESAI)
        ->and($dokumen->no_dokumen)->toMatch('/^SKD-\d{4}-\d{6}$/')
        ->and($dokumen->file_dokumen)->not->toBeNull();

    expect(Storage::disk('local')->exists($dokumen->file_dokumen))->toBeTrue()
        ->and(Storage::disk('public')->exists($dokumen->file_dokumen))->toBeFalse();

    expect(Storage::disk('local')->get($dokumen->file_dokumen))->toStartWith('%PDF-');

    $audit = LogAudit::query()->where('aksi', 'menerbitkan_dokumen')->firstOrFail();

    expect(json_encode($audit->detail))->not->toContain($laporan->nik_almarhum);
});

it('membuat nomor dokumen unik dan menolak duplikat pada indeks unik', function () {
    [$pemohon] = buatAkunDemo();

    $nomor = [];

    for ($i = 0; $i < 3; $i++) {
        $laporan = LaporanKematian::factory()->create([
            'id_pengguna' => $pemohon->id_pengguna,
            'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
            'tanggal_pengajuan' => now(),
        ]);

        $dokumen = DokumenResmi::factory()->create([
            'id_laporan' => $laporan->id_laporan,
            'status_ttd' => DokumenResmi::STATUS_SELESAI,
            'no_dokumen' => null,
            'file_dokumen' => null,
            'tanggal_terbit' => now(),
        ]);

        expect(app(LayananDokumenResmi::class)->terbitkan($dokumen))->toBeTrue();

        $nomor[] = $dokumen->fresh()->no_dokumen;
    }

    expect(array_unique($nomor))->toHaveCount(3);

    $laporanLain = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
        'tanggal_pengajuan' => now(),
    ]);

    expect(fn () => DokumenResmi::factory()->create([
        'id_laporan' => $laporanLain->id_laporan,
        'no_dokumen' => $nomor[0],
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('memakai ulang nomor dokumen saat penerbitan diulang', function () {
    [$pemohon] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon);

    $layanan = app(LayananDokumenResmi::class);

    expect($layanan->terbitkan($dokumen))->toBeTrue();

    $nomorPertama = $dokumen->fresh()->no_dokumen;
    $berkasPertama = $dokumen->fresh()->file_dokumen;

    expect($nomorPertama)->not->toBeNull()
        ->and($berkasPertama)->not->toBeNull();

    // Terbitkan ulang tidak menimpa berkas dan tidak mengambil nomor baru.
    expect($layanan->terbitkan($dokumen->fresh()))->toBeTrue();

    $dokumen->refresh();

    expect($dokumen->no_dokumen)->toBe($nomorPertama)
        ->and($dokumen->file_dokumen)->toBe($berkasPertama);

    expect(LayananNomorDokumen::berikutnya())->not->toBe($nomorPertama);
});

it('tidak menimpa berkas dokumen yang sudah ada', function () {
    [$pemohon] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon);

    Storage::disk('local')->put('dokumen/berkas-lama.pdf', '%PDF-1.4 isi lama');
    $dokumen->update([
        'no_dokumen' => 'SKD-2026-000999',
        'file_dokumen' => 'dokumen/berkas-lama.pdf',
    ]);

    expect(app(LayananDokumenResmi::class)->terbitkan($dokumen->fresh()))->toBeTrue();

    expect(Storage::disk('local')->get('dokumen/berkas-lama.pdf'))->toBe('%PDF-1.4 isi lama')
        ->and($dokumen->fresh()->file_dokumen)->toBe('dokumen/berkas-lama.pdf');
});

it('mengunduh hanya untuk pemilik, setelah Selesai, dengan header aman', function () {
    Storage::fake('local');

    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();
    $pemohonLain = Pengguna::factory()->pemohon()->create();

    [$laporan, $dokumen] = buatDokumenSelesai($pemohon);

    expect(app(LayananDokumenResmi::class)->terbitkan($dokumen))->toBeTrue();

    $dokumen->refresh();

    // Pemohon lain tidak boleh mengunduh (IDOR).
    $this->actingAs($pemohonLain)
        ->get(route('pengajuan.unduh', $laporan))
        ->assertNotFound();

    // Verifikator tidak diberi akses unduh dokumen resmi.
    $this->actingAs($verifikator)
        ->get(route('tanda_tangan.unduh', $laporan))
        ->assertForbidden();

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.unduh', $laporan))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertDownload('Akta_Kematian_'.$dokumen->no_dokumen.'.pdf');

    $this->actingAs($pemohon)
        ->get(route('pengajuan.unduh', $laporan))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertDownload('Akta_Kematian_'.$dokumen->no_dokumen.'.pdf');
});

it('menolak unduh sebelum pengajuan berstatus Selesai', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
    ], [
        'status_ttd' => DokumenResmi::STATUS_MENUNGGU,
    ]);

    $this->actingAs($pemohon)
        ->get(route('pengajuan.unduh', $laporan))
        ->assertForbidden();

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.unduh', $laporan))
        ->assertForbidden();

    expect($dokumen->fresh()->file_dokumen)->toBeNull();
});

it('tidak menghasilkan galat 500 saat render PDF gagal dan dapat dipulihkan', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
    ], [
        'status_ttd' => DokumenResmi::STATUS_MENUNGGU,
        'tanggal_terbit' => null,
    ]);

    $nik = (string) $laporan->nik_almarhum;

    $this->app->instance(PembuatPdf::class, new class($nik) implements PembuatPdf
    {
        public function __construct(private readonly string $nik) {}

        public function hasil(string $tampilan, array $data): string
        {
            throw new RuntimeException('Gagal render untuk NIK '.$this->nik.' pada dompdf.');
        }
    });

    $this->actingAs($pejabat)
        ->post(route('tanda_tangan.tandatangani', $laporan), ['pin' => '123456'])
        ->assertRedirect(route('tanda_tangan.index'))
        ->assertSessionHas('galat', 'Dokumen resmi sedang disiapkan.');

    $dokumen->refresh();

    expect($dokumen->status_ttd)->toBe(DokumenResmi::STATUS_SELESAI)
        ->and($dokumen->file_dokumen)->toBeNull()
        ->and($dokumen->no_dokumen)->not->toBeNull();

    $nomorAwal = $dokumen->no_dokumen;

    $audit = LogAudit::query()->where('aksi', 'gagal_menerbitkan_dokumen')->firstOrFail();

    expect($audit->detail['kelas'])->toBe('RuntimeException')
        ->and($audit->detail['pesan'])->not->toContain($nik)
        ->and($audit->detail['pesan'])->toContain(MaskerNik::masker($nik));

    // Halaman pemohon tetap aman dan menampilkan pesan penyiapan.
    $this->actingAs($pemohon)
        ->get(route('pengajuan.detail', $laporan))
        ->assertOk()
        ->assertSee('Dokumen resmi sedang disiapkan');

    // Pulihkan dengan renderer nyata lalu tombol "Buat ulang".
    $this->app->instance(PembuatPdf::class, new PembuatPdfDompdf);

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.show', $laporan))
        ->assertOk()
        ->assertSee('Buat ulang dokumen');

    $this->post(route('tanda_tangan.terbitkan', $laporan))
        ->assertRedirect()
        ->assertSessionHas('sukses');

    $dokumen->refresh();

    expect($dokumen->no_dokumen)->toBe($nomorAwal)
        ->and($dokumen->file_dokumen)->not->toBeNull()
        ->and(Storage::disk('local')->exists($dokumen->file_dokumen))->toBeTrue();
});

it('memulihkan berkas yang hilang dari disk lewat perintah artisan', function () {
    [$pemohon] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon);

    expect(app(LayananDokumenResmi::class)->terbitkan($dokumen))->toBeTrue();

    $dokumen->refresh();
    $jalur = $dokumen->file_dokumen;
    $nomor = $dokumen->no_dokumen;

    Storage::disk('local')->delete($jalur);

    $this->artisan('dokumen:terbitkan', ['--laporan' => $laporan->id_laporan])
        ->assertSuccessful();

    $dokumen->refresh();

    expect($dokumen->no_dokumen)->toBe($nomor)
        ->and($dokumen->file_dokumen)->not->toBeNull()
        ->and(Storage::disk('local')->exists($dokumen->file_dokumen))->toBeTrue();
});

it('mengarahkan unduh pejabat ke pesan penyiapan saat berkas hilang', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon);

    $dokumen->update([
        'no_dokumen' => 'SKD-2026-000777',
        'file_dokumen' => 'dokumen/hilang.pdf',
    ]);

    $this->actingAs($pejabat)
        ->get(route('tanda_tangan.unduh', $laporan))
        ->assertRedirect()
        ->assertSessionHas('galat', 'Dokumen resmi sedang disiapkan.');
});

it('membatasi laju pembuatan ulang dokumen', function () {
    [$pemohon, , $pejabat] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon);

    $dokumen->update([
        'no_dokumen' => 'SKD-2026-000888',
        'file_dokumen' => 'dokumen/sudah-ada.pdf',
    ]);
    Storage::disk('local')->put('dokumen/sudah-ada.pdf', '%PDF-1.4 isi');

    $this->actingAs($pejabat);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('tanda_tangan.terbitkan', $laporan))->assertRedirect();
    }

    $this->post(route('tanda_tangan.terbitkan', $laporan))->assertStatus(429);
});

it('meng-escape HTML pada data dan menandai TTE mock di PDF', function () {
    [$pemohon] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon, [
        'nama_almarhum' => '<script>alert("x")</script> Budi',
        'tempat_kematian' => '<b>RS</b>',
    ]);

    config(['satuduka.tte_driver' => 'mock']);

    $html = view('dokumen.akta_pdf', dataTampilanAkta($laporan, $dokumen))->render();

    expect($html)->not->toContain('<script>alert')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->not->toContain('<b>RS</b>')
        ->and($html)->toContain('&lt;b&gt;RS&lt;/b&gt;')
        ->and($html)->toContain('SIMULASI/MOCK');

    config(['satuduka.tte_driver' => 'bsre']);

    $htmlNonMock = view('dokumen.akta_pdf', dataTampilanAkta($laporan, $dokumen))->render();

    expect($htmlNonMock)->not->toContain('SIMULASI/MOCK');
});

it('menghasilkan PDF yang tidak memuat markup HTML mentah', function () {
    [$pemohon] = buatAkunDemo();
    [$laporan, $dokumen] = buatDokumenSelesai($pemohon, [
        'nama_almarhum' => '<script>alert(1)</script>',
    ]);

    $isi = (new PembuatPdfDompdf)->hasil('dokumen.akta_pdf', dataTampilanAkta($laporan, $dokumen));

    expect($isi)->toStartWith('%PDF-')
        ->and($isi)->not->toContain('<script>alert(1)</script>');
});
