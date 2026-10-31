<?php

/**
 * Pengujian alur rekonsiliasi anomali (SRS 5.2.7 dan 6.2.7).
 *
 * Seluruh pengujian memakai SQLite in-memory dan tidak memanggil layanan
 * eksternal. Data SIAK berasal dari berkas dummy.
 */

use App\Models\AnalisisAi;
use App\Models\AnomaliDokumen;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Support\MaskerNik;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat pengajuan berstatus Dalam Rekonsiliasi Anomali beserta anomali terbuka.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatKasusRekonsiliasi(Pengguna $pemohon, Pengguna $verifikator, array $timpa = []): LaporanKematian
{
    $laporan = LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DALAM_REKONSILIASI,
        'tanggal_pengajuan' => now(),
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
    ], $timpa));

    $hasil = HasilVerifikasi::create([
        'id_laporan' => $laporan->id_laporan,
        'id_pengguna' => $verifikator->id_pengguna,
        'status_verifikasi' => HasilVerifikasi::STATUS_ANOMALI,
        'catatan' => 'NIK pada dokumen berbeda dengan data SIAK.',
        'tanggal_verifikasi' => now(),
    ]);

    AnomaliDokumen::create([
        'id_verifikasi' => $hasil->id_verifikasi,
        'jenis_anomali' => AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
        'deskripsi' => 'Nama pada dokumen berbeda dengan data SIAK.',
        'status_rekonsiliasi' => AnomaliDokumen::STATUS_TERBUKA,
    ]);

    return $laporan;
}

it('membatasi seluruh rute rekonsiliasi hanya untuk verifikator', function () {
    Storage::fake('local');

    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();
    $laporan = buatKasusRekonsiliasi($pemohon, $verifikator);

    $rute = [
        ['get', route('rekonsiliasi.index')],
        ['get', route('rekonsiliasi.show', $laporan)],
        ['post', route('rekonsiliasi.selesaikan', $laporan)],
        ['post', route('rekonsiliasi.tolak_fatal', $laporan)],
    ];

    foreach ($rute as [$metode, $url]) {
        $this->{$metode}($url)->assertRedirect(route('masuk'));
    }

    foreach ([$pemohon, $pejabat] as $pengguna) {
        foreach ($rute as [$metode, $url]) {
            $this->actingAs($pengguna)->{$metode}($url)->assertForbidden();
        }
    }

    $this->actingAs($verifikator)
        ->get(route('rekonsiliasi.index'))
        ->assertOk()
        ->assertSee(MaskerNik::masker($laporan->nik_almarhum))
        ->assertDontSee($laporan->nik_almarhum)
        ->assertSee($laporan->nama_almarhum);

    $this->get(route('rekonsiliasi.show', $laporan))->assertOk();
});

it('menampilkan komparasi dengan NIK termasker dan menandai layanan tidak tersedia', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatKasusRekonsiliasi($pemohon, $verifikator, [
        'nik_almarhum' => '3471019999990001',
    ]);

    AnalisisAi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
        'status' => AnalisisAi::STATUS_GAGAL,
        'hasil' => ['pesan' => 'Layanan AI tidak tersedia.'],
    ]);

    $this->actingAs($verifikator)
        ->get(route('rekonsiliasi.show', $laporan))
        ->assertOk()
        ->assertSee('Layanan eksternal tidak tersedia')
        ->assertSee('Tidak tersedia')
        ->assertSee(MaskerNik::masker($laporan->nik_almarhum))
        ->assertDontSee($laporan->nik_almarhum);
});

it('menyelesaikan rekonsiliasi dengan koreksi dan audit NIK termasker', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatKasusRekonsiliasi($pemohon, $verifikator);
    $nikLama = (string) $laporan->nik_almarhum;
    $nikBaru = '3471036407609002';

    $this->actingAs($verifikator)
        ->post(route('rekonsiliasi.selesaikan', $laporan), [
            'nama_almarhum' => 'Siti Aminah',
            'nik_almarhum' => $nikBaru,
            'tanggal_kematian' => '2026-10-01',
            'catatan_rekonsiliasi' => 'Data acuan diambil dari KTP dan data SIAK terbaru.',
        ])
        ->assertRedirect(route('rekonsiliasi.index'))
        ->assertSessionHas('sukses');

    $laporan->refresh();

    expect($laporan->nama_almarhum)->toBe('Siti Aminah')
        ->and($laporan->nik_almarhum)->toBe($nikBaru)
        ->and($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and($laporan->catatan_revisi)->toBeNull();

    $anomali = AnomaliDokumen::query()->firstOrFail();

    expect($anomali->status_rekonsiliasi)->toBe(AnomaliDokumen::STATUS_SELESAI)
        ->and($anomali->tanggal_rekonsiliasi)->not->toBeNull();

    $hasil = HasilVerifikasi::query()
        ->where('status_verifikasi', HasilVerifikasi::STATUS_REKONSILIASI)
        ->firstOrFail();

    expect($hasil->catatan)->toBe('Data acuan diambil dari KTP dan data SIAK terbaru.')
        ->and($hasil->id_pengguna)->toBe($verifikator->id_pengguna);

    $audit = LogAudit::query()->where('aksi', 'menyelesaikan_rekonsiliasi')->firstOrFail();

    expect($audit->detail['perubahan']['sebelum']['nik_almarhum'])->toBe(MaskerNik::masker($nikLama))
        ->and($audit->detail['perubahan']['sesudah']['nik_almarhum'])->toBe(MaskerNik::masker($nikBaru));

    $teksAudit = json_encode($audit->detail, JSON_UNESCAPED_SLASHES);

    expect($teksAudit)->not->toContain($nikLama)
        ->and($teksAudit)->not->toContain($nikBaru);
});

