<?php

/**
 * Pengujian alur verifikasi berkas dengan persetujuan, revisi, dan anomali.
 *
 * Seluruh pengujian memakai SQLite in-memory dari konfigurasi PHPUnit dan
 * Storage::fake('local') untuk berkas unggahan.
 */

use App\Models\AnalisisAi;
use App\Models\AnomaliDokumen;
use App\Models\BerkasPendukung;
use App\Models\DokumenResmi;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Support\MaskerNik;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mengaktifkan driver penyedia AI nyata untuk pengujian kegagalan.
 */
function aktifkanDriverOpenAiVerifikasiBerkas(): void
{
    config([
        'ai.driver' => 'openai',
        'ai.openai.api_key' => 'kunci-uji',
        'ai.openai.base_url' => 'https://api.openai.test/v1',
        'ai.openai.model' => 'model-uji',
        'ai.timeout' => 5,
    ]);
}

/**
 * Membuat laporan yang berada dalam antrean verifikasi dengan data SIAK valid.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanAntreanVerifikasi(Pengguna $pemohon, array $timpa = []): LaporanKematian
{
    return LaporanKematian::factory()->create(array_merge([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
        'nama_pelapor' => 'Pemohon Demo',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
        'sebab_kematian' => 'Sakit',
    ], $timpa));
}

/**
 * Melampirkan satu berkas berhasil unggah pada laporan.
 */
function lampirkanBerkasVerifikasiBerkas(LaporanKematian $laporan, string $jenis): BerkasPendukung
{
    $namaFile = str_replace(' ', '-', mb_strtolower($jenis)).'.pdf';
    $path = 'berkas-verifikasi/'.$laporan->id_laporan.'/'.md5($jenis).'.pdf';

    Storage::disk('local')->put($path, "%PDF-1.4\nBerkas pengujian verifikasi.\n");

    return BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => $jenis,
        'nama_file' => $namaFile,
        'path_file' => $path,
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
        'tanggal_unggah' => now(),
    ]);
}

/**
 * Melengkapi berkas wajib minimal sesuai konfigurasi, tanpa saksi.
 */
function lengkapiBerkasVerifikasiBerkas(LaporanKematian $laporan): void
{
    $buktiKematian = config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0];

    foreach (array_merge([$buktiKematian], config('verifikasi.berkas_wajib.lainnya')) as $jenis) {
        lampirkanBerkasVerifikasiBerkas($laporan, $jenis);
    }
}

/**
 * Mencatat hasil perbandingan AI agar panel dapat ditampilkan.
 */
function catatPerbandinganAiVerifikasiBerkas(LaporanKematian $laporan): AnalisisAi
{
    return AnalisisAi::create([
        'id_laporan' => $laporan->id_laporan,
        'id_berkas' => null,
        'jenis' => AnalisisAi::JENIS_PERBANDINGAN,
        'hasil' => [
            'skor' => 0.95,
            'ada_beda' => false,
            'kolom' => [
                [
                    'label' => 'Nama Almarhum',
                    'nilai_pemohon' => 'Suharto Wiryo',
                    'nilai_ekstraksi' => 'Suharto Wiryo',
                    'nilai_siak' => 'Suharto Wiryo',
                    'status' => 'cocok',
                    'keyakinan' => 0.95,
                    'catatan' => [],
                ],
            ],
        ],
        'skor' => 0.95,
        'model' => 'mock-ai-v1',
        'status' => AnalisisAi::STATUS_BERHASIL,
        'hash_berkas' => hash('sha256', 'perbandingan-verifikasi'),
        'dibuat_pada' => now(),
    ]);
}

