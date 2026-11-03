<?php

/**
 * Pengujian backfill NIK (Fase 9b).
 *
 * Baris plaintext sengaja disisipkan lewat `DB::table` untuk meniru data lama,
 * lalu `PenyelarasNik` dijalankan seperti pada migrasi.
 */

use App\Support\PengindeksNik;
use App\Support\PenyelarasNik;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Sisipkan satu pengguna lama dengan NIK plaintext dan hash kosong.
 */
function sisipPenggunaNikLama(string $nik, string $email): int
{
    return (int) DB::table('pengguna')->insertGetId([
        'nama_lengkap' => 'Pengguna Lama',
        'nik' => $nik,
        'nik_hash' => null,
        'nip' => null,
        'email' => $email,
        'kata_sandi' => 'rahasia',
        'no_telepon' => null,
        'peran' => 'pemohon',
        'dibuat_pada' => now(),
        'diperbarui_pada' => now(),
    ], 'id_pengguna');
}

/**
 * Sisipkan satu laporan lama dengan NIK almarhum plaintext.
 */
function sisipLaporanNikLama(int $idPengguna, string $nik, string $nomor): int
{
    return (int) DB::table('laporan_kematian')->insertGetId([
        'id_pengguna' => $idPengguna,
        'no_pengajuan' => $nomor,
        'nama_pelapor' => 'Pelapor Lama',
        'hubungan_pelapor' => 'Anak Kandung',
        'nama_almarhum' => 'Almarhum Lama',
        'nik_almarhum' => $nik,
        'nik_almarhum_hash' => null,
        'tanggal_kematian' => '2026-10-01',
        'tempat_kematian' => 'Rumah',
        'status_pengajuan' => 'Menunggu Verifikasi',
        'tanggal_pengajuan' => now(),
        'dibuat_pada' => now(),
        'diperbarui_pada' => now(),
    ], 'id_laporan');
}

it('mengubah data NIK lama menjadi terenkripsi dan idempoten', function () {
    $nikA = '3471031203559001';
    $nikB = '3471031203559002';
    $nikC = '3471031203559003';

    $idA = sisipPenggunaNikLama($nikA, 'lama-a@demo.satuduka.id');
    $idB = sisipPenggunaNikLama($nikB, 'lama-b@demo.satuduka.id');
    $idLaporan = sisipLaporanNikLama($idA, $nikC, 'SD-20261001-9001');

    DB::table('analisis_ai')->insert([
        [
            'id_laporan' => $idLaporan,
            'id_berkas' => null,
            'jenis' => 'ekstraksi',
            'hasil' => json_encode([
                'berhasil' => true,
                'kolom' => [
                    'nik' => ['nilai' => $nikC, 'keyakinan' => 0.99],
                ],
            ]),
            'skor' => 0.99,
            'model' => 'mock-ai-v1',
            'status' => 'berhasil',
            'hash_berkas' => null,
            'dibuat_pada' => now(),
        ],
        [
            'id_laporan' => $idLaporan,
            'id_berkas' => null,
            'jenis' => 'perbandingan',
            'hasil' => json_encode([
                'kolom' => [[
                    'kunci' => 'nik',
                    'label' => 'NIK',
                    'nilai_pemohon' => $nikC,
                    'nilai_ekstraksi' => $nikC,
                    'nilai_siak' => $nikC,
                    'status' => 'cocok',
                    'keyakinan' => 0.99,
                    'catatan' => [],
                ]],
            ]),
            'skor' => 0.99,
            'model' => 'mock-ai-v1',
            'status' => 'berhasil',
            'hash_berkas' => null,
            'dibuat_pada' => now(),
        ],
    ]);

    $pertama = PenyelarasNik::jalankan();

    expect($pertama['pengguna'])->toBe(2)
        ->and($pertama['laporan_kematian'])->toBe(1)
        ->and($pertama['analisis_ai'])->toBe(2);

    $barisA = DB::table('pengguna')->where('id_pengguna', $idA)->first();
    $barisB = DB::table('pengguna')->where('id_pengguna', $idB)->first();
    $barisLaporan = DB::table('laporan_kematian')->where('id_laporan', $idLaporan)->first();

    expect((string) $barisA->nik)->not->toBe($nikA)
        ->and(Crypt::decryptString((string) $barisA->nik))->toBe($nikA)
        ->and($barisA->nik_hash)->toBe(PengindeksNik::hash($nikA))
        ->and(Crypt::decryptString((string) $barisB->nik))->toBe($nikB)
        ->and($barisB->nik_hash)->toBe(PengindeksNik::hash($nikB))
        ->and(Crypt::decryptString((string) $barisLaporan->nik_almarhum))->toBe($nikC)
        ->and($barisLaporan->nik_almarhum_hash)->toBe(PengindeksNik::hash($nikC));

    $analisis = DB::table('analisis_ai')->orderBy('id_analisis')->get();

    expect($analisis[0]->hasil)->not->toContain($nikC)
        ->and(json_decode($analisis[0]->hasil, true)['kolom']['nik']['nilai'])->toBe('347103120355****')
        ->and(json_decode($analisis[0]->hasil, true)['kolom']['nik']['hash'])->toBe(PengindeksNik::hash($nikC))
        ->and($analisis[1]->hasil)->not->toContain($nikC)
        ->and(json_decode($analisis[1]->hasil, true)['kolom'][0]['hash_pemohon'])->toBe(PengindeksNik::hash($nikC));

    // Dijalankan ulang: tidak ada baris yang berubah lagi.
    $kedua = PenyelarasNik::jalankan();

    expect($kedua)->toBe([
        'pengguna' => 0,
        'laporan_kematian' => 0,
        'analisis_ai' => 0,
    ]);
});

