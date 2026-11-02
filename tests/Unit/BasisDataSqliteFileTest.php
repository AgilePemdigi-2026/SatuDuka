<?php

use App\Http\Controllers\LaporanController;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

it('menjalankan migrasi dan alur pengajuan pada sqlite berbasis file', function () {
    $path = tempnam(sys_get_temp_dir(), 'satuduka-');

    expect($path)->not->toBeFalse();

    $koneksi = 'sqlite_berkas_uji';
    $koneksiAwal = DB::getDefaultConnection();

    config([
        'database.connections.'.$koneksi => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 5000,
            'journal_mode' => 'WAL',
            'synchronous' => 'NORMAL',
        ],
    ]);

    DB::purge($koneksi);

    try {
        Artisan::call('migrate', ['--database' => $koneksi, '--force' => true]);

        DB::setDefaultConnection($koneksi);

        expect(DB::connection()->getDriverName())->toBe('sqlite')
            ->and(is_file($path))->toBeTrue()
            ->and((int) DB::selectOne('pragma foreign_keys')->foreign_keys)->toBe(1)
            ->and((int) DB::selectOne('pragma busy_timeout')->timeout)->toBe(5000)
            ->and(strtolower(DB::selectOne('pragma journal_mode')->journal_mode))->toBe('wal')
            ->and((int) DB::selectOne('pragma synchronous')->synchronous)->toBe(1);

        Storage::fake('local');

        $pemohon = Pengguna::factory()->pemohon()->create([
            'nama_lengkap' => 'Pemohon Berkas',
            'nik' => '9999990101900099',
            'email' => 'pemohon.berkas@contoh.id',
            'no_telepon' => '081234567890',
        ]);

        $data = [
            'nama_pelapor' => $pemohon->nama_lengkap,
            'hubungan_pelapor' => 'Anak Kandung',
            'nama_almarhum' => 'Budi Santoso',
            'nik_almarhum' => '3471031203559001',
            'tanggal_kematian' => now()->subDay()->toDateString(),
            'tempat_kematian' => 'RSUD Kota Yogyakarta',
            'sebab_kematian' => 'Sakit',
        ];

        $this->actingAs($pemohon)
            ->post(route('laporan.draf'), $data)
            ->assertRedirect();

        $laporan = LaporanKematian::firstOrFail();

        $jenisWajib = array_merge(
            [config('verifikasi.berkas_wajib.bukti_kematian.jenis')[0]],
            config('verifikasi.berkas_wajib.lainnya'),
        );

        foreach ($jenisWajib as $jenis) {
            $this->post(route('laporan.berkas.unggah', $laporan), [
                'jenis_dokumen' => $jenis,
                LaporanController::INPUT_BERKAS[$jenis] => UploadedFile::fake()->create('berkas.pdf', 100, 'application/pdf'),
            ])->assertRedirect();
        }

        $this->post(route('laporan.kirim', $laporan), $data + ['pernyataan' => '1'])
            ->assertRedirect(route('laporan.lihat', $laporan));

        $laporan->refresh();

        expect($laporan->status_pengajuan)->toBe(LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
            ->and($laporan->no_pengajuan)->toMatch('/^SD-\d{8}-\d{4}$/')
            ->and(BerkasPendukung::count())->toBe(4);
    } finally {
        DB::setDefaultConnection($koneksiAwal);
        DB::purge($koneksi);

        foreach ([$path, $path.'-wal', $path.'-shm'] as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }
    }
});

it('menjalankan dua transaksi tulis berurutan pada sqlite berbasis file', function () {
    $path = tempnam(sys_get_temp_dir(), 'satuduka-tulis-');

    expect($path)->not->toBeFalse();

    $koneksiSatu = 'sqlite_tulis_satu';
    $koneksiDua = 'sqlite_tulis_dua';
    $konfigurasi = config('database.connections.sqlite');
    $konfigurasi['database'] = $path;
    $konfigurasi['url'] = null;

    config([
        'database.connections.'.$koneksiSatu => $konfigurasi,
        'database.connections.'.$koneksiDua => $konfigurasi,
    ]);

    DB::purge($koneksiSatu);
    DB::purge($koneksiDua);

    try {
        expect(config('database.connections.'.$koneksiSatu.'.transaction_mode'))->toBe('IMMEDIATE');

        DB::connection($koneksiSatu)->statement(
            'create table uji_transaksi (id integer primary key autoincrement, nilai text not null)'
        );

        // Kedua transaksi harus selesai; galat "database is locked" akan menggagalkan tes.
        DB::connection($koneksiSatu)->transaction(function () use ($koneksiSatu): void {
            DB::connection($koneksiSatu)->table('uji_transaksi')->insert(['nilai' => 'pertama']);
        });

        DB::connection($koneksiDua)->transaction(function () use ($koneksiDua): void {
            DB::connection($koneksiDua)->table('uji_transaksi')->insert(['nilai' => 'kedua']);
        });

        expect(DB::connection($koneksiSatu)->table('uji_transaksi')->orderBy('id')->pluck('nilai')->all())
            ->toBe(['pertama', 'kedua']);
    } finally {
        DB::purge($koneksiSatu);
        DB::purge($koneksiDua);

        foreach ([$path, $path.'-wal', $path.'-shm'] as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }
    }
});
