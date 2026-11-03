<?php

/**
 * Pengujian keamanan dan tampilan formulir autentikasi.
 *
 * Kata sandi tidak boleh ikut tersimpan pada old input ketika validasi gagal,
 * dan pesan error per field hanya boleh tampil sekali (inline).
 */
it('tidak menyimpan kata sandi registrasi pada old input saat validasi gagal', function () {
    $this->get(route('registrasi'));

    $respon = $this->post(route('registrasi.simpan'), [
        'email' => 'pemohon.baru@demo.satuduka.id',
        'kata_sandi' => 'RahasiaUji123',
        'konfirmasi_kata_sandi' => 'RahasiaUji123',
    ]);

    $respon->assertRedirect(route('registrasi'))
        ->assertSessionMissing('_old_input.kata_sandi')
        ->assertSessionMissing('_old_input.konfirmasi_kata_sandi');

    $this->followRedirects($respon)
        ->assertOk()
        ->assertDontSee('RahasiaUji123');
});

it('tidak menyimpan kata sandi masuk pada old input saat kredensial salah', function () {
    buatAkunDemo();

    $this->get(route('masuk'));

    $respon = $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => 'RahasiaUji123',
    ]);

    $respon->assertRedirect(route('masuk'))
        ->assertSessionMissing('_old_input.kata_sandi');

    $this->followRedirects($respon)
        ->assertOk()
        ->assertDontSee('RahasiaUji123');
});

it('tidak menyimpan kata sandi atur ulang pada old input saat token tidak valid', function () {
    [$pemohon] = buatAkunDemo();

    $alamat = route('atur_ulang_kata_sandi', [
        'token' => 'token-palsu',
        'email' => $pemohon->email,
    ]);

    $this->get($alamat);

    $respon = $this->post(route('atur_ulang_kata_sandi.simpan'), [
        'token' => 'token-palsu',
        'email' => $pemohon->email,
        'kata_sandi' => 'RahasiaUji123',
        'konfirmasi_kata_sandi' => 'RahasiaUji123',
    ]);

    $respon->assertRedirect($alamat)
        ->assertSessionMissing('_old_input.kata_sandi')
        ->assertSessionMissing('_old_input.konfirmasi_kata_sandi');

    $this->followRedirects($respon)
        ->assertOk()
        ->assertDontSee('RahasiaUji123');
});

it('menampilkan pesan kredensial salah tepat satu kali', function () {
    buatAkunDemo();

    $this->get(route('masuk'));

    $respon = $this->followRedirects($this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => 'password124',
    ]));

    $respon->assertOk();

    expect(substr_count($respon->getContent(), 'NIK/NIP atau kata sandi salah'))->toBe(1);
});