it('membatasi seluruh rute verifikasi berkas hanya untuk verifikator', function () {
    Storage::fake('local');

    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasiBerkas($laporan, BerkasPendukung::JENIS_KTP_PELAPOR);

    $rute = [
        ['get', route('verifikasi.index')],
        ['get', route('verifikasi.show', $laporan)],
        ['get', route('verifikasi.pratinjau', [$laporan, $berkas])],
        ['get', route('verifikasi.unduh', [$laporan, $berkas])],
        ['post', route('verifikasi.setujui', $laporan)],
        ['post', route('verifikasi.perbaikan', $laporan)],
        ['post', route('verifikasi.revisi', $laporan)],
        ['post', route('verifikasi.anomali', $laporan)],
        ['post', route('verifikasi.tolak_fatal', $laporan)],
        ['post', route('verifikasi.analisis_ai', $laporan)],
        ['post', route('verifikasi.ringkasan_ai', $laporan)],
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
        ->get(route('verifikasi.index'))
        ->assertOk()
        ->assertSee(MaskerNik::masker($laporan->nik_almarhum))
        ->assertDontSee($laporan->nik_almarhum)
        ->assertSee($laporan->nama_almarhum);

    $this->get(route('verifikasi.show', $laporan))->assertOk();
    $this->get(route('verifikasi.pratinjau', [$laporan, $berkas]))->assertOk();
    $this->get(route('verifikasi.unduh', [$laporan, $berkas]))->assertOk();
});

it('menyetujui berkas dan menyimpan hasil verifikasi', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    lengkapiBerkasVerifikasiBerkas($laporan);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.setujui', $laporan), [
            'catatan' => 'Data dan dokumen sesuai.',
        ])
        ->assertRedirect(route('verifikasi.index'))
        ->assertSessionHas('sukses');

    $laporan->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE)
        ->and($laporan->catatan_revisi)->toBeNull();

    $hasil = HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->firstOrFail();

    expect($hasil->id_pengguna)->toBe($verifikator->id_pengguna)
        ->and($hasil->status_verifikasi)->toBe(HasilVerifikasi::STATUS_DITERIMA)
        ->and($hasil->catatan)->toBe('Data dan dokumen sesuai.')
        ->and($hasil->deteksi_otomatis)->toBeArray()
        ->and($hasil->deteksi_otomatis['rekomendasi_utama'])->toBe('Siap disetujui');

    expect(DokumenResmi::where('id_laporan', $laporan->id_laporan)
        ->value('status_ttd'))->toBe(DokumenResmi::STATUS_MENUNGGU);

    expect(LogAudit::where('aksi', 'menyetujui_berkas_pengajuan')->count())->toBe(1);
});

it('menghitung kelengkapan sesuai aturan wajib konfigurasi', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    lengkapiBerkasVerifikasiBerkas($laporan);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk()
        ->assertSee('4 / 4 lengkap')
        ->assertDontSee('4 / 9 lengkap')
        ->assertSee(BerkasPendukung::JENIS_KTP_SAKSI_1)
        ->assertSee('Opsional');
});

it('mewajibkan alasan override saat menyetujui berkas yang kurang', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);

    $wajib = array_merge(
        [config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0]],
        config('verifikasi.berkas_wajib.lainnya'),
    );
    $kurang = array_pop($wajib);

    foreach ($wajib as $jenis) {
        lampirkanBerkasVerifikasiBerkas($laporan, $jenis);
    }

    $this->from(route('verifikasi.show', $laporan))
        ->actingAs($verifikator)
        ->post(route('verifikasi.setujui', $laporan), ['catatan' => 'Coba setujui'])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHasErrors('alasan_override');

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and(HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->count())->toBe(0)
        ->and($kurang)->toBe(BerkasPendukung::JENIS_KTP_PELAPOR);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.setujui', $laporan), [
            'catatan' => 'Berkas kurang, tetapi verifikator memilih menyetujui.',
            'alasan_override' => 'Sudah dikonfirmasi langsung ke pemohon.',
        ])
        ->assertRedirect(route('verifikasi.index'));

    $hasil = HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->firstOrFail();

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE)
        ->and($hasil->alasan_override)->toBe('Sudah dikonfirmasi langsung ke pemohon.')
        ->and($hasil->deteksi_otomatis['anomali'])->not->toBeEmpty();
});

