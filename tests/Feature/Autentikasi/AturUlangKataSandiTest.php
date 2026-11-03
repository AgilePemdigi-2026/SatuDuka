<?php

/**
 * Pengujian pengaturan ulang kata sandi (use case 5.2.2 alur 4.a).
 *
 * Tautan dikirim ke surel TERDAFTAR yang dicari dari NIK/NIP yang diketik
 * (MAIL_MAILER=log untuk demo).
 */

use App\Models\LogAudit;
use App\Notifications\TautanAturUlangKataSandi;
use App\Services\LayananPenguncian;
use App\Support\PengindeksNik;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('menampilkan formulir lupa kata sandi dengan kolom nik/nip', function () {
    $this->get(route('lupa_kata_sandi'))
        ->assertOk()
        ->assertSee('Lupa Kata Sandi')
        ->assertSee('NIK / NIP');
});

it('mengirim tautan pengaturan ulang ke surel terdaftar akun', function () {
    Notification::fake();
    [$pemohon] = buatAkunDemo();

    $this->post(route('lupa_kata_sandi.kirim'), [
        'identitas' => '9999990101900001',
    ])
        ->assertRedirect()
        ->assertSessionHas('sukses', 'Jika data yang Anda masukkan terdaftar, tautan pengaturan ulang telah dikirim ke surel akun.');

    Notification::assertSentTo($pemohon, TautanAturUlangKataSandi::class);

    expect(LogAudit::where('aksi', 'minta_atur_ulang_kata_sandi')
        ->where('id_pengguna', $pemohon->id_pengguna)
        ->exists())->toBeTrue();
});

it('memberi pesan netral untuk identitas yang tidak ditemukan', function () {
    buatAkunDemo();

    $this->post(route('lupa_kata_sandi.kirim'), [
        'identitas' => '9999990101900099',
    ])
        ->assertRedirect()
        // Pesan netral: tidak membocorkan apakah identitas terdaftar.
        ->assertSessionHas('info', 'Jika data yang Anda masukkan terdaftar, tautan pengaturan ulang telah dikirim ke surel akun.')
        ->assertSessionMissing('errors');
});

it('menyimpan kata sandi baru dari tautan surel lalu mengarahkan ke halaman masuk', function () {
    [$pemohon] = buatAkunDemo();

    $token = Password::broker()->createToken($pemohon);

    $this->get(route('atur_ulang_kata_sandi', ['token' => $token, 'email' => $pemohon->email]))
        ->assertOk()
        ->assertSee('Atur Ulang Kata Sandi');

    $this->post(route('atur_ulang_kata_sandi.simpan'), [
        'token' => $token,
        'email' => $pemohon->email,
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])
        ->assertRedirect(route('masuk'))
        ->assertSessionHas('sukses', 'Kata sandi berhasil direset. Silakan login.');

    $pengguna = $pemohon->fresh();

    expect(Hash::check('Baru12345', $pengguna->kata_sandi))->toBeTrue()
        ->and(LogAudit::where('aksi', 'atur_ulang_kata_sandi')
            ->where('id_pengguna', $pemohon->id_pengguna)
            ->exists())->toBeTrue();

    // Kata sandi lama tidak lagi berlaku; yang baru bisa dipakai masuk.
    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => 'Baru12345',
    ])->assertRedirect(route('dashboard'));
});

