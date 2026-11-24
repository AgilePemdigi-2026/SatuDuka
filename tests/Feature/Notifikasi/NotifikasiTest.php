<?php

/**
 * Pengujian pusat notifikasi: kirim otomatis pada perubahan status,
 * tab pembacaan, penandaan dibaca, dan isolasi antar pengguna.
 */

use App\Models\AnomaliDokumen;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Notifikasi;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Storage;

/**
 * Pengajuan lengkap yang lolos kelengkapan sesuai konfigurasi.
 */
function buatPengajuanUntukNotifikasi(Pengguna $pemohon, array $timpa = []): LaporanKematian
{
    $laporan = LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => now()->subDays(2)->toDateString(),
    ], $timpa));

    $buktiKematian = config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0];

    foreach (array_merge([$buktiKematian], config('verifikasi.berkas_wajib.lainnya')) as $jenis) {
        $path = 'berkas-notifikasi/'.$laporan->id_laporan.'/'.md5($jenis).'.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\nBerkas pengujian notifikasi.\n");

        BerkasPendukung::factory()->create([
            'id_laporan' => $laporan->id_laporan,
            'jenis_dokumen' => $jenis,
            'path_file' => $path,
        ]);
    }

    return $laporan;
}

it('mengirim notifikasi perlu diperbaiki saat verifikator minta revisi', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatPengajuanUntukNotifikasi($pemohon);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.revisi', $laporan), [
            'catatan' => 'Kartu Keluarga kurang jelas.',
            'alasan_override' => 'Verifikator menemukan dokumen asli bermasalah.',
        ])
        ->assertRedirect(route('verifikasi.index'));

    $notifikasi = Notifikasi::query()->where('id_pengguna', $pemohon->id_pengguna)->firstOrFail();

    expect($notifikasi->jenis)->toBe(Notifikasi::JENIS_PERLU_DIPERBAIKI)
        ->and($notifikasi->judul)->toBe('Pengajuan Ditolak/Butuh Revisi')
        ->and($notifikasi->pesan)->toBe('Kartu Keluarga kurang jelas.')
        ->and($notifikasi->id_laporan)->toBe($laporan->id_laporan)
        ->and($notifikasi->sudah_dibaca)->toBeFalse();
});

it('mengirim notifikasi anomali info saat berkas ditandai anomali', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatPengajuanUntukNotifikasi($pemohon);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.anomali', $laporan), [
            'jenis_anomali' => AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
            'deskripsi' => 'NIK pada dokumen berbeda dengan data SIAK.',
            'alasan_override' => 'Verifikator menilai perlu rekonsiliasi.',
        ])
        ->assertRedirect(route('verifikasi.index'));

    $notifikasi = Notifikasi::query()->where('id_pengguna', $pemohon->id_pengguna)->firstOrFail();

    expect($notifikasi->jenis)->toBe(Notifikasi::JENIS_INFO)
        ->and($notifikasi->judul)->toBe('Berkas Ditandai Anomali');
});

it('mengirim notifikasi dokumen terbit saat pejabat menandatangani dengan pin', function () {
    Storage::fake('local');

    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();
    $laporan = buatPengajuanUntukNotifikasi($pemohon);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.setujui', $laporan), [
            'catatan' => 'Data dan dokumen sesuai.',
        ])
        ->assertRedirect(route('verifikasi.index'));

    Notifikasi::query()->update(['sudah_dibaca' => true]);

    $this->actingAs($pejabat)
        ->post(route('tanda_tangan.tandatangani', $laporan), [
            'pin' => '123456',
            'tanda_tangan' => pngTandaTangan(),
        ])
        ->assertRedirect(route('tanda_tangan.index'));

    $terbit = Notifikasi::query()
        ->where('id_pengguna', $pemohon->id_pengguna)
        ->where('jenis', Notifikasi::JENIS_DOKUMEN_TERBIT)
        ->firstOrFail();

    expect($terbit->judul)->toBe('Dokumen Resmi Terbit')
        ->and($terbit->pesan)->toContain('Suharto Wiryo');

    $laporan->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_SELESAI);
});