it('meminta revisi dengan catatan dan menyimpan hasil verifikasi', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk()
        ->assertSee('Tolak / Butuh Revisi')
        ->assertSee(route('verifikasi.revisi', $laporan));

    $this->post(route('verifikasi.revisi', $laporan), [
        'catatan' => 'Kartu Keluarga kurang jelas, mohon unggah ulang.',
        'alasan_override' => 'Verifikator memilih jalur revisi langsung.',
    ])
        ->assertRedirect(route('verifikasi.index'))
        ->assertSessionHas('sukses');

    $laporan->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI)
        ->and($laporan->catatan_revisi)->toBe('Kartu Keluarga kurang jelas, mohon unggah ulang.');

    $hasil = HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->firstOrFail();

    expect($hasil->id_pengguna)->toBe($verifikator->id_pengguna)
        ->and($hasil->status_verifikasi)->toBe(HasilVerifikasi::STATUS_PERLU_REVISI)
        ->and($hasil->catatan)->toBe('Kartu Keluarga kurang jelas, mohon unggah ulang.');

    expect(LogAudit::where('aksi', 'meminta_revisi_berkas')->count())->toBe(1);
});

it('menandai anomali dan menyimpan hasil verifikasi', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.anomali', $laporan), [
            'jenis_anomali' => AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
            'deskripsi' => 'NIK pada dokumen berbeda dengan data SIAK.',
            'alasan_override' => 'Verifikator menilai perlu rekonsiliasi.',
        ])
        ->assertRedirect(route('verifikasi.index'))
        ->assertSessionHas('sukses');

    $laporan->refresh();

    expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_DALAM_REKONSILIASI);

    $hasil = HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->firstOrFail();

    expect($hasil->id_pengguna)->toBe($verifikator->id_pengguna)
        ->and($hasil->status_verifikasi)->toBe(HasilVerifikasi::STATUS_ANOMALI)
        ->and($hasil->catatan)->toBe('NIK pada dokumen berbeda dengan data SIAK.');

    $anomali = AnomaliDokumen::where('id_verifikasi', $hasil->id_verifikasi)->firstOrFail();

    expect($anomali->jenis_anomali)->toBe(AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS)
        ->and($anomali->deskripsi)->toBe('NIK pada dokumen berbeda dengan data SIAK.')
        ->and($anomali->status_rekonsiliasi)->toBe(AnomaliDokumen::STATUS_TERBUKA);

    expect(LogAudit::where('aksi', 'menandai_anomali_berkas')->count())->toBe(1);
});

it('menerima kelima jenis anomali dari halaman baru', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $jenisAnomali = [
        AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
        AnomaliDokumen::JENIS_NIK_GANDA,
        AnomaliDokumen::JENIS_TANGGAL_KEMATIAN,
        AnomaliDokumen::JENIS_TEMPAT_KEMATIAN,
        AnomaliDokumen::JENIS_DOKUMEN_TIDAK_TERBACA,
    ];

    $this->actingAs($verifikator);

    foreach ($jenisAnomali as $jenis) {
        $laporan = buatLaporanAntreanVerifikasi($pemohon);

        $this->get(route('verifikasi.show', $laporan))
            ->assertOk()
            ->assertSee($jenis);

        $this->post(route('verifikasi.anomali', $laporan), [
            'jenis_anomali' => $jenis,
            'deskripsi' => 'Anomali '.$jenis.' ditemukan.',
            'alasan_override' => 'Verifikator menilai perlu rekonsiliasi.',
        ])->assertRedirect(route('verifikasi.index'));

        $hasil = HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->firstOrFail();

        expect(AnomaliDokumen::where('id_verifikasi', $hasil->id_verifikasi)
            ->value('jenis_anomali'))->toBe($jenis);
    }
});

it('menolak laporan di luar status yang boleh diverifikasi', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertNotFound();

    $this->post(route('verifikasi.setujui', $laporan), ['catatan' => 'Coba setujui'])
        ->assertStatus(409);

    $this->post(route('verifikasi.revisi', $laporan), ['catatan' => 'Mohon perbaiki berkas'])
        ->assertStatus(409);

    $this->post(route('verifikasi.anomali', $laporan), [
        'jenis_anomali' => AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
        'deskripsi' => 'Ada perbedaan identitas.',
    ])->assertStatus(409);

    expect(HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->count())->toBe(0);
});

