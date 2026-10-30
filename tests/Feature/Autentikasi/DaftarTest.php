<?php

/**
 * Pengujian fitur Registrasi.
 *
 * Sumber skenario: SRS Bab VI Tabel 6.2.
 * Setiap blok pengujian memetakan satu skenario Gherkin.
 */

use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Support\DataSIAK;
use Illuminate\Support\Facades\Hash;

// Skenario: Berpindah ke halaman Masuk dari halaman Registrasi.
it('menampilkan halaman registrasi dengan tautan ke halaman masuk', function () {
    $this->get(route('registrasi'))
        ->assertOk()
        ->assertSee('Registrasi Akun Pemohon')
        ->assertSee('Nomor Induk Kependudukan (NIK)')
        ->assertSee('Alamat Email')
        ->assertSee(route('masuk'));
});

// Skenario: Berhasil registrasi dengan NIK yang terdapat pada data kependudukan.
it('menyimpan akun pemohon baru dan mengarahkan ke halaman masuk', function () {
    $nik = '3471031907459015';

    $this->post(route('registrasi.simpan'), [
        'nik' => $nik,
        'email' => 'pemohon.baru@demo.satuduka.id',
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])
        ->assertRedirect(route('masuk'))
        ->assertSessionHas('sukses', 'Registrasi berhasil!');

    $pengguna = Pengguna::cariByNik($nik);

    expect($pengguna)->not->toBeNull()
        ->and($pengguna->nama_lengkap)->toBe(DataSIAK::cariNik($nik)['nama_lengkap'])
        ->and($pengguna->email)->toBe('pemohon.baru@demo.satuduka.id')
        ->and($pengguna->peran)->toBe('pemohon')
        ->and(Hash::check('Baru12345', $pengguna->kata_sandi))->toBeTrue();

    // Registrasi wajib tercatat ke log_audit.
    expect(LogAudit::where('aksi', 'registrasi')->where('id_pengguna', $pengguna->id_pengguna)->exists())->toBeTrue();
});

// Skenario: Registrasi gagal karena kata sandi lemah.
it('menolak kata sandi yang tidak memenuhi nfr-01', function () {
    $this->post(route('registrasi.simpan'), [
        'nik' => '3404016511599014',
        'email' => 'pemohon.baru@demo.satuduka.id',
        'kata_sandi' => 'abc123',
        'konfirmasi_kata_sandi' => 'abc123',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors([
            'kata_sandi' => 'Kata sandi minimal 8 karakter dan mengandung huruf serta angka',
        ]);

    expect(Pengguna::count())->toBe(0);
});

// Skenario: Registrasi gagal karena konfirmasi kata sandi tidak cocok.
it('menolak konfirmasi kata sandi yang tidak cocok', function () {
    $this->post(route('registrasi.simpan'), [
        'nik' => '3404016511599014',
        'email' => 'pemohon.baru@demo.satuduka.id',
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12346',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['konfirmasi_kata_sandi' => 'Kata sandi tidak cocok']);

    expect(Pengguna::count())->toBe(0);
});

// Skenario: Registrasi gagal karena format NIK tidak valid.
it('menolak nik dengan format tidak valid', function () {
    $this->post(route('registrasi.simpan'), [
        'nik' => '123456789012',
        'email' => 'pemohon.baru@demo.satuduka.id',
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['nik' => 'NIK tidak valid']);

    expect(Pengguna::count())->toBe(0);
});

// Skenario: Registrasi gagal karena NIK sudah terdaftar.
it('menolak nik yang sudah terdaftar', function () {
    Pengguna::factory()->pemohon()->create([
        'nik' => '9999990101900001',
        'email' => 'pemohon@demo.satuduka.id',
    ]);

    $this->post(route('registrasi.simpan'), [
        'nik' => '9999990101900001',
        'email' => 'pemohon.baru@demo.satuduka.id',
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['nik' => 'NIK sudah terdaftar']);
});

// Skenario: Registrasi gagal karena NIK tidak ditemukan pada data kependudukan.
it('menolak nik yang tidak ditemukan pada data kependudukan', function () {
    $this->post(route('registrasi.simpan'), [
        'nik' => '9999990101900099',
        'email' => 'pemohon.baru@demo.satuduka.id',
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['nik' => 'NIK tidak ditemukan pada data kependudukan']);

    expect(Pengguna::count())->toBe(0);
});

// Skenario: Registrasi gagal karena surel sudah terdaftar.
it('menolak surel yang sudah terdaftar', function () {
    Pengguna::factory()->pemohon()->create([
        'nik' => '9999990101900001',
        'email' => 'pemohon@demo.satuduka.id',
    ]);

    $this->post(route('registrasi.simpan'), [
        'nik' => '3404016511599014',
        'email' => 'pemohon@demo.satuduka.id',
        'kata_sandi' => 'Baru12345',
        'konfirmasi_kata_sandi' => 'Baru12345',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['email' => 'Surel sudah terdaftar']);
});

// Skenario: Registrasi gagal karena kolom kosong.
it('menampilkan pesan kolom kosong dan menandai kolom tersebut', function () {
    // Kunjungi halaman registrasi dahulu agar redirect-back kembali ke sini.
    $this->get(route('registrasi'));

    $this->followRedirects($this->post(route('registrasi.simpan'), [
        'nik' => '3404016511599014',
        'email' => 'pemohon.baru@demo.satuduka.id',
        'kata_sandi' => '',
        'konfirmasi_kata_sandi' => '',
    ]))
        ->assertOk()
        ->assertSee('Harap isi semua field yang diperlukan')
        ->assertSee('aria-invalid="true"', false);

    expect(Pengguna::count())->toBe(0);
});
