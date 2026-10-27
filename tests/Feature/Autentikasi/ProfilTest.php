<?php

/**
 * Pengujian pengelolaan profil (use case 5.2.4 dan user story 6.2.4).
 */

use App\Models\LogAudit;
use App\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat berkas PNG 1x1 yang sah tanpa memerlukan ekstensi GD.
 */
function berkasFotoPng(string $nama = 'foto.png'): UploadedFile
{
    $isi = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    );

    return UploadedFile::fake()->createWithContent($nama, $isi);
}

it('menampilkan halaman kelola profil dengan data diri dan NIK tidak dapat diubah', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->get(route('profil.tampilkan'))
        ->assertOk()
        ->assertSee('Kelola Profil')
        ->assertSee('Foto Profil')
        ->assertSee('Data Diri')
        ->assertSee('Pengaturan Keamanan')
        ->assertSee('9999990101900001')
        ->assertSee('readonly', false);
});

it('menampilkan NIP yang tidak dapat diubah bagi verifikator dan pejabat', function () {
    [, $verifikator, $pejabat] = buatAkunDemo();

    $this->actingAs($verifikator)
        ->get(route('profil.tampilkan'))
        ->assertOk()
        ->assertSee('999999991234567890')
        ->assertSee('readonly', false);

    $this->actingAs($pejabat)
        ->get(route('profil.tampilkan'))
        ->assertOk()
        ->assertSee('999999992345678901')
        ->assertSee('readonly', false);
});

it('memperbarui data diri dan hanya mencatat nama kolom yang berubah', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->put(route('profil.simpan'), [
            'nama_lengkap' => 'Nama Baru Pemohon',
            'email' => 'pemohon.baru@contoh.id',
            'no_telepon' => '081234567890',
            'alamat' => 'Jalan Malioboro Nomor 1',
        ])
        ->assertRedirect(route('profil.tampilkan'))
        ->assertSessionHas('sukses', 'Profil berhasil diperbarui.');

    $segar = $pemohon->fresh();

    expect($segar->nama_lengkap)->toBe('Nama Baru Pemohon')
        ->and($segar->email)->toBe('pemohon.baru@contoh.id')
        ->and($segar->no_telepon)->toBe('081234567890')
        ->and($segar->alamat)->toBe('Jalan Malioboro Nomor 1')
        ->and($segar->nik)->toBe('9999990101900001');

    $log = LogAudit::where('aksi', 'memperbarui_profil')->latest('id_log')->first();

    expect($log)->not->toBeNull()
        ->and($log->detail['kolom'])->toContain('nama_lengkap', 'email', 'no_telepon', 'alamat')
        ->and(json_encode($log->detail))->not->toContain('Nama Baru Pemohon')
        ->and(json_encode($log->detail))->not->toContain('pemohon.baru@contoh.id');
});

it('menolak format email dan nomor telepon yang tidak valid', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->put(route('profil.simpan'), [
            'nama_lengkap' => $pemohon->nama_lengkap,
            'email' => 'bukan-email',
            'no_telepon' => 'telepon-salah',
        ])
        ->assertSessionHasErrors([
            'email' => 'Format alamat surel tidak valid',
            'no_telepon' => 'Format nomor telepon tidak valid',
        ]);

    expect($pemohon->fresh()->email)->toBe('pemohon@demo.satuduka.id');
});

it('menolak email atau nomor telepon milik pengguna lain', function () {
    [$pemohon, $verifikator] = buatAkunDemo();
    $verifikator->update(['no_telepon' => '081298765432']);

    $this->actingAs($pemohon)
        ->put(route('profil.simpan'), [
            'nama_lengkap' => $pemohon->nama_lengkap,
            'email' => $verifikator->email,
            'no_telepon' => '081298765432',
        ])
        ->assertSessionHasErrors([
            'email' => 'Email / Nomor Telepon sudah digunakan oleh pengguna lain.',
            'no_telepon' => 'Email / Nomor Telepon sudah digunakan oleh pengguna lain.',
        ]);

    expect($pemohon->fresh()->email)->toBe('pemohon@demo.satuduka.id')
        ->and($pemohon->fresh()->no_telepon)->not->toBe('081298765432');
});