it('menolak pratinjau dan unduh berkas dari laporan lain', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporanA = buatLaporanAntreanVerifikasi($pemohon);
    $laporanB = buatLaporanAntreanVerifikasi($pemohon);
    $berkasB = lampirkanBerkasVerifikasiBerkas($laporanB, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);

    $this->actingAs($verifikator);

    $this->get(route('verifikasi.pratinjau', [$laporanA, $berkasB]))->assertNotFound();
    $this->get(route('verifikasi.unduh', [$laporanA, $berkasB]))->assertNotFound();

    $this->get(route('verifikasi.pratinjau', [$laporanB, $berkasB]))->assertOk();
    $this->get(route('verifikasi.unduh', [$laporanB, $berkasB]))->assertOk();
});

it('tidak dapat menjalankan aksi verifikasi dua kali pada laporan yang sama', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();

    $laporanSetujui = buatLaporanAntreanVerifikasi($pemohon);
    lengkapiBerkasVerifikasiBerkas($laporanSetujui);

    $laporanRevisi = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Siti Aminah',
        'nik_almarhum' => '3471036407609002',
        'jenis_kelamin' => 'P',
    ]);
    $laporanAnomali = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Karsih',
        'nik_almarhum' => '3471144306539010',
        'jenis_kelamin' => 'P',
    ]);

    $this->actingAs($verifikator);

    $this->post(route('verifikasi.setujui', $laporanSetujui), [
        'catatan' => 'Disetujui.',
    ])->assertRedirect(route('verifikasi.index'));

    $this->post(route('verifikasi.setujui', $laporanSetujui), [
        'catatan' => 'Disetujui lagi.',
    ])->assertStatus(409);

    $this->post(route('verifikasi.revisi', $laporanRevisi), [
        'catatan' => 'Mohon unggah ulang berkas.',
        'alasan_override' => 'Verifikator memilih revisi langsung.',
    ])->assertRedirect(route('verifikasi.index'));

    $this->post(route('verifikasi.revisi', $laporanRevisi), [
        'catatan' => 'Mohon unggah ulang berkas lagi.',
        'alasan_override' => 'Verifikator memilih revisi langsung.',
    ])->assertStatus(409);

    $this->post(route('verifikasi.anomali', $laporanAnomali), [
        'jenis_anomali' => AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
        'deskripsi' => 'Identitas tidak sesuai.',
        'alasan_override' => 'Verifikator menilai perlu rekonsiliasi.',
    ])->assertRedirect(route('verifikasi.index'));

    $this->post(route('verifikasi.anomali', $laporanAnomali), [
        'jenis_anomali' => AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS,
        'deskripsi' => 'Identitas masih tidak sesuai.',
        'alasan_override' => 'Verifikator menilai perlu rekonsiliasi.',
    ])->assertRedirect(route('verifikasi.index'));

    expect(HasilVerifikasi::where('id_laporan', $laporanSetujui->id_laporan)->count())->toBe(1)
        ->and(HasilVerifikasi::where('id_laporan', $laporanRevisi->id_laporan)->count())->toBe(1)
        ->and(HasilVerifikasi::where('id_laporan', $laporanAnomali->id_laporan)->count())->toBe(2);
});

it('mencari antrean tanpa membedakan huruf besar kecil', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $cocok = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Siti Aminah',
        'nik_almarhum' => '3471031203559001',
        'no_pengajuan' => 'SD-20261028-0001',
    ]);
    $lain = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Budi Santoso',
        'nik_almarhum' => '3471031203559002',
        'no_pengajuan' => 'SD-20261028-0002',
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.index', ['cari' => 'sItI']))
        ->assertOk()
        ->assertSee($cocok->nama_almarhum)
        ->assertDontSee($lain->nama_almarhum);
});

