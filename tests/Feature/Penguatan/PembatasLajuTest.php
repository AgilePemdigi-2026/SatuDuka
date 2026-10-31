<?php

/**
 * Pengujian pembatas laju rute sensitif (Fase 9).
 *
 * Batas dibaca dari config/satuduka.php sehingga dapat diubah tanpa kode.
 */

use App\Models\Pengguna;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('memblokir percobaan masuk ke-(batas+1) dengan halaman 429 Indonesia', function () {
    config(['satuduka.rate_limit.masuk' => 2]);

    $identitas = '9999990101900001';

    for ($i = 0; $i < 2; $i++) {
        $this->post(route('masuk.coba'), [
            'identitas' => $identitas,
            'kata_sandi' => 'salah-total',
        ])->assertRedirect();
    }

    $this->post(route('masuk.coba'), [
        'identitas' => $identitas,
        'kata_sandi' => 'salah-total',
    ])
        ->assertStatus(429)
        ->assertSee('Terlalu Banyak Permintaan')
        ->assertSee('Silakan tunggu sebentar');
});

it('memblokir registrasi ke-(batas+1)', function () {
    config(['satuduka.rate_limit.registrasi' => 1]);

    $data = [
        'nik' => '9999990101900001',
        'email' => 'uji@demo.satuduka.id',
        'kata_sandi' => 'Rahasia123',
        'konfirmasi_kata_sandi' => 'Rahasia123',
    ];

    $this->post(route('registrasi.simpan'), $data)->assertRedirect();

    $this->post(route('registrasi.simpan'), $data)->assertStatus(429);
});

it('memblokir permintaan tautan kata sandi ke-(batas+1)', function () {
    config(['satuduka.rate_limit.lupa_kata_sandi' => 1]);

    Pengguna::factory()->pemohon()->create([
        'nik' => '9999990101900002',
        'email' => 'lupa@demo.satuduka.id',
    ]);

    $this->post(route('lupa_kata_sandi.kirim'), ['identitas' => '9999990101900002'])
        ->assertRedirect();

    $this->post(route('lupa_kata_sandi.kirim'), ['identitas' => '9999990101900002'])
        ->assertStatus(429);
});

it('melonggarkan batas lewat konfigurasi tanpa menghapusnya', function () {
    config([
        'satuduka.rate_limit.masuk' => 1,
        'satuduka.rate_limit.pengali_longgar' => 20,
    ]);

    // Limiter dibuat saat boot dengan pengali dari config saat itu; di sini
    // dipastikan nilai batas dasar tetap dibaca dinamis dari config.
    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900003',
        'kata_sandi' => 'salah',
    ])->assertRedirect();

    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900003',
        'kata_sandi' => 'salah',
    ])->assertStatus(429);
});
