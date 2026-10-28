<?php

use App\Models\AnomaliDokumen;
use App\Models\DokumenResmi;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Pengajuan terkirim milik seorang pemohon.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatPengajuanTerkirim(Pengguna $pemohon, array $timpa = []): LaporanKematian
{
    return LaporanKematian::factory()->terkirim()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'nama_almarhum' => 'Budi Santoso',
    ], $timpa));
}

it('menampilkan daftar pengajuan milik pemohon tanpa draf', function () {
    [$pemohon] = buatAkunDemo();

    buatPengajuanTerkirim($pemohon, [
        'no_pengajuan' => 'SD-20260910-0001',
        'tanggal_pengajuan' => Carbon::parse('2026-09-10 09:00'),
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'nama_almarhum' => 'Suparno Wijaya',
    ]);

    buatPengajuanTerkirim($pemohon, [
        'no_pengajuan' => 'SD-20261001-0002',
        'tanggal_pengajuan' => Carbon::parse('2026-10-01 10:00'),
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
    ]);

    LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'no_pengajuan' => null,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'nama_almarhum' => 'Draf Tersembunyi',
    ]);

    $this->actingAs($pemohon)->get(route('pengajuan.status'))
        ->assertOk()
        ->assertSee('Lacak Status Pengajuan')
        ->assertSeeInOrder(['SD-20261001-0002', 'SD-20260910-0001'])
        ->assertSee('Budi Santoso')
        ->assertSee('Suparno Wijaya')
        ->assertSee(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->assertSee(LaporanKematian::STATUS_SELESAI)
        ->assertDontSee('Draf Tersembunyi');
});

it('menampilkan pesan saat pemohon belum memiliki pengajuan', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)->get(route('pengajuan.status'))
        ->assertOk()
        ->assertSee('Belum ada pengajuan yang diajukan');
});

it('mencari pengajuan berdasarkan nomor dan menampilkan detailnya', function () {
    [$pemohon] = buatAkunDemo();

    $laporan = buatPengajuanTerkirim($pemohon, [
        'no_pengajuan' => 'SD-20260910-0001',
        'tanggal_pengajuan' => Carbon::parse('2026-09-10 09:00'),
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
    ]);

    $this->actingAs($pemohon)
        ->get(route('pengajuan.status', ['nomor' => 'SD-20260910-0001']))
        ->assertRedirect(route('pengajuan.detail', $laporan));

    $this->get(route('pengajuan.detail', $laporan))
        ->assertOk()
        ->assertSee('SD-20260910-0001')
        ->assertSee('Budi Santoso')
        ->assertSee('10 September 2026, 09:00')
        ->assertSee(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->assertSee('Pengajuan Diterima')
        ->assertSee('Verifikasi Dokumen')
        ->assertSee('Peninjauan Verifikator')
        ->assertSee('Persetujuan dan Tanda Tangan Elektronik')
        ->assertSee('Dokumen Terbit');
});

it('meminta nomor pengajuan saat kolom pencarian kosong', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)->get(route('pengajuan.status', ['nomor' => '']))
        ->assertOk()
        ->assertSee('Harap isi nomor pengajuan')
        ->assertSee('border-red-400', false);
});

it('menampilkan data tidak ditemukan untuk nomor yang tidak terdaftar', function () {
    [$pemohon] = buatAkunDemo();
    buatPengajuanTerkirim($pemohon, ['no_pengajuan' => 'SD-20260910-0001']);

    $this->actingAs($pemohon)->get(route('pengajuan.status', ['nomor' => 'SD-2026-99999']))
        ->assertOk()
        ->assertSee('Data tidak ditemukan')
        ->assertSee('SD-20260910-0001');
});