it('memperlakukan wildcard dan backslash pencarian sebagai karakter literal', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $persen = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Budi % Santoso',
        'nik_almarhum' => '3471031203559001',
        'no_pengajuan' => 'SD-20261028-0001',
    ]);
    $tanpaPersen = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Budi Tanpa Persen',
        'nik_almarhum' => '3471031203559002',
        'no_pengajuan' => 'SD-20261028-0002',
    ]);
    $garisBawah = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Nama_Underscore',
        'nik_almarhum' => '3471031203559003',
        'no_pengajuan' => 'SD-20261028-0003',
    ]);
    $tanpaGarisBawah = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'NamaXUnderscore',
        'nik_almarhum' => '3471031203559004',
        'no_pengajuan' => 'SD-20261028-0004',
    ]);
    $backslash = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Budi \\ Santoso',
        'nik_almarhum' => '3471031203559005',
        'no_pengajuan' => 'SD-20261028-0005',
    ]);
    $tanpaBackslash = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Budi X Santoso',
        'nik_almarhum' => '3471031203559006',
        'no_pengajuan' => 'SD-20261028-0006',
    ]);

    $this->actingAs($verifikator);

    $this->get(route('verifikasi.index', ['cari' => '%']))
        ->assertOk()
        ->assertSee($persen->nama_almarhum)
        ->assertDontSee($tanpaPersen->nama_almarhum);

    $this->get(route('verifikasi.index', ['cari' => '_']))
        ->assertOk()
        ->assertSee($garisBawah->nama_almarhum)
        ->assertDontSee($tanpaGarisBawah->nama_almarhum);

    $this->get(route('verifikasi.index', ['cari' => '\\']))
        ->assertOk()
        ->assertSee($backslash->nama_almarhum)
        ->assertDontSee($tanpaBackslash->nama_almarhum);
});

it('menampilkan seluruh antrean saat kata kunci pencarian kosong', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $pertama = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Siti Aminah',
        'nik_almarhum' => '3471031203559001',
        'no_pengajuan' => 'SD-20261028-0001',
    ]);
    $kedua = buatLaporanAntreanVerifikasi($pemohon, [
        'nama_almarhum' => 'Budi Santoso',
        'nik_almarhum' => '3471031203559002',
        'no_pengajuan' => 'SD-20261028-0002',
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.index', ['cari' => '']))
        ->assertOk()
        ->assertSee($pertama->nama_almarhum)
        ->assertSee($kedua->nama_almarhum);
});

it('menampilkan panel analisis AI pada detail verifikasi tanpa mengubah status', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    lampirkanBerkasVerifikasiBerkas($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);
    catatPerbandinganAiVerifikasiBerkas($laporan);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk()
        ->assertSee('Pemeriksaan Dokumen (AI)')
        ->assertSee('Hasil bantuan AI, hanya saran')
        ->assertSee('Baca dokumen (AI)')
        ->assertSee('Buat ringkasan AI')
        ->assertSee(route('verifikasi.analisis_ai', $laporan))
        ->assertSee(route('verifikasi.ringkasan_ai', $laporan));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);

    $laporan->refresh()->load('berkasPendukung');
    expect($laporan->berkasPendukung)->toHaveCount(1);
});

