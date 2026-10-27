<?php

use App\Models\BerkasPendukung;
use App\Models\HasilVerifikasi;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Models\ReferensiWilayah;
use App\Support\DataSIAK;
use Database\Seeders\ReferensiWilayahSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Fondasi verifikasi: konfigurasi, referensi wilayah, dan data SIAK.
 */
it('menyediakan ambang dan daftar berkas pada konfigurasi verifikasi', function () {
    expect(config('verifikasi.batas_hari_pelaporan'))->toBe(30)
        ->and(config('verifikasi.wajib_saksi'))->toBeFalse()
        ->and(config('verifikasi.nama_cocok_persis'))->toEqual(1.0)
        ->and(config('verifikasi.nama_beda_tipis'))->toEqual(0.80)
        ->and(config('verifikasi.batas_usia'))->toBe(120)
        ->and(config('verifikasi.izinkan_nik_demo'))->toBeBool()
        ->and(config('verifikasi.berkas_wajib.bukti_kematian.minimal_satu'))->toBeTrue()
        ->and(config('verifikasi.berkas_wajib.bukti_kematian.jenis'))
        ->toContain(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN)
        ->and(config('verifikasi.berkas_wajib.lainnya'))
        ->toContain(BerkasPendukung::JENIS_KTP_ALMARHUM)
        ->and(config('verifikasi.berkas_bersyarat.keterlambatan'))
        ->toContain(BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN)
        ->and(config('verifikasi.berkas_bersyarat.jenis_kasus_khusus'))
        ->toContain(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN)
        ->and(config('verifikasi.berkas_opsional'))
        ->toContain(BerkasPendukung::JENIS_KTP_SAKSI_1)
        ->and(config('verifikasi.berkas_opsional'))
        ->toContain(BerkasPendukung::JENIS_KTP_SAKSI_2);
});

it('mendaftar kode provinsi secara eksplisit', function () {
    $jalur = base_path('database/data/kode_provinsi.json');
    $data = json_decode((string) file_get_contents($jalur), true);

    expect($data)->toBeArray()->toHaveCount(38);

    $kode = array_column($data, 'kode');

    expect($kode)->toContain('34')
        ->and($kode)->toContain('93')
        ->and($kode)->toContain('94')
        ->and($kode)->toContain('95')
        ->and($kode)->toContain('96')
        ->and($kode)->toBe(array_values(array_unique($kode)));

    foreach ($kode as $nilai) {
        expect($nilai)->toMatch('/^\d{2}$/');
    }
});

it('mengisi referensi wilayah dari data kecamatan 2020', function () {
    $this->seed(ReferensiWilayahSeeder::class);

    expect(ReferensiWilayah::query()->count())->toBe(14);

    $gondokusuman = ReferensiWilayah::query()->find('347103');

    expect($gondokusuman)->not->toBeNull()
        ->and($gondokusuman->nama_kecamatan)->toBe('Gondokusuman')
        ->and($gondokusuman->jumlah_kematian_2020)->toBe(174);
});

it('mempertahankan konstanta empat jenis lama dan memakai aturan kelengkapan baru', function () {
    expect(BerkasPendukung::DAFTAR_JENIS)->toContain(BerkasPendukung::JENIS_SURAT_KETERANGAN_KELURAHAN)
        ->and(BerkasPendukung::DAFTAR_JENIS)->toContain(BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN)
        ->and(BerkasPendukung::DAFTAR_JENIS)->toContain(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN)
        ->and(BerkasPendukung::DAFTAR_JENIS)->toContain(BerkasPendukung::JENIS_KTP_SAKSI_1)
        ->and(BerkasPendukung::DAFTAR_JENIS)->toContain(BerkasPendukung::JENIS_KTP_SAKSI_2)
        ->and(BerkasPendukung::DAFTAR_JENIS_WAJIB)->toHaveCount(4)
        ->and(BerkasPendukung::jenisYangKurang([], false, LaporanKematian::JENIS_KASUS_NORMAL))->toHaveCount(4);

    $lengkap = [
        BerkasPendukung::JENIS_SURAT_KETERANGAN_KELURAHAN,
        BerkasPendukung::JENIS_KTP_ALMARHUM,
        BerkasPendukung::JENIS_KARTU_KELUARGA,
        BerkasPendukung::JENIS_KTP_PELAPOR,
    ];

    expect(BerkasPendukung::jenisYangKurang($lengkap, false, LaporanKematian::JENIS_KASUS_NORMAL))->toBe([])
        ->and(BerkasPendukung::jenisYangKurang($lengkap, true, LaporanKematian::JENIS_KASUS_NORMAL))
        ->toBe([BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN])
        ->and(BerkasPendukung::jenisYangKurang($lengkap, false, LaporanKematian::JENIS_KASUS_TIDAK_DIKENAL))
        ->toBe([BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN]);
});