it('menampilkan pusat notifikasi dengan tab dan hanya milik sendiri', function () {
    [$pemohon, $pemohonLain] = buatAkunDemo();

    Notifikasi::factory()->count(2)->create(['id_pengguna' => $pemohon->id_pengguna]);
    Notifikasi::factory()->create([
        'id_pengguna' => $pemohonLain->id_pengguna,
        'judul' => 'Notifikasi milik orang lain',
    ]);

    $this->actingAs($pemohon)
        ->get(route('notifikasi.index'))
        ->assertOk()
        ->assertSee('Pusat Notifikasi');

    expect(Notifikasi::query()->where('id_pengguna', $pemohon->id_pengguna)->count())->toBe(2)
        ->and(Notifikasi::query()->where('id_pengguna', $pemohonLain->id_pengguna)->count())->toBe(1);
});

it('menolak akses pusat notifikasi untuk tamu', function () {
    $this->get(route('notifikasi.index'))->assertRedirect(route('masuk'));
});

it('menandai satu notifikasi sebagai dibaca oleh pemiliknya', function () {
    [$pemohon, $pemohonLain] = buatAkunDemo();
    $milik = Notifikasi::factory()->create(['id_pengguna' => $pemohon->id_pengguna]);
    $orangLain = Notifikasi::factory()->create(['id_pengguna' => $pemohonLain->id_pengguna]);

    $this->actingAs($pemohon)
        ->post(route('notifikasi.tandai', $milik))
        ->assertRedirect();

    expect($milik->fresh()->sudah_dibaca)->toBeTrue()
        ->and($orangLain->fresh()->sudah_dibaca)->toBeFalse();

    // Notifikasi milik pengguna lain ditolak (404) dan tidak berubah.
    $this->actingAs($pemohon)
        ->post(route('notifikasi.tandai', $orangLain))
        ->assertNotFound();

    expect($orangLain->fresh()->sudah_dibaca)->toBeFalse();
});

it('menandai seluruh notifikasi sebagai dibaca sekaligus', function () {
    [$pemohon] = buatAkunDemo();

    Notifikasi::factory()->count(3)->create(['id_pengguna' => $pemohon->id_pengguna]);

    $this->actingAs($pemohon)
        ->post(route('notifikasi.tandai_semua'))
        ->assertRedirect();

    expect(Notifikasi::query()->where('id_pengguna', $pemohon->id_pengguna)->where('sudah_dibaca', false)->count())->toBe(0);
});

it('memfilter notifikasi berdasarkan tab perlu tindakan dan selesai', function () {
    [$pemohon] = buatAkunDemo();

    $perlu = Notifikasi::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'jenis' => Notifikasi::JENIS_PERLU_DIPERBAIKI,
        'judul' => 'Notifikasi Perlu Diperbaiki',
    ]);
    $terbit = Notifikasi::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'jenis' => Notifikasi::JENIS_DOKUMEN_TERBIT,
        'judul' => 'Notifikasi Dokumen Terbit',
    ]);
    Notifikasi::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'jenis' => Notifikasi::JENIS_INFO,
        'judul' => 'Notifikasi Informasi',
    ]);

    $this->actingAs($pemohon)
        ->get(route('notifikasi.index', ['tab' => 'perlu_tindakan']))
        ->assertOk()
        ->assertSee('Notifikasi Perlu Diperbaiki')
        ->assertDontSee('Notifikasi Dokumen Terbit');

    $this->actingAs($pemohon)
        ->get(route('notifikasi.index', ['tab' => 'selesai']))
        ->assertOk()
        ->assertSee('Notifikasi Dokumen Terbit')
        ->assertDontSee('Notifikasi Perlu Diperbaiki');
});