it('menjalankan analisis AI dari alur verifikasi berkas tanpa mengubah status', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasiBerkas(
        $laporan,
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
    );

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHas('analisis_ai');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk()
        ->assertSee('Hasil bantuan AI, hanya saran');

    expect(AnalisisAi::query()
        ->where('id_laporan', $laporan->id_laporan)
        ->where('jenis', AnalisisAi::JENIS_EKSTRAKSI)
        ->count())->toBe(1)
        ->and(AnalisisAi::query()
            ->where('id_laporan', $laporan->id_laporan)
            ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
            ->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('membuat ringkasan AI dari alur verifikasi berkas', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    lampirkanBerkasVerifikasiBerkas($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.ringkasan_ai', $laporan))
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHas('sukses');

    expect(AnalisisAi::query()
        ->where('id_laporan', $laporan->id_laporan)
        ->where('jenis', AnalisisAi::JENIS_RINGKASAN)
        ->where('status', AnalisisAi::STATUS_BERHASIL)
        ->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menolak pratinjau berkas yang belum berhasil diunggah', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasiBerkas($laporan, BerkasPendukung::JENIS_KTP_PELAPOR);
    $berkas->update(['status_unggahan' => BerkasPendukung::STATUS_GAGAL]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.pratinjau', [$laporan, $berkas]))
        ->assertNotFound();
});

it('menolak detail untuk status selain menunggu verifikasi atau rekonsiliasi', function (string $status) {
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon, ['status_pengajuan' => $status]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertNotFound();
})->with([
    'draf' => [LaporanKematian::STATUS_DRAF],
    'selesai' => [LaporanKematian::STATUS_SELESAI],
    'ditolak butuh revisi' => [LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI],
    'disetujui menunggu tte' => [LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE],
]);

it('menampilkan panel pemeriksaan aturan dan rekomendasi mesin', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk()
        ->assertSee('Pemeriksaan Aturan')
        ->assertSee('Rekomendasi Utama Mesin')
        ->assertSee('BERKAS_TIDAK_LENGKAP')
        ->assertSee('Minta Perbaikan Berkas');

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('mewajibkan alasan override saat tindakan berbeda dari rekomendasi mesin', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    lengkapiBerkasVerifikasiBerkas($laporan);

    $this->from(route('verifikasi.show', $laporan))
        ->actingAs($verifikator)
        ->post(route('verifikasi.revisi', $laporan), ['catatan' => 'Mohon unggah ulang berkas.'])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHasErrors('alasan_override');

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and(HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->count())->toBe(0);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.revisi', $laporan), [
            'catatan' => 'Mohon unggah ulang berkas.',
            'alasan_override' => 'Verifikator menemukan dokumen asli bermasalah.',
        ])
        ->assertRedirect(route('verifikasi.index'));

    $hasil = HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->firstOrFail();

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI)
        ->and($hasil->alasan_override)->toBe('Verifikator menemukan dokumen asli bermasalah.')
        ->and($hasil->deteksi_otomatis['anomali'])->toBe([]);
});

it('memproses pengajuan berstatus dalam rekonsiliasi anomali', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DALAM_REKONSILIASI,
    ]);
    lengkapiBerkasVerifikasiBerkas($laporan);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk();

    $this->actingAs($verifikator)
        ->post(route('verifikasi.setujui', $laporan), ['catatan' => 'Rekonsiliasi selesai.'])
        ->assertRedirect(route('verifikasi.index'));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE);
});

it('mengurutkan antrean berdasarkan keparahan anomali tertinggi lalu tanggal', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();

    $bersih = buatLaporanAntreanVerifikasi($pemohon, [
        'tanggal_pengajuan' => now()->subDays(2),
        'no_pengajuan' => 'SD-20261028-0001',
    ]);
    lengkapiBerkasVerifikasiBerkas($bersih);

    $ganda = buatLaporanAntreanVerifikasi($pemohon, [
        'tanggal_pengajuan' => now(),
        'no_pengajuan' => 'SD-20261028-0002',
        'nama_almarhum' => 'Siti Aminah',
        'nik_almarhum' => '3471036407609002',
        'jenis_kelamin' => 'P',
    ]);
    lengkapiBerkasVerifikasiBerkas($ganda);

    buatLaporanAntreanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now()->addMinute(),
        'nik_almarhum' => $ganda->nik_almarhum,
        'no_pengajuan' => 'SD-20261028-0003',
    ]);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.index'))
        ->assertOk();

    $items = $respons->viewData('pengajuan')->items();

    expect($items[0]->id_laporan)->toBe($ganda->id_laporan);
});

it('menampilkan banner manual saat AI tidak tersedia dan tetap bisa memutuskan', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    lengkapiBerkasVerifikasiBerkas($laporan);

    AnalisisAi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
        'status' => AnalisisAi::STATUS_GAGAL,
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.show', $laporan))
        ->assertOk()
        ->assertSee('Layanan eksternal tidak tersedia')
        ->assertSee('Lanjutkan secara manual');

    $this->actingAs($verifikator)
        ->post(route('verifikasi.setujui', $laporan), [
            'catatan' => 'Diverifikasi manual.',
            'alasan_override' => 'AI tidak tersedia, verifikator memeriksa manual.',
        ])
        ->assertRedirect(route('verifikasi.index'));

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE);
});

