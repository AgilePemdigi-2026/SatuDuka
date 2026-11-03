<?php

/**
 * Pengujian enkripsi NIK (Fase 9b): cast `encrypted` + blind index HMAC.
 *
 * Semua pemeriksaan nilai mentah memakai `DB::table` agar yang dibaca benar-
 * benar isi kolom, bukan hasil dekripsi cast Eloquent.
 */

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Services\Verifikasi\LayananAturanBerkas;
use App\Support\NikAnalisis;
use App\Support\PengindeksNik;
use App\Support\PenyelarasNik;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

const NIK_UJI = '3471031203559001';
const NIK_UJI_LAIN = '3471031203559002';

it('menyimpan NIK pengguna dan almarhum dalam bentuk terenkripsi', function () {
    $pemohon = Pengguna::factory()->pemohon()->create(['nik' => NIK_UJI]);

    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'nik_almarhum' => NIK_UJI_LAIN,
    ]);

    $mentahPengguna = (string) DB::table('pengguna')
        ->where('id_pengguna', $pemohon->id_pengguna)
        ->value('nik');
    $mentahLaporan = (string) DB::table('laporan_kematian')
        ->where('id_laporan', $laporan->id_laporan)
        ->value('nik_almarhum');

    expect($mentahPengguna)->not->toBe(NIK_UJI)
        ->and($mentahLaporan)->not->toBe(NIK_UJI_LAIN)
        ->and($mentahPengguna)->not->toMatch('/^\d{16}$/')
        ->and($mentahLaporan)->not->toMatch('/^\d{16}$/')
        ->and(strlen($mentahPengguna))->toBeGreaterThan(16)
        ->and(Crypt::decryptString($mentahPengguna))->toBe(NIK_UJI)
        ->and(Crypt::decryptString($mentahLaporan))->toBe(NIK_UJI_LAIN);

    // Cast tetap mengembalikan plaintext saat dibaca lewat model.
    expect($pemohon->fresh()->nik)->toBe(NIK_UJI)
        ->and($laporan->fresh()->nik_almarhum)->toBe(NIK_UJI_LAIN);

    // Blind index tersimpan dan tidak sama dengan hash polos APP_KEY.
    expect(DB::table('pengguna')->where('id_pengguna', $pemohon->id_pengguna)->value('nik_hash'))
        ->toBe(PengindeksNik::hash(NIK_UJI))
        ->not->toBe(hash('sha256', NIK_UJI));

    expect(PengindeksNik::kunci())->not->toBe((string) config('app.key'));
});

it('mencari pengguna dengan NIK lengkap termasuk format berspasi dan bertanda hubung', function () {
    $pemohon = Pengguna::factory()->pemohon()->create(['nik' => NIK_UJI]);

    expect(Pengguna::cariDenganNik(NIK_UJI)?->is($pemohon))->toBeTrue()
        ->and(Pengguna::cariDenganNik('3471 0312 0355 9001')?->is($pemohon))->toBeTrue()
        ->and(Pengguna::cariDenganNik('3471-0312-0355-9001')?->is($pemohon))->toBeTrue()
        ->and(Pengguna::cariDenganNik('34710312'))->toBeNull()
        ->and(Pengguna::cariDenganNik('0000000000000000'))->toBeNull();

    $this->post(route('masuk.coba'), [
        'identitas' => '3471-0312-0355-9001',
        'kata_sandi' => 'password123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($pemohon);
});

it('mencari antrean verifikasi dengan NIK lengkap tetapi tidak dengan NIK sebagian', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'nama_almarhum' => 'Almarhum Pencarian Nik',
        'nik_almarhum' => NIK_UJI,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
    ]);

    $this->actingAs($verifikator)
        ->get(route('verifikasi.index', ['cari' => NIK_UJI]))
        ->assertOk()
        ->assertSee('Almarhum Pencarian Nik');

    $this->actingAs($verifikator)
        ->get(route('verifikasi.index', ['cari' => '34710312']))
        ->assertOk()
        ->assertDontSee('Almarhum Pencarian Nik');
});

