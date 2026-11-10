<?php

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Services\Verifikasi\AnomaliVerifikasi;
use App\Services\Verifikasi\HasilAnalisis;
use App\Services\Verifikasi\LayananAturanBerkas;
use App\Services\Verifikasi\LayananValidasiNik;
use App\Services\Verifikasi\MesinRekonsiliasi;
use Database\Seeders\ReferensiWilayahSeeder;

/**
 * Menyiapkan referensi kecamatan Kota Yogyakarta untuk pemeriksaan alamat.
 */
function siapkanReferensiRekonsiliasi(): void
{
    (new ReferensiWilayahSeeder)->run();
}

/**
 * Laporan bersih dengan data almarhum untuk pemeriksaan dokumen.
 *
 * @param  array<string, mixed>  $timpa
 */
function buatLaporanRekonsiliasi(array $timpa = []): LaporanKematian
{
    siapkanReferensiRekonsiliasi();

    return LaporanKematian::factory()->create(array_merge([
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
        'tanggal_kematian' => now()->subDays(5)->toDateString(),
        'jenis_kasus' => LaporanKematian::JENIS_KASUS_NORMAL,
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => '3471031203559001',
        'jenis_kelamin' => 'L',
    ], $timpa));
}

/**
 * Melampirkan seluruh berkas wajib pada laporan.
 */
function lengkapiBerkasRekonsiliasi(LaporanKematian $laporan): void
{
    $jenisWajib = array_merge(
        [config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0]],
        config('verifikasi.berkas_wajib.lainnya'),
    );

    foreach ($jenisWajib as $jenis) {
        BerkasPendukung::factory()->create([
            'id_laporan' => $laporan->id_laporan,
            'jenis_dokumen' => $jenis,
            'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
        ]);
    }
}

/**
 * @return list<string>
 */
function kodeRekonsiliasi(HasilAnalisis $hasil): array
{
    return $hasil->kodeAnomali();
}

function mesinRekonsiliasi(): MesinRekonsiliasi
{
    return new MesinRekonsiliasi(
        validasiNik: app(LayananValidasiNik::class),
        aturanBerkas: app(LayananAturanBerkas::class),
    );
}

it('menghasilkan hasil bersih tanpa menulis atau mengubah status laporan', function () {
    $laporan = buatLaporanRekonsiliasi();
    lengkapiBerkasRekonsiliasi($laporan);

    $sebelum = $laporan->fresh()->toArray();
    $jumlahAnalisisAi = AnalisisAi::query()->count();

    $hasil = mesinRekonsiliasi()->analisis($laporan);

    expect($hasil->anomali)->toBe([])
        ->and($hasil->bersih())->toBeTrue()
        ->and($hasil->rekomendasiUtama)->toBe('Siap disetujui')
        ->and($hasil->layananTidakTersedia)->toBeFalse()
        ->and($laporan->fresh()->toArray())->toBe($sebelum)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and(AnalisisAi::query()->count())->toBe($jumlahAnalisisAi);
});

