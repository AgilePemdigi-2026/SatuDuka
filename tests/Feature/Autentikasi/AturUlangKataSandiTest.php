<?php

/**
 * Pengujian pengaturan ulang kata sandi (use case 5.2.2 alur 4.a).
 *
 * Tautan dikirim ke surel TERDAFTAR yang dicari dari NIK/NIP yang diketik
 * (MAIL_MAILER=log untuk demo).
 */

use App\Models\LogAudit;
use App\Notifications\TautanAturUlangKataSandi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('menampilkan formulir lupa kata sandi dengan kolom nik/nip', function () {
    $this->get(route('lupa_kata_sandi'))
        ->assertOk()
        ->assertSee('Lupa Kata Sandi')
        ->assertSee('NIK/NIP');
});

it('mengirim tautan pengaturan ulang ke surel terdaftar akun', function () {
    Notification::fake();
    [$pemohon] = buatAkunDemo();

    $this->post(route('lupa_kata_sandi.kirim'), [
        'identitas' => '9999990101900001',
    ])
        ->assertRedirect()
        ->assertSessionHas('sukses', __('passwords.sent'));

    Notification::assertSentTo($pemohon, TautanAturUlangKataSandi::class);

    expect(LogAudit::where('aksi', 'minta_atur_ulang_kata_sandi')
        ->where('id_pengguna', $pemohon->id_pengguna)
        ->exists())->toBeTrue();
});

it('menolak identitas yang tidak ditemukan pada formulir lupa kata sandi', function () {
    buatAkunDemo();

    $this->post(route('lupa_kata_sandi.kirim'), [
        'identitas' => '9999990101900099',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['identitas' => 'NIK/NIP tidak ditemukan']);
});

it('menyimpan kata sandi baru dari tautan surel lalu mengarahkan ke halaman masuk', function () {
    [$pemohon] = buatAkunDemo();

    $token = Password::broker()->createToken($pemohon);

    $this->get(route('atur_ulang_kata_sandi', ['token' => $token, 'email' => $pemohon->email]))
        ->assertOk()
        ->assertSee('Kata Sandi Baru');

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