it('mengalihkan rute verifikasi lama ke alur baru', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);

    $this->actingAs($verifikator)
        ->get('/verifikasi')
        ->assertRedirect(route('verifikasi.index'));

    $this->actingAs($verifikator)
        ->get('/verifikasi/'.$laporan->id_laporan)
        ->assertRedirect(route('verifikasi.show', $laporan));
});

it('menampilkan catatan revisi pada lini masa status pemohon', function () {
    [$pemohon] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
        'catatan_revisi' => 'Kartu Keluarga kurang jelas, mohon unggah ulang.',
    ]);

    $this->actingAs($pemohon)
        ->get(route('pengajuan.detail', $laporan))
        ->assertOk()
        ->assertSee('Catatan Revisi dari Verifikator')
        ->assertSee('Kartu Keluarga kurang jelas, mohon unggah ulang.');
});

it('menolak transisi status di luar pemetaan verifikasi', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon, [
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
    ]);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.setujui', $laporan), ['catatan' => 'Coba setujui'])
        ->assertStatus(409);

    expect($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_SELESAI)
        ->and(HasilVerifikasi::where('id_laporan', $laporan->id_laporan)->count())->toBe(0);
});

it('hanya menampilkan pengajuan menunggu verifikasi dan rekonsiliasi pada antrean', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    $menunggu = buatLaporanAntreanVerifikasi($pemohon, [
        'no_pengajuan' => 'SD-20261028-0001',
        'nik_almarhum' => '3471031203559001',
    ]);
    $rekonsiliasi = buatLaporanAntreanVerifikasi($pemohon, [
        'no_pengajuan' => 'SD-20261028-0002',
        'nik_almarhum' => '3471036407609002',
        'nama_almarhum' => 'Siti Aminah',
        'jenis_kelamin' => 'P',
        'status_pengajuan' => LaporanKematian::STATUS_DALAM_REKONSILIASI,
    ]);
    $draf = buatLaporanAntreanVerifikasi($pemohon, [
        'no_pengajuan' => null,
        'nik_almarhum' => '3471105701829028',
        'nama_almarhum' => 'Retno Palupi',
        'jenis_kelamin' => 'P',
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
    ]);
    $selesai = buatLaporanAntreanVerifikasi($pemohon, [
        'no_pengajuan' => 'SD-20261028-0003',
        'nik_almarhum' => '3471144306539010',
        'nama_almarhum' => 'Karsih',
        'jenis_kelamin' => 'P',
        'status_pengajuan' => LaporanKematian::STATUS_SELESAI,
    ]);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.index'))
        ->assertOk();

    $ids = collect($respons->viewData('pengajuan')->items())
        ->pluck('id_laporan')
        ->all();

    expect($ids)->toContain($menunggu->id_laporan)
        ->and($ids)->toContain($rekonsiliasi->id_laporan)
        ->and($ids)->not->toContain($draf->id_laporan)
        ->and($ids)->not->toContain($selesai->id_laporan);
});

it('membatasi antrean verifikasi sepuluh pengajuan per halaman', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    LaporanKematian::factory()->count(12)->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
    ]);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.index'))
        ->assertOk();

    $halaman = $respons->viewData('pengajuan');

    expect($halaman->total())->toBe(12)
        ->and(count($halaman->items()))->toBe(10);
});

it('mengirim header keamanan pada rute pratinjau berkas', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasiBerkas($laporan, BerkasPendukung::JENIS_KTP_PELAPOR);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.pratinjau', [$laporan, $berkas]))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('mengirim header keamanan pada rute unduh berkas', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasiBerkas($laporan, BerkasPendukung::JENIS_KTP_PELAPOR);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.unduh', [$laporan, $berkas]))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'no-store, private');

    expect($respons->headers->get('Cache-Control'))->toBe('no-store, private');
});

it('mencatat audit analisis AI verifikator dengan jenis, model, dan pemicu', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasiBerkas($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
        ->assertRedirect(route('verifikasi.show', $laporan));

    $audit = LogAudit::query()
        ->where('aksi', 'menganalisis_dokumen_ai')
        ->where('id_objek', $laporan->id_laporan)
        ->firstOrFail();

    expect($audit->layanan_eksternal)->toBe(LogAudit::LAYANAN_AI)
        ->and($audit->hasil)->toBe(LogAudit::HASIL_SUKSES)
        ->and($audit->detail)->toMatchArray([
            'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
            'model' => 'mock-ai-v1',
            'pemicu' => 'verifikator_detail',
        ]);
});

