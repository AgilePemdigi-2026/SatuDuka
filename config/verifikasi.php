<?php

use App\Models\BerkasPendukung;

/*
|--------------------------------------------------------------------------
| Konfigurasi Verifikasi dan Rekonsiliasi
|--------------------------------------------------------------------------
|
| Seluruh ambang, daftar berkas, dan bobot aturan diletakkan di sini agar
| mudah diubah tanpa menyentuh kode aturan. Mesin aturan hanya mendeteksi
| dan merekomendasikan; status pengajuan tetap diubah oleh pengguna.
|
*/

$nikDemo = env('VERIFIKASI_NIK_DEMO', env('APP_ENV', 'production') === 'local');

return [
    /*
    |--------------------------------------------------------------------------
    | Batas Pelaporan
    |--------------------------------------------------------------------------
    */

    'batas_hari_pelaporan' => 30,

    /*
    |--------------------------------------------------------------------------
    | Daftar Berkas
    |--------------------------------------------------------------------------
    |
    | Bukti kematian boleh berupa salah satu dari tiga dokumen. Berkas lain
    | pada kelompok "lainnya" wajib. Saksi bersifat opsional secara default.
    |
    */

    'berkas_wajib' => [
        'bukti_kematian' => [
            'minimal_satu' => true,
            'jenis' => [
                BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
                BerkasPendukung::JENIS_SURAT_KETERANGAN_KELURAHAN,
                BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN,
            ],
        ],
        'lainnya' => [
            BerkasPendukung::JENIS_KTP_ALMARHUM,
            BerkasPendukung::JENIS_KARTU_KELUARGA,
            BerkasPendukung::JENIS_KTP_PELAPOR,
        ],
    ],

    'berkas_opsional' => [
        BerkasPendukung::JENIS_KTP_SAKSI_1,
        BerkasPendukung::JENIS_KTP_SAKSI_2,
    ],

    'wajib_saksi' => false,

    'berkas_bersyarat' => [
        'keterlambatan' => [
            BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN,
        ],
        'jenis_kasus_khusus' => [
            BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Normalisasi dan Pencocokan Nama
    |--------------------------------------------------------------------------
    */

    'nama_cocok_persis' => 1.0,

    'nama_beda_tipis' => 0.80,

    /*
    |--------------------------------------------------------------------------
    | Normalisasi Nama
    |--------------------------------------------------------------------------
    |
    | Gelar hanya dibuang bila berdiri sendiri di awal atau akhir nama. Token
    | pendek "r" dan "rm" baru dianggap gelar bila masih ada cukup token nama
    | setelahnya, sehingga "R. Farhan" tetap terbaca sebagai inisial.
    |
    */

    'nama_gelar_depan' => [
        'dr',
        'drg',
        'drs',
        'dra',
        'ir',
        'prof',
        'h',
        'hj',
        'kh',
        'k.h',
    ],

    'nama_gelar_belakang' => [
        's.pd',
        's.kom',
        's.h',
        's.t',
        'm.m',
        'm.pd',
        'm.si',
        'm.sc',
        'ph.d',
        'a.md',
        's.sos',
        's.ak',
        'm.ak',
        'm.kes',
        's.ked',
        's.farm',
        'm.farm',
        's.psi',
        'm.psi',
        's.ip',
        's.ab',
        'm.h',
        'm.t',
        'm.kom',
        's.ag',
        'm.ag',
        's.si',
        's.kep',
        'ns',
        'amd',
        'dr',
        'drg',
    ],

    'nama_gelar_awal_pendek' => ['r', 'rm'],

    'nama_gelar_awal_pendek_min_sisa' => 2,

    'nama_ejaan_lama' => [
        'oe' => 'u',
        'dj' => 'j',
        'tj' => 'c',
    ],

    'nama_penalti_inisial' => 0.05,

    'nama_penalti_nama_tunggal' => 0.15,

    /*
    |--------------------------------------------------------------------------
    | Data Demo dan Batas Usia
    |--------------------------------------------------------------------------
    */

    'izinkan_nik_demo' => filter_var($nikDemo, FILTER_VALIDATE_BOOLEAN),

    'batas_usia' => 120,

    /*
    |--------------------------------------------------------------------------
    | Jenis Kasus
    |--------------------------------------------------------------------------
    */

    'jenis_kasus' => [
        'Normal',
        'Jenazah Tidak Dikenal',
        'Jenazah Tidak Ditemukan',
    ],
];