it('mendeteksi setiap kode anomali pada taksonomi', function (string $kode, Closure $siapkan) {
    $laporan = buatLaporanRekonsiliasi();
    lengkapiBerkasRekonsiliasi($laporan);

    $mesin = $siapkan($laporan);

    $hasil = ($mesin instanceof MesinRekonsiliasi ? $mesin : mesinRekonsiliasi())
        ->analisis($laporan->fresh());

    expect(kodeRekonsiliasi($hasil))->toContain($kode);
})->with([
    'pengajuan ganda' => [
        MesinRekonsiliasi::KODE_PENGAJUAN_GANDA,
        function (LaporanKematian $laporan): ?MesinRekonsiliasi {
            LaporanKematian::factory()->create([
                'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                'tanggal_pengajuan' => now(),
                'nik_almarhum' => $laporan->nik_almarhum,
            ]);

            return null;
        },
    ],
    'kasus khusus' => [
        MesinRekonsiliasi::KODE_KASUS_KHUSUS,
        function (LaporanKematian $laporan): ?MesinRekonsiliasi {
            $laporan->update(['jenis_kasus' => LaporanKematian::JENIS_KASUS_TIDAK_DIKENAL]);

            BerkasPendukung::factory()->create([
                'id_laporan' => $laporan->id_laporan,
                'jenis_dokumen' => BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN,
                'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
            ]);

            return null;
        },
    ],
    'nik tidak valid' => [
        MesinRekonsiliasi::KODE_NIK_TIDAK_VALID,
        function (LaporanKematian $laporan): ?MesinRekonsiliasi {
            $laporan->update(['nik_almarhum' => '123']);

            return null;
        },
    ],
    'tanggal tidak konsisten' => [
        MesinRekonsiliasi::KODE_TANGGAL_TIDAK_KONSISTEN,
        function (LaporanKematian $laporan): ?MesinRekonsiliasi {
            AnalisisAi::factory()->create([
                'id_laporan' => $laporan->id_laporan,
                'jenis' => AnalisisAi::JENIS_PERBANDINGAN,
                'status' => AnalisisAi::STATUS_BERHASIL,
                'hasil' => [
                    'kolom' => [
                        ['kunci' => 'tanggal_kematian', 'label' => 'Tanggal Kematian', 'status' => 'beda'],
                    ],
                ],
            ]);

            return null;
        },
    ],
    'terlambat tanpa sptjm' => [
        MesinRekonsiliasi::KODE_TERLAMBAT_TANPA_SPTJM,
        function (LaporanKematian $laporan): ?MesinRekonsiliasi {
            $laporan->update(['tanggal_kematian' => now()->subDays(40)->toDateString()]);

            return null;
        },
    ],
    'berkas tidak lengkap' => [
        MesinRekonsiliasi::KODE_BERKAS_TIDAK_LENGKAP,
        function (LaporanKematian $laporan): ?MesinRekonsiliasi {
            $laporan->berkasPendukung()->delete();

            return null;
        },
    ],
    'dokumen tidak terbaca' => [
        MesinRekonsiliasi::KODE_DOKUMEN_TIDAK_TERBACA,
        function (LaporanKematian $laporan): ?MesinRekonsiliasi {
            AnalisisAi::factory()->create([
                'id_laporan' => $laporan->id_laporan,
                'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
                'status' => AnalisisAi::STATUS_GAGAL,
            ]);

            return null;
        },
    ],
]);

it('mengurutkan banyak anomali dari keparahan tertinggi dan memakai rekomendasi tertinggi', function () {
    $laporan = buatLaporanRekonsiliasi([
        'tanggal_kematian' => now()->subDays(40)->toDateString(),
    ]);
    lengkapiBerkasRekonsiliasi($laporan);

    LaporanKematian::factory()->create([
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
        'nik_almarhum' => $laporan->nik_almarhum,
    ]);

    AnalisisAi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
        'status' => AnalisisAi::STATUS_GAGAL,
    ]);
    AnalisisAi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis' => AnalisisAi::JENIS_PERBANDINGAN,
        'status' => AnalisisAi::STATUS_BERHASIL,
        'hasil' => [
            'kolom' => [
                ['kunci' => 'nama_almarhum', 'label' => 'Nama Almarhum', 'status' => 'beda'],
                ['kunci' => 'tanggal_kematian', 'label' => 'Tanggal Kematian', 'status' => 'beda'],
            ],
        ],
    ]);

    $hasil = mesinRekonsiliasi()->analisis($laporan->fresh());

    $urutan = array_flip((array) config('verifikasi.urutan_keparahan'));
    $bobot = array_map(
        static fn (AnomaliVerifikasi $anomali): int => $urutan[$anomali->keparahan] ?? PHP_INT_MAX,
        $hasil->anomali,
    );
    $terurut = $bobot;
    sort($terurut);

    expect($bobot)->toBe($terurut)
        ->and($hasil->anomali[0]->kode)->toBe(MesinRekonsiliasi::KODE_PENGAJUAN_GANDA)
        ->and($hasil->punyaKode(MesinRekonsiliasi::KODE_NAMA_TIDAK_COCOK))->toBeTrue()
        ->and($hasil->punyaKode(MesinRekonsiliasi::KODE_TERLAMBAT_TANPA_SPTJM))->toBeTrue()
        ->and($hasil->punyaKode(MesinRekonsiliasi::KODE_DOKUMEN_TIDAK_TERBACA))->toBeTrue()
        ->and($hasil->rekomendasiUtama)
        ->toBe(config('verifikasi.taksonomi_anomali.PENGAJUAN_GANDA.rekomendasi'));
});