it('mengizinkan pengguna menyimpan email dan nomor teleponnya sendiri', function () {
    [$pemohon] = buatAkunDemo();
    $pemohon->update(['no_telepon' => '081211112222']);

    $this->actingAs($pemohon)
        ->put(route('profil.simpan'), [
            'nama_lengkap' => $pemohon->nama_lengkap,
            'email' => $pemohon->email,
            'no_telepon' => '081211112222',
        ])
        ->assertRedirect(route('profil.tampilkan'))
        ->assertSessionHas('sukses', 'Profil berhasil diperbarui.');
});

it('mengabaikan NIK dan NIP yang dikirim pada perubahan profil', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->put(route('profil.simpan'), [
            'nama_lengkap' => $pemohon->nama_lengkap,
            'email' => $pemohon->email,
            'nik' => '1111111111111111',
            'nip' => '111111111111111111',
        ])
        ->assertRedirect(route('profil.tampilkan'));

    expect($pemohon->fresh()->nik)->toBe('9999990101900001')
        ->and($pemohon->fresh()->nip)->toBeNull();
});

it('hanya memperbarui profil pengguna yang sedang masuk', function () {
    [$pemohon, $verifikator] = buatAkunDemo();

    $namaVerifikator = $verifikator->nama_lengkap;
    $emailVerifikator = $verifikator->email;

    $this->actingAs($pemohon)
        ->put(route('profil.simpan'), [
            'nama_lengkap' => 'Pemohon Diubah',
            'email' => 'pemohon.diubah@contoh.id',
        ])
        ->assertRedirect(route('profil.tampilkan'));

    expect($pemohon->fresh()->nama_lengkap)->toBe('Pemohon Diubah')
        ->and($verifikator->fresh()->nama_lengkap)->toBe($namaVerifikator)
        ->and($verifikator->fresh()->email)->toBe($emailVerifikator);
});

it('menolak foto yang bukan JPG/PNG atau melebihi 2 MB dan mempertahankan foto lama', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();

    Storage::disk('local')->put('foto_profil/lama.jpg', 'foto lama');
    $pemohon->update(['path_foto' => 'foto_profil/lama.jpg']);

    $pesan = 'Ukuran berkas melebihi batas (maks. 2MB) atau format tidak didukung (gunakan JPG/PNG).';

    $this->actingAs($pemohon)
        ->post(route('profil.foto'), [
            'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['foto' => $pesan]);

    $isiPng = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    );

    $this->actingAs($pemohon)
        ->post(route('profil.foto'), [
            'foto' => UploadedFile::fake()->createWithContent('besar.png', $isiPng.str_repeat('0', 3 * 1024 * 1024)),
        ])
        ->assertSessionHasErrors(['foto' => $pesan]);

    expect($pemohon->fresh()->path_foto)->toBe('foto_profil/lama.jpg')
        ->and(Storage::disk('local')->exists('foto_profil/lama.jpg'))->toBeTrue();
});