it('menyimpan jenis kasus dan hasil deteksi aturan', function () {
    expect(Schema::hasColumn('laporan_kematian', 'jenis_kasus'))->toBeTrue()
        ->and(Schema::hasColumn('hasil_verifikasi', 'deteksi_otomatis'))->toBeTrue()
        ->and(Schema::hasColumn('hasil_verifikasi', 'alasan_override'))->toBeTrue();

    $laporan = LaporanKematian::factory()->create();
    $verifikasi = HasilVerifikasi::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'deteksi_otomatis' => ['anomali' => []],
        'alasan_override' => 'Verifikator memilih jalur manual.',
    ]);

    expect($laporan->fresh()->jenis_kasus)->toBe(LaporanKematian::JENIS_KASUS_NORMAL)
        ->and($verifikasi->fresh()->deteksi_otomatis)->toBe(['anomali' => []])
        ->and($verifikasi->fresh()->alasan_override)->toBe('Verifikator memilih jalur manual.');
});

it('menampilkan bukti kematian sebagai kelompok dan menandai berkas bersyarat', function () {
    $pemohon = Pengguna::factory()->pemohon()->create();

    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'no_pengajuan' => null,
        'tanggal_pengajuan' => null,
        'tanggal_kematian' => now()->subDays(40)->toDateString(),
        'jenis_kasus' => LaporanKematian::JENIS_KASUS_NORMAL,
    ]);

    $this->actingAs($pemohon)
        ->get(route('laporan.ubah', $laporan).'?langkah=2')
        ->assertOk()
        ->assertSee('Jenis Kasus');

    $this->actingAs($pemohon)
        ->get(route('laporan.ubah', $laporan).'?langkah=3')
        ->assertOk()
        ->assertSee('Bukti Kematian')
        ->assertSee('Pilih minimal satu dokumen berikut.')
        ->assertSee(BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN)
        ->assertSee('Wajib karena keterlambatan')
        ->assertDontSee(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN);

    $laporan->update(['jenis_kasus' => LaporanKematian::JENIS_KASUS_TIDAK_DIKENAL]);

    $this->actingAs($pemohon)
        ->get(route('laporan.ubah', $laporan).'?langkah=3')
        ->assertOk()
        ->assertSee(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN)
        ->assertSee('Wajib karena jenis kasus khusus');

    $laporan->update([
        'jenis_kasus' => LaporanKematian::JENIS_KASUS_NORMAL,
        'tanggal_kematian' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($pemohon)
        ->get(route('laporan.ubah', $laporan).'?langkah=3')
        ->assertOk()
        ->assertSee(BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN)
        ->assertDontSee('Wajib karena keterlambatan')
        ->assertDontSee(BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN);
});

it('menjaga data SIAK dummy tetap konsisten dan bervariasi', function () {
    $data = DataSIAK::semua();
    $provinsi = array_column(
        json_decode((string) file_get_contents(base_path('database/data/kode_provinsi.json')), true),
        'kode',
    );

    expect(count($data))->toBeGreaterThanOrEqual(30);

    $nikTerpakai = [];
    $meninggal = 0;
    $luarKota = 0;
    $bergelar = 0;
    $namaTunggal = 0;

    foreach ($data as $penduduk) {
        $nik = $penduduk['nik'];

        expect($nik)->toMatch('/^\d{16}$/')
            ->and($nikTerpakai)->not->toHaveKey($nik)
            ->and($provinsi)->toContain(substr($nik, 0, 2))
            ->and((int) substr($nik, 12, 4))->toBeGreaterThanOrEqual(9001);

        $nikTerpakai[$nik] = true;

        $tanggalLahir = Carbon::createFromFormat('Y-m-d', $penduduk['tanggal_lahir']);
        $tanggalNik = (int) substr($nik, 6, 2);
        $bulanNik = (int) substr($nik, 8, 2);
        $tahunNik = (int) substr($nik, 10, 2);

        if ($penduduk['jenis_kelamin'] === 'P') {
            $tanggalNik -= 40;
        }

        expect($tanggalNik)->toBe($tanggalLahir->day)
            ->and($bulanNik)->toBe($tanggalLahir->month)
            ->and($tahunNik)->toBe((int) $tanggalLahir->format('y'));

        $meninggal += $penduduk['status_kependudukan'] === DataSIAK::STATUS_MENINGGAL ? 1 : 0;
        $luarKota += str_contains($penduduk['alamat'], 'Yogyakarta') ? 0 : 1;
        $bergelar += str_contains($penduduk['nama_lengkap'], '.') ? 1 : 0;
        $namaTunggal += str_contains($penduduk['nama_lengkap'], ' ') ? 0 : 1;
    }

    expect($meninggal)->toBe(3)
        ->and($luarKota)->toBeGreaterThanOrEqual(2)
        ->and($bergelar)->toBeGreaterThanOrEqual(2)
        ->and($namaTunggal)->toBeGreaterThanOrEqual(2);
});