it('mengarahkan kegagalan analisis AI ke halaman verifikasi tanpa mengubah status', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporan = buatLaporanAntreanVerifikasi($pemohon);
    $berkas = lampirkanBerkasVerifikasiBerkas($laporan, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);

    Storage::disk('local')->put($berkas->path_file, '%PDF-1.4 GAGAL-'.Str::random(12));

    aktifkanDriverOpenAiVerifikasiBerkas();
    Http::fake([
        'https://api.openai.test/v1/responses' => Http::response(['error' => ['message' => 'gagal']], 500),
    ]);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporan), ['id_berkas' => $berkas->id_berkas])
        ->assertRedirect(route('verifikasi.show', $laporan))
        ->assertSessionHas('galat', 'Analisis AI tidak tersedia, lanjutkan secara manual');

    expect(AnalisisAi::query()
        ->where('id_laporan', $laporan->id_laporan)
        ->where('jenis', AnalisisAi::JENIS_EKSTRAKSI)
        ->where('status', AnalisisAi::STATUS_GAGAL)
        ->count())->toBe(1)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});

it('menolak analisis AI dengan berkas milik laporan lain tanpa membuat catatan', function () {
    Storage::fake('local');

    [$pemohon, $verifikator] = buatAkunDemo();
    $laporanA = buatLaporanAntreanVerifikasi($pemohon, ['nik_almarhum' => '3471031203559001']);
    $laporanB = buatLaporanAntreanVerifikasi($pemohon, [
        'nik_almarhum' => '3471036407609002',
        'nama_almarhum' => 'Siti Aminah',
        'jenis_kelamin' => 'P',
    ]);
    $berkasB = lampirkanBerkasVerifikasiBerkas($laporanB, BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.analisis_ai', $laporanA), ['id_berkas' => $berkasB->id_berkas])
        ->assertRedirect(route('verifikasi.show', $laporanA))
        ->assertSessionHas('galat', 'Surat Keterangan Kematian tidak ditemukan.');

    expect(AnalisisAi::query()->count())->toBe(0);
});

it('mengurutkan antrean menurut tanggal saat keparahan sama dan keparahan mengalahkan tanggal', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    $bersihLama = buatLaporanAntreanVerifikasi($pemohon, [
        'no_pengajuan' => 'SD-20261028-0001',
        'nik_almarhum' => '3471031203559001',
        'nama_almarhum' => 'Suharto Wiryo',
        'jenis_kelamin' => 'L',
        'tanggal_pengajuan' => now()->subDays(3),
    ]);
    lengkapiBerkasVerifikasiBerkas($bersihLama);

    $bersihBaru = buatLaporanAntreanVerifikasi($pemohon, [
        'no_pengajuan' => 'SD-20261028-0002',
        'nik_almarhum' => '3471105701829028',
        'nama_almarhum' => 'Retno Palupi',
        'jenis_kelamin' => 'P',
        'tanggal_pengajuan' => now()->subDays(2),
    ]);
    lengkapiBerkasVerifikasiBerkas($bersihBaru);

    $fatalTerbaru = buatLaporanAntreanVerifikasi($pemohon, [
        'no_pengajuan' => 'SD-20261028-0003',
        'nik_almarhum' => '3471136204709016',
        'nama_almarhum' => 'dr. Retno Wulandari',
        'jenis_kelamin' => 'P',
        'tanggal_pengajuan' => now(),
    ]);
    lengkapiBerkasVerifikasiBerkas($fatalTerbaru);

    $respons = $this->actingAs($verifikator)
        ->get(route('verifikasi.index'))
        ->assertOk();

    $items = $respons->viewData('pengajuan')->items();

    expect(collect($items)->pluck('id_laporan')->all())->toBe([
        $fatalTerbaru->id_laporan,
        $bersihLama->id_laporan,
        $bersihBaru->id_laporan,
    ]);
});