it('menolak token pengaturan ulang yang tidak valid', function () {
    [$pemohon] = buatAkunDemo();

    $this->post(route('atur_ulang_kata_sandi.simpan'), [
        'token' => 'token-palsu',
        'email' => $pemohon->email,
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['email' => __('passwords.token')]);
});

it('menolak kata sandi baru yang tidak memenuhi nfr-01', function () {
    [$pemohon] = buatAkunDemo();

    $token = Password::broker()->createToken($pemohon);

    $this->post(route('atur_ulang_kata_sandi.simpan'), [
        'token' => $token,
        'email' => $pemohon->email,
        'kata_sandi' => 'abc123',
        'konfirmasi_kata_sandi' => 'abc123',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors([
            'kata_sandi' => 'Kata sandi minimal 8 karakter dan mengandung huruf serta angka',
        ]);
});

// (a) Saat permintaan ditahan (RESET_THROTTLED), pesan tetap netral agar
// keberadaan akun tidak bocor lewat perbedaan pesan.
it('menampilkan pesan netral yang sama saat permintaan tautan di-throttle', function () {
    Notification::fake();
    buatAkunDemo();

    $pesanNetral = 'Jika data yang Anda masukkan terdaftar, tautan pengaturan ulang telah dikirim ke surel akun.';

    // Permintaan pertama mengirim tautan.
    $this->post(route('lupa_kata_sandi.kirim'), ['identitas' => '9999990101900001'])
        ->assertSessionHas('sukses', $pesanNetral);

    // Permintaan kedua dalam jendela throttle: broker mengembalikan
    // RESET_THROTTLED, tetapi pengguna tetap melihat pesan netral yang sama.
    $this->post(route('lupa_kata_sandi.kirim'), ['identitas' => '9999990101900001'])
        ->assertSessionHas('sukses', $pesanNetral)
        ->assertSessionMissing('errors');
});

// (b) Reset sukses mencabut semua sesi lain dan memperbarui remember_token.
it('mencabut sesi lain dan memperbarui remember_token setelah reset berhasil', function () {
    [$pemohon] = buatAkunDemo();

    $pemohon->forceFill(['remember_token' => 'token-lama'])->save();
    $tokenLama = $pemohon->fresh()->remember_token;

    config(['session.driver' => 'database']);

    DB::table('sessions')->insert([
        ['id' => 'sesi-lama-1', 'user_id' => $pemohon->id_pengguna, 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'sesi-lama-2', 'user_id' => $pemohon->id_pengguna, 'payload' => 'x', 'last_activity' => now()->timestamp],
        ['id' => 'sesi-milik-orang-lain', 'user_id' => 999999, 'payload' => 'x', 'last_activity' => now()->timestamp],
    ]);

    $token = Password::broker()->createToken($pemohon);

    $this->post(route('atur_ulang_kata_sandi.simpan'), [
        'token' => $token,
        'email' => $pemohon->email,
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])->assertRedirect(route('masuk'));

    $pengguna = $pemohon->fresh();

    expect(DB::table('sessions')->where('user_id', $pemohon->id_pengguna)->count())->toBe(0)
        ->and(DB::table('sessions')->where('id', 'sesi-milik-orang-lain')->exists())->toBeTrue()
        ->and($pengguna->remember_token)->not->toBeNull()
        ->and($pengguna->remember_token)->not->toBe($tokenLama);
});

// Reset membuka kunci login untuk semua format identitas (kunci kanonik).
it('membuka kunci login untuk semua format setelah reset kata sandi', function () {
    [$pemohon] = buatAkunDemo();

    $kunci = 'login:9999990101900001';

    for ($i = 0; $i < LayananPenguncian::MAKS_GAGAL_LOGIN; $i++) {
        $this->post(route('masuk.coba'), [
            'identitas' => '9999990101900001',
            'kata_sandi' => 'password124',
        ])->assertSessionHasErrors();
    }

    expect(LayananPenguncian::terkunci($kunci))->toBeTrue()
        ->and(LayananPenguncian::jumlahGagal($kunci))->toBe(LayananPenguncian::MAKS_GAGAL_LOGIN);

    $token = Password::broker()->createToken($pemohon);

    $this->post(route('atur_ulang_kata_sandi.simpan'), [
        'token' => $token,
        'email' => $pemohon->email,
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])->assertRedirect(route('masuk'));

    foreach (['9999990101900001', '9999-9901-0190-0001', '9999 9901 0190 0001'] as $identitas) {
        $kunciFormat = 'login:'.PengindeksNik::angka($identitas);

        expect($kunciFormat)->toBe($kunci)
            ->and(LayananPenguncian::terkunci($kunciFormat))->toBeFalse()
            ->and(LayananPenguncian::jumlahGagal($kunciFormat))->toBe(0);
    }
});