it('mengunggah foto profil baru dan menghapus foto lama', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();

    Storage::disk('local')->put('foto_profil/lama.jpg', 'foto lama');
    $pemohon->update(['path_foto' => 'foto_profil/lama.jpg']);

    $this->actingAs($pemohon)
        ->post(route('profil.foto'), ['foto' => berkasFotoPng()])
        ->assertRedirect(route('profil.tampilkan'));

    $segar = $pemohon->fresh();

    expect($segar->path_foto)->not->toBeNull()
        ->and($segar->path_foto)->not->toBe('foto_profil/lama.jpg')
        ->and(Storage::disk('local')->exists($segar->path_foto))->toBeTrue()
        ->and(Storage::disk('local')->exists('foto_profil/lama.jpg'))->toBeFalse();

    $this->actingAs($pemohon)
        ->get(route('profil.foto.tampil'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    expect(LogAudit::where('aksi', 'mengubah_foto_profil')->exists())->toBeTrue();
});

it('menampilkan inisial saat foto profil belum ada', function () {
    Storage::fake('local');
    [$pemohon] = buatAkunDemo();

    $respons = $this->actingAs($pemohon)->get(route('profil.foto.tampil'));

    $respons->assertOk();
    expect($respons->headers->get('Content-Type'))->toContain('image/svg+xml');
    $respons->assertSee('PD');
});

it('mengubah kata sandi setelah verifikasi kata sandi lama', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->put(route('profil.kata_sandi'), [
            'kata_sandi_lama' => 'password123',
            'kata_sandi_baru' => 'sandiBaru1',
            'konfirmasi_kata_sandi' => 'sandiBaru1',
        ])
        ->assertRedirect(route('profil.tampilkan'))
        ->assertSessionHas('sukses', 'Kata sandi berhasil diperbarui');

    $segar = $pemohon->fresh();

    expect(Hash::check('sandiBaru1', $segar->kata_sandi))->toBeTrue()
        ->and(Hash::check('password123', $segar->kata_sandi))->toBeFalse();

    $log = LogAudit::where('aksi', 'mengubah_kata_sandi')->latest('id_log')->first();

    expect($log)->not->toBeNull()
        ->and($log->detail)->toBeNull()
        ->and(json_encode($log->getAttributes()))->not->toContain('sandiBaru1');
});

it('menolak kata sandi lama yang salah', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->put(route('profil.kata_sandi'), [
            'kata_sandi_lama' => 'sandiSalah1',
            'kata_sandi_baru' => 'sandiBaru1',
            'konfirmasi_kata_sandi' => 'sandiBaru1',
        ])
        ->assertSessionHasErrors(['kata_sandi_lama' => 'Kata sandi lama tidak sesuai']);

    expect(Hash::check('password123', $pemohon->fresh()->kata_sandi))->toBeTrue();
});

it('menolak kata sandi baru yang lemah', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->put(route('profil.kata_sandi'), [
            'kata_sandi_lama' => 'password123',
            'kata_sandi_baru' => 'abc',
            'konfirmasi_kata_sandi' => 'abc',
        ])
        ->assertSessionHasErrors([
            'kata_sandi_baru' => 'Kata sandi minimal 8 karakter dan harus mengandung huruf serta angka',
        ]);

    expect(Hash::check('password123', $pemohon->fresh()->kata_sandi))->toBeTrue();
});

it('menolak konfirmasi kata sandi yang tidak cocok', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->put(route('profil.kata_sandi'), [
            'kata_sandi_lama' => 'password123',
            'kata_sandi_baru' => 'sandiBaru1',
            'konfirmasi_kata_sandi' => 'sandiLain2',
        ])
        ->assertSessionHasErrors(['konfirmasi_kata_sandi' => 'Konfirmasi kata sandi tidak cocok']);

    expect(Hash::check('password123', $pemohon->fresh()->kata_sandi))->toBeTrue();
});

it('mengalihkan tamu dari halaman profil ke halaman masuk', function () {
    $this->get(route('profil.tampilkan'))->assertRedirect(route('masuk'));
    $this->put(route('profil.simpan'), [])->assertRedirect(route('masuk'));
});

it('mengalihkan sesi kedaluwarsa ke halaman masuk dengan pesan', function () {
    Route::get('/uji-sesi-kedaluwarsa', fn () => throw new TokenMismatchException);

    $this->get('/uji-sesi-kedaluwarsa')
        ->assertRedirect(route('masuk'))
        ->assertSessionHas('galat', 'Sesi Anda telah berakhir. Silakan login kembali.');
});

it('menampilkan tautan kelola profil pada dashboard setiap peran', function () {
    [$pemohon, $verifikator, $pejabat] = buatAkunDemo();

    foreach ([$pemohon, $verifikator, $pejabat] as $pengguna) {
        $this->actingAs($pengguna)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelola Profil')
            ->assertSee(route('profil.tampilkan'));
    }

    expect(Pengguna::count())->toBe(3);
});