it('tidak membandingkan data pengajuan dengan SIAK', function () {
    $laporan = buatLaporanRekonsiliasi([
        'nik_almarhum' => '3471031203559999',
        'nama_almarhum' => 'Nama yang tidak ada di data kependudukan',
    ]);
    lengkapiBerkasRekonsiliasi($laporan);

    $hasil = mesinRekonsiliasi()->analisis($laporan->fresh());

    expect($hasil->layananTidakTersedia)->toBeFalse()
        ->and($hasil->anomali)->toBe([])
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
        ->and(LogAudit::query()->where('layanan_eksternal', LogAudit::LAYANAN_SIAK)->count())->toBe(0);
});

it('menyertakan ketidakcocokan hasil ekstraksi AI yang sudah tersimpan', function () {
    $laporan = buatLaporanRekonsiliasi();
    lengkapiBerkasRekonsiliasi($laporan);

    AnalisisAi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis' => AnalisisAi::JENIS_PERBANDINGAN,
        'status' => AnalisisAi::STATUS_BERHASIL,
        'hasil' => [
            'kolom' => [
                ['kunci' => 'nama_almarhum', 'label' => 'Nama Almarhum', 'status' => 'beda'],
                ['kunci' => 'tanggal_kematian', 'label' => 'Tanggal Kematian', 'status' => 'beda'],
            ],
        ],
    ]);

    $hasil = mesinRekonsiliasi()->analisis($laporan->fresh());

    expect($hasil->punyaKode(MesinRekonsiliasi::KODE_NAMA_TIDAK_COCOK))->toBeTrue()
        ->and($hasil->punyaKode(MesinRekonsiliasi::KODE_TANGGAL_TIDAK_KONSISTEN))->toBeTrue()
        ->and($hasil->layananTidakTersedia)->toBeFalse();
});

it('menyimpan snapshot deteksi otomatis dan alasan override tanpa mengubah keputusan', function () {
    $laporan = buatLaporanRekonsiliasi();
    lengkapiBerkasRekonsiliasi($laporan);

    $verifikasi = HasilVerifikasi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'status_verifikasi' => HasilVerifikasi::STATUS_DITERIMA,
    ]);

    $mesin = mesinRekonsiliasi();
    $hasil = $mesin->analisis($laporan);
    $tersimpan = $mesin->simpanKeputusan($verifikasi, $hasil, 'Verifikator memilih jalur manual.');

    expect($tersimpan->deteksi_otomatis)->toBeArray()
        ->and($tersimpan->deteksi_otomatis['versi'])->toBe(1)
        ->and($tersimpan->deteksi_otomatis['rekomendasi_utama'])->toBe('Siap disetujui')
        ->and($tersimpan->deteksi_otomatis['layanan_tidak_tersedia'])->toBeFalse()
        ->and($tersimpan->deteksi_otomatis['anomali'])->toBe([])
        ->and($tersimpan->alasan_override)->toBe('Verifikator memilih jalur manual.')
        ->and($tersimpan->status_verifikasi)->toBe(HasilVerifikasi::STATUS_DITERIMA)
        ->and($laporan->fresh()->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI);
});