it('membatalkan seluruh chunk bila gagal di tengah lalu berhasil saat dijalankan ulang', function () {
    $nikA = '3471031203559004';
    $nikB = '3471031203559005';
    $nikC = '3471031203559006';

    $idA = sisipPenggunaNikLama($nikA, 'gagal-a@demo.satuduka.id');
    $idB = sisipPenggunaNikLama($nikB, 'gagal-b@demo.satuduka.id');
    $idC = sisipPenggunaNikLama($nikC, 'gagal-c@demo.satuduka.id');
    $idLaporan = sisipLaporanNikLama($idA, $nikC, 'SD-20261001-9002');

    $gagal = false;

    try {
        PenyelarasNik::jalankan(sebelumBaris: function (string $tabel, int|string $id) use ($idB, &$gagal): void {
            if ($tabel === 'pengguna' && (int) $id === $idB) {
                $gagal = true;
                throw new RuntimeException('Kegagalan uji di tengah chunk');
            }
        });
    } catch (RuntimeException) {
        // Diharapkan.
    }

    expect($gagal)->toBeTrue();

    // Transaksi chunk dibatalkan: belum ada baris yang berubah.
    $mentah = DB::table('pengguna')
        ->whereIn('id_pengguna', [$idA, $idB, $idC])
        ->pluck('nik')
        ->all();

    expect($mentah)->toContain($nikA)
        ->and($mentah)->toContain($nikB)
        ->and($mentah)->toContain($nikC)
        ->and(DB::table('laporan_kematian')->where('id_laporan', $idLaporan)->value('nik_almarhum'))
        ->toBe($nikC);

    // Jalankan ulang tanpa gangguan: seluruh data selesai dikonversi.
    PenyelarasNik::jalankan();

    $baru = DB::table('pengguna')
        ->whereIn('id_pengguna', [$idA, $idB, $idC])
        ->orderBy('id_pengguna')
        ->get();

    expect($baru)->toHaveCount(3);

    foreach ($baru as $baris) {
        expect((string) $baris->nik)->not->toMatch('/^\d{16}$/')
            ->and(Crypt::decryptString((string) $baris->nik))->toMatch('/^\d{16}$/')
            ->and($baris->nik_hash)->not->toBeNull();
    }

    expect(DB::table('laporan_kematian')->where('id_laporan', $idLaporan)->value('nik_almarhum_hash'))->not->toBeNull();
});

it('menjalankan perintah enkripsi ulang dengan pratinjau tanpa membocorkan NIK', function () {
    $nik = '3471031203559007';
    sisipPenggunaNikLama($nik, 'perintah@demo.satuduka.id');

    Artisan::call('nik:enkripsi-ulang', ['--dry-run' => true]);

    $keluaran = Artisan::output();

    expect($keluaran)->toContain('Pratinjau')
        ->and($keluaran)->toContain('pengguna')
        ->and($keluaran)->not->toContain($nik)
        ->and($keluaran)->not->toContain((string) config('app.key'));

    Artisan::call('nik:enkripsi-ulang');

    expect(Artisan::output())->toContain('Selesai')
        ->and(DB::table('pengguna')->where('email', 'perintah@demo.satuduka.id')->value('nik_hash'))
        ->toBe(PengindeksNik::hash($nik));
});