it('tidak membocorkan pengajuan milik pemohon lain saat mencari', function () {
    [$pemohon] = buatAkunDemo();
    $pemohonLain = Pengguna::factory()->pemohon()->create();

    buatPengajuanTerkirim($pemohonLain, [
        'no_pengajuan' => 'SD-20260910-0009',
        'nama_almarhum' => 'Rahasia Orang Lain',
    ]);

    $this->actingAs($pemohon)->get(route('pengajuan.status', ['nomor' => 'SD-20260910-0009']))
        ->assertOk()
        ->assertSee('Data tidak ditemukan')
        ->assertDontSee('Rahasia Orang Lain');
});

it('menampilkan lini masa lima tahap beserta tanggal dan catatan', function () {
    Storage::fake('local');
    [$pemohon, $verifikator] = buatAkunDemo();

    $laporan = buatPengajuanTerkirim($pemohon, [
        'no_pengajuan' => 'SD-20261020-0001',
        'tanggal_pengajuan' => Carbon::parse('2026-10-01 09:00'),
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
    ]);

    $verifikasi = HasilVerifikasi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'id_pengguna' => $verifikator->id_pengguna,
        'tanggal_verifikasi' => Carbon::parse('2026-10-02 10:00'),
    ]);

    AnomaliDokumen::factory()->selesai()->create([
        'id_verifikasi' => $verifikasi->id_verifikasi,
        'tanggal_rekonsiliasi' => Carbon::parse('2026-10-03 11:00'),
    ]);

    DokumenResmi::factory()->selesai()->create([
        'id_laporan' => $laporan->id_laporan,
        'tanggal_terbit' => Carbon::parse('2026-10-05 12:00'),
        'file_dokumen' => 'dokumen/akta-kematian.pdf',
        'alasan_pengembalian' => 'Berkas sempat dikembalikan untuk perbaikan.',
    ]);

    $this->actingAs($pemohon)->get(route('pengajuan.detail', $laporan))
        ->assertOk()
        ->assertSee('1 Oktober 2026, 09:00')
        ->assertSee('2 Oktober 2026, 10:00')
        ->assertSee('3 Oktober 2026, 11:00')
        ->assertSee('5 Oktober 2026, 12:00')
        ->assertSee('Berkas sempat dikembalikan untuk perbaikan.')
        ->assertSee('Unduh Dokumen');
});

it('hanya mengizinkan unduh dokumen setelah pengajuan selesai', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();

    $belumSelesai = buatPengajuanTerkirim($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
    ]);

    $this->actingAs($pemohon)
        ->get(route('pengajuan.detail', $belumSelesai))
        ->assertOk()
        ->assertSee('Unduh Dokumen')
        ->assertSee('disabled', false);

    $this->get(route('pengajuan.unduh', $belumSelesai))->assertForbidden();

    $selesai = buatPengajuanTerkirim($pemohon, [
        'no_pengajuan' => 'SD-20261020-0002',
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
    ]);

    $this->get(route('pengajuan.unduh', $selesai))->assertForbidden();

    DokumenResmi::factory()->selesai()->create([
        'id_laporan' => $selesai->id_laporan,
        'file_dokumen' => 'dokumen/akta-kematian.pdf',
        'tanggal_terbit' => now(),
    ]);

    $this->get(route('pengajuan.unduh', $selesai))->assertNotFound();

    Storage::disk('local')->put('dokumen/akta-kematian.pdf', 'isi dokumen');

    $this->get(route('pengajuan.unduh', $selesai))
        ->assertOk()
        ->assertDownload('Akta_Kematian_SD-20261020-0002.pdf')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect(LogAudit::query()->where('aksi', 'mengunduh_dokumen')->count())->toBe(1);
});

it('menolak akses ke pengajuan milik pemohon lain', function () {
    [$pemohon] = buatAkunDemo();
    $pemohonLain = Pengguna::factory()->pemohon()->create();

    $laporan = buatPengajuanTerkirim($pemohonLain, [
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
    ]);

    $this->actingAs($pemohon)->get(route('pengajuan.detail', $laporan))->assertNotFound();
    $this->get(route('pengajuan.unduh', $laporan))->assertNotFound();
});

