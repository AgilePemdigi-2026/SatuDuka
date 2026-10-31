<?php

/*
|--------------------------------------------------------------------------
| Identitas Merek dan Daerah
|--------------------------------------------------------------------------
|
| Nama merek memakai config('app.name') agar hanya ada satu sumber.
| Nilai di bawah dipakai halaman tamu dan template dokumen simulasi.
|
*/

return [
    'tagline' => 'Sistem Kependudukan Digital',
    'layanan' => 'Layanan Pencatatan & Administrasi Kematian',
    'daerah' => 'Kota Yogyakarta',
    'instansi' => 'Pemerintah Kota Yogyakarta',
    'dinas' => 'Dinas Kependudukan dan Pencatatan Sipil',

    /*
    |--------------------------------------------------------------------------
    | Tanda Tangan Elektronik (TTE)
    |--------------------------------------------------------------------------
    |
    | PIN dummy hanya menjadi nilai bawaan pada lingkungan lokal/pengujian.
    | Di luar itu nilainya null sehingga tidak ada PIN yang sah sampai
    | penyedia TTE sungguhan dikonfigurasi. Driver mock ditolak pada produksi.
    |
    */

    'tte_driver' => env('SATUDUKA_TTE_DRIVER', 'mock'),

    'tte_pin_dummy' => env(
        'SATUDUKA_TTE_PIN_DUMMY',
        in_array((string) env('APP_ENV', 'production'), ['local', 'testing'], true) ? '123456' : null,
    ),

    /*
    |--------------------------------------------------------------------------
    | Batas Laju (Rate Limit) Rute Sensitif
    |--------------------------------------------------------------------------
    |
    | Nilai dalam "percobaan per menit". Semua batas dapat dilonggarkan untuk
    | demo/presentasi dengan SATUDUKA_RATE_LIMIT_LONGGAR=true, yang mengalikan
    | batas dengan `pengali_longgar`. Batas tetap ada agar tidak terbuka penuh.
    |
    */

    'rate_limit' => [
        'longgar' => filter_var(env('SATUDUKA_RATE_LIMIT_LONGGAR', false), FILTER_VALIDATE_BOOLEAN),
        'pengali_longgar' => 20,

        // 10/menit: penguncian akun (5 gagal) tetap tampil sebelum throttle.
        'masuk' => 10,
        'registrasi' => 5,
        'lupa_kata_sandi' => 3,
        'atur_ulang_kata_sandi' => 5,
        'unggah_berkas' => 20,
        'analisis_ai' => 10,
        'tandatangani' => 10,
        'terbitkan' => 5,
    ],
];