it('tetap mendeteksi pengajuan ganda dan menolak duplikat NIK lewat hash', function () {
    [$pemohon] = buatAkunDemo();

    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'nik_almarhum' => NIK_UJI,
        'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
        'tanggal_pengajuan' => now(),
    ]);

    expect((new LayananAturanBerkas)->adaPengajuanGanda(NIK_UJI, null))->toBeTrue()
        ->and((new LayananAturanBerkas)->adaPengajuanGanda(NIK_UJI, (int) $laporan->id_laporan))->toBeFalse();

    Pengguna::factory()->pemohon()->create(['nik' => NIK_UJI_LAIN]);

    expect(fn () => Pengguna::factory()->pemohon()->create(['nik' => NIK_UJI_LAIN]))
        ->toThrow(QueryException::class);
});

it('menolak registrasi dengan NIK yang sudah terdaftar', function () {
    Pengguna::factory()->pemohon()->create(['nik' => NIK_UJI]);

    $this->post(route('registrasi.simpan'), [
        'nik' => NIK_UJI,
        'email' => 'baru@demo.satuduka.id',
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])->assertSessionHasErrors('nik');

    expect(Pengguna::query()->where('email', 'baru@demo.satuduka.id')->exists())->toBeFalse();
});

it('mengizinkan petugas tanpa NIK tetap masuk dengan NIP', function () {
    $verifikator = Pengguna::factory()->verifikator()->create([
        'nik' => null,
        'nip' => '999999991234567890',
        'kata_sandi' => 'password123',
    ]);

    expect(DB::table('pengguna')->where('id_pengguna', $verifikator->id_pengguna)->value('nik_hash'))
        ->toBeNull();

    $this->post(route('masuk.coba'), [
        'identitas' => '999999991234567890',
        'kata_sandi' => 'password123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($verifikator);
});

it('mewajibkan kunci hash eksplisit pada produksi dan gagal dengan pesan jelas', function () {
    $this->app['env'] = 'production';
    config(['satuduka.nik_hash_key' => null]);

    expect(fn () => PengindeksNik::kunci())
        ->toThrow(LogicException::class, PengindeksNik::PESAN_KUNCI_KOSONG);

    expect(fn () => PenyelarasNik::jalankan())
        ->toThrow(LogicException::class, PengindeksNik::PESAN_KUNCI_KOSONG);

    config(['satuduka.nik_hash_key' => str_repeat('k', 64)]);

    expect(PengindeksNik::kunci())->toBe(str_repeat('k', 64));
});

it('mengubah hash bila kunci blind index dirotasi', function () {
    $awal = PengindeksNik::hash(NIK_UJI);

    config(['satuduka.nik_hash_key' => 'kunci-rotasi-uji']);

    expect(PengindeksNik::hash(NIK_UJI))->not->toBe($awal);
});

it('menyamarkan NIK pada hasil analisis AI dan menyimpan blind index', function () {
    Storage::fake('local');

    [$pemohon] = buatAkunDemo();
    $laporan = LaporanKematian::factory()->create([
        'id_pengguna' => $pemohon->id_pengguna,
        'no_pengajuan' => null,
        'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        'tanggal_pengajuan' => null,
        'nama_almarhum' => 'Suharto Wiryo',
        'nik_almarhum' => NIK_UJI,
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'RSUD Kota Yogyakarta',
    ]);

    $path = 'berkas_laporan/'.$laporan->id_laporan.'/surat-uji.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 uji');

    BerkasPendukung::factory()->create([
        'id_laporan' => $laporan->id_laporan,
        'jenis_dokumen' => BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        'nama_file' => 'surat-cocok.pdf',
        'path_file' => $path,
        'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
    ]);

    $this->actingAs($pemohon)
        ->post(route('laporan.periksa_ai', $laporan))
        ->assertRedirect();

    $mentah = DB::table('analisis_ai')->pluck('hasil')->implode(' ');

    expect($mentah)->not->toMatch('/\b\d{16}\b/')
        ->and($mentah)->not->toContain(NIK_UJI)
        ->and(json_encode(session('analisis_ai')))->not->toContain(NIK_UJI);

    $ekstraksi = AnalisisAi::query()
        ->where('jenis', AnalisisAi::JENIS_EKSTRAKSI)
        ->firstOrFail()
        ->hasil;

    expect($ekstraksi['kolom']['nik']['nilai'])->toBe('347103120355****')
        ->and($ekstraksi['kolom']['nik']['hash'])->toBe(PengindeksNik::hash(NIK_UJI));

    $perbandingan = AnalisisAi::query()
        ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
        ->firstOrFail()
        ->hasil;

    $barisNik = collect($perbandingan['kolom'])->firstWhere('kunci', 'nik');

    expect($barisNik['nilai_pemohon'])->toBe('347103120355****')
        ->and($barisNik['nilai_ekstraksi'])->toBe('347103120355****')
        ->and($barisNik['hash_pemohon'])->toBe(PengindeksNik::hash(NIK_UJI))
        ->and($barisNik['hash_ekstraksi'])->toBe(PengindeksNik::hash(NIK_UJI))
        ->and($barisNik['status'])->toBe('cocok');
});

it('menyamarkan hasil analisis lama secara idempoten', function () {
    $hasil = [
        'kolom' => [
            'nik' => ['nilai' => NIK_UJI, 'keyakinan' => 0.99],
        ],
    ];

    $sekali = NikAnalisis::samarkanEkstraksi($hasil);
    $duaKali = NikAnalisis::samarkanEkstraksi($sekali);

    expect($sekali['kolom']['nik']['nilai'])->toBe('347103120355****')
        ->and($sekali['kolom']['nik']['hash'])->toBe(PengindeksNik::hash(NIK_UJI))
        ->and($duaKali)->toBe($sekali)
        ->and(json_encode($sekali))->not->toContain(NIK_UJI);
});

it('menghasilkan NIK terenkripsi dari factory dan seeder', function () {
    // DatabaseSeeder sengaja diuji: dulu memakai WithoutModelEvents sehingga
    // blind index tidak terisi.
    $this->seed(DatabaseSeeder::class);

    $pengguna = Pengguna::factory()->pemohon()->create(['nik' => NIK_UJI]);
    $laporan = LaporanKematian::factory()->create(['id_pengguna' => $pengguna->id_pengguna]);

    foreach ([
        ['pengguna', 'id_pengguna', $pengguna->id_pengguna, 'nik', 'nik_hash'],
        ['laporan_kematian', 'id_laporan', $laporan->id_laporan, 'nik_almarhum', 'nik_almarhum_hash'],
    ] as [$tabel, $kunci, $id, $kolom, $hash]) {
        $baris = DB::table($tabel)->where($kunci, $id)->first();

        expect((string) $baris->{$kolom})->not->toMatch('/^\d{16}$/')
            ->and(Crypt::decryptString((string) $baris->{$kolom}))->toMatch('/^\d{16}$/')
            ->and($baris->{$hash})->not->toBeNull();
    }

    $mentah = DB::table('pengguna')->whereNotNull('nik')->get(['nik', 'nik_hash'])
        ->merge(DB::table('laporan_kematian')->whereNotNull('nik_almarhum')->get(['nik_almarhum as nik', 'nik_almarhum_hash as nik_hash']));

    expect($mentah)->not->toBeEmpty();

    foreach ($mentah as $baris) {
        expect((string) $baris->nik)->not->toMatch('/^\d{16}$/')
            ->and($baris->nik_hash)->not->toBeNull();
    }
});