it('membatasi halaman status pengajuan untuk pemohon', function () {
    [, $verifikator] = buatAkunDemo();

    $this->actingAs($verifikator)->get(route('pengajuan.status'))->assertForbidden();
});

it('membatasi daftar pengajuan per halaman', function () {
    [$pemohon] = buatAkunDemo();

    foreach (range(1, 11) as $urutan) {
        buatPengajuanTerkirim($pemohon, [
            'no_pengajuan' => sprintf('SD-20261020-%04d', $urutan),
            'tanggal_pengajuan' => now()->subMinutes($urutan),
        ]);
    }

    $respons = $this->actingAs($pemohon)->get(route('pengajuan.status'))->assertOk();

    expect($respons->viewData('daftar')->count())->toBe(10);
    $respons->assertSee('page=2', false);
});

it('menampilkan draf kosong pada bagian perlu tindakan', function () {
    [$pemohon] = buatAkunDemo();

    LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'no_pengajuan' => null,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'nama_almarhum' => null,
        'dibuat_pada' => Carbon::parse('2026-10-21 08:00'),
    ]);

    $this->actingAs($pemohon)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Perlu Tindakan Anda')
        ->assertSee('Draf belum diisi')
        ->assertSee('Dibuat 21 Oktober 2026')
        ->assertSee('>Lanjutkan</a>', false);
});

it('menampilkan tombol Perbaiki untuk pengajuan yang membutuhkan revisi', function () {
    [$pemohon] = buatAkunDemo();

    $laporan = buatPengajuanTerkirim($pemohon, [
        'no_pengajuan' => 'SD-20261020-0011',
        'status_pengajuan' => LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
    ]);

    $this->actingAs($pemohon)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Perlu Tindakan Anda')
        ->assertSee('>Perbaiki</a>', false)
        ->assertSee(route('laporan.ubah', $laporan));
});

it('menampilkan tombol Lihat untuk pengajuan yang sudah selesai', function () {
    [$pemohon] = buatAkunDemo();

    $laporan = buatPengajuanTerkirim($pemohon, [
        'no_pengajuan' => 'SD-20261020-0012',
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
    ]);

    $this->actingAs($pemohon)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('>Lihat</a>', false)
        ->assertSee(route('pengajuan.detail', $laporan))
        ->assertDontSee('Perlu Tindakan Anda');
});

it('membatasi pengajuan saya menjadi lima baris terbaru', function () {
    [$pemohon] = buatAkunDemo();

    foreach (range(1, 7) as $urutan) {
        buatPengajuanTerkirim($pemohon, [
            'no_pengajuan' => sprintf('SD-20261020-%04d', $urutan),
            'nama_almarhum' => 'Almarhum '.$urutan,
            'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            'dibuat_pada' => now()->subMinutes(10 - $urutan),
        ]);
    }

    $respons = $this->actingAs($pemohon)->get(route('dashboard'))->assertOk();

    expect($respons->viewData('pengajuanSaya'))->toHaveCount(5);

    $respons->assertSee('Almarhum 7')
        ->assertSee('Almarhum 3')
        ->assertDontSee('Almarhum 2')
        ->assertDontSee('Almarhum 1')
        ->assertSee('Lihat semua');
});

it('menampilkan ringkasan status pengajuan di dashboard pemohon', function () {
    [$pemohon] = buatAkunDemo();

    buatPengajuanTerkirim($pemohon, ['status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI]);
    buatPengajuanTerkirim($pemohon, ['status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI]);
    buatPengajuanTerkirim($pemohon, ['status_pengajuan' => LaporanKematian::STATUS_SELESAI]);

    $this->actingAs($pemohon)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Status Pengajuan')
        ->assertSee(route('pengajuan.status'))
        ->assertSee('>2</span>', false)
        ->assertSee('>1</span>', false)
        ->assertSee(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});
