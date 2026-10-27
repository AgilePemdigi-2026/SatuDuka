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
