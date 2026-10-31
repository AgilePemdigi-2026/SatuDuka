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
];