it('menolak koreksi NIK yang tidak valid dengan pesan Indonesia', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatKasusRekonsiliasi($pemohon, $verifikator);

    $this->from(route('rekonsiliasi.show', $laporan))
        ->actingAs($verifikator)
        ->post(route('rekonsiliasi.selesaikan', $laporan), [
            'nama_almarhum' => 'Suharto Wiryo',
            'nik_almarhum' => '1234567890123456',
            'tanggal_kematian' => '2026-10-01',
            'catatan_rekonsiliasi' => 'Mencoba mengganti NIK.',
        ])
        ->assertRedirect(route('rekonsiliasi.show', $laporan))
        ->assertSessionHasErrors('nik_almarhum');

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DALAM_REKONSILIASI)
        ->and($laporan->fresh()->nik_almarhum)->toBe('3471031203559001')
        ->and(HasilVerifikasi::query()
            ->where('status_verifikasi', HasilVerifikasi::STATUS_REKONSILIASI)
            ->count())->toBe(0);
});

it('menolak tanggal kematian di masa depan dengan pesan Indonesia', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatKasusRekonsiliasi($pemohon, $verifikator);

    $this->from(route('rekonsiliasi.show', $laporan))
        ->actingAs($verifikator)
        ->post(route('rekonsiliasi.selesaikan', $laporan), [
            'nama_almarhum' => 'Suharto Wiryo',
            'nik_almarhum' => '',
            'tanggal_kematian' => now()->addDay()->toDateString(),
            'catatan_rekonsiliasi' => 'Mencoba mengubah tanggal kematian.',
        ])
        ->assertRedirect(route('rekonsiliasi.show', $laporan))
        ->assertSessionHasErrors('tanggal_kematian');

    expect($laporan->fresh()->tanggal_kematian?->toDateString())->toBe('2026-10-01')
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DALAM_REKONSILIASI);
});

it('menolak permanen dengan alasan eskalasi internal dan catatan resmi publik', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatKasusRekonsiliasi($pemohon, $verifikator);

    $alasanEskalasi = 'Identitas fiktif; hasil penelusuran SIAK tidak dapat dipertanggungjawabkan.';
    $catatanResmi = 'Pengajuan ditolak permanen karena data identitas tidak dapat diverifikasi.';

    $this->actingAs($verifikator)
        ->post(route('rekonsiliasi.tolak_fatal', $laporan), [
            'alasan_eskalasi' => $alasanEskalasi,
            'catatan_resmi' => $catatanResmi,
        ])
        ->assertRedirect(route('rekonsiliasi.index'))
        ->assertSessionHas('sukses');

    $laporan->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_DITOLAK_ANOMALI_FATAL)
        ->and($laporan->catatan_revisi)->toBe($catatanResmi);

    expect(AnomaliDokumen::query()->firstOrFail()->status_rekonsiliasi)
        ->toBe(AnomaliDokumen::STATUS_DITOLAK_PERMANEN);

    $hasil = HasilVerifikasi::query()->where('status_verifikasi', HasilVerifikasi::STATUS_DITOLAK)->firstOrFail();

    expect($hasil->alasan_override)->toBe($alasanEskalasi)
        ->and($hasil->catatan)->toBe($catatanResmi);

    $audit = LogAudit::query()->where('aksi', 'menolak_anomali_fatal_rekonsiliasi')->firstOrFail();

    expect($audit->detail['alasan_eskalasi'])->toBe($alasanEskalasi);

    $this->actingAs($pemohon)
        ->get(route('pengajuan.detail', $laporan))
        ->assertOk()
        ->assertSee('Catatan Resmi Verifikator')
        ->assertSee($catatanResmi)
        ->assertDontSee($alasanEskalasi);
});

it('mengembalikan berkas ke pemohon lewat rute perbaikan tanpa menutup anomali', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatKasusRekonsiliasi($pemohon, $verifikator);

    $this->actingAs($verifikator)
        ->get(route('rekonsiliasi.show', $laporan))
        ->assertOk()
        ->assertSee('Kembalikan ke Pemohon')
        ->assertSee(route('verifikasi.perbaikan', $laporan));

    $this->post(route('verifikasi.perbaikan', $laporan), [
        'catatan' => 'Dokumen faskes tidak terbaca, mohon unggah ulang dokumen yang valid.',
        'alasan_override' => 'Data acuan belum dapat dipastikan dari dokumen yang ada.',
    ])
        ->assertRedirect(route('verifikasi.index'));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_PERBAIKAN)
        ->and(AnomaliDokumen::query()->firstOrFail()->status_rekonsiliasi)
        ->toBe(AnomaliDokumen::STATUS_TERBUKA);

    expect(LogAudit::query()->where('aksi', 'meminta_perbaikan_berkas')->count())->toBe(1);
});

it('menolak detail dan aksi untuk status selain rekonsiliasi', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
    ]);

    $this->actingAs($verifikator)
        ->get(route('rekonsiliasi.show', $laporan))
        ->assertNotFound();

    $this->actingAs($verifikator)
        ->post(route('rekonsiliasi.selesaikan', $laporan), [
            'nama_almarhum' => 'Nama Uji',
            'nik_almarhum' => '',
            'tanggal_kematian' => '2026-10-01',
            'catatan_rekonsiliasi' => 'Mencoba menyelesaikan rekonsiliasi.',
        ])
        ->assertStatus(409);

    $this->post(route('rekonsiliasi.tolak_fatal', $laporan), [
        'alasan_eskalasi' => 'Alasan eskalasi pengujian.',
        'catatan_resmi' => 'Catatan resmi pengujian.',
    ])->assertStatus(409);

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});
