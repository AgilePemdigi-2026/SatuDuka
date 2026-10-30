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
    | Taksonomi Anomali Mesin Rekonsiliasi
    |--------------------------------------------------------------------------
    |
    | Keparahan dan rekomendasi mengikuti docs/ATURAN_VERIFIKASI.md bagian 6.
    | Mesin hanya mendeteksi dan merekomendasikan; tidak menolak otomatis.
    |
    */

    'urutan_keparahan' => [
        'fatal',
        'tinggi',
        'sedang',
        'rendah',
        'info',
    ],

    'rekomendasi_utama_bersih' => 'Siap disetujui',

    'taksonomi_anomali' => [
        'PENGAJUAN_GANDA' => [
            'keparahan' => 'fatal',
            'rekomendasi' => 'Tolak anomali fatal atau minta verifikasi manual.',
        ],
        'SIAK_SUDAH_MENINGGAL' => [
            'keparahan' => 'fatal',
            'rekomendasi' => 'Rekonsiliasi data kependudukan; kemungkinan kematian sudah tercatat sebelumnya.',
        ],
        'KASUS_KHUSUS' => [
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Jalur manual; pastikan Surat Keterangan Kepolisian tersedia.',
        ],
        'NIK_TIDAK_VALID' => [
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Minta perbaikan berkas dan NIK almarhum.',
        ],
        'NIK_TIDAK_DITEMUKAN' => [
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Minta perbaikan berkas atau rekonsiliasi data kependudukan.',
        ],
        'JENIS_KELAMIN_TIDAK_COCOK' => [
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Rekonsiliasi jenis kelamin dengan data SIAK dan dokumen.',
        ],
        'TANGGAL_TIDAK_KONSISTEN' => [
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Minta klarifikasi tanggal lahir, tanggal kematian, atau tanggal pengajuan.',
        ],
        'NAMA_TIDAK_COCOK' => [
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Minta perbaikan nama almarhum.',
        ],
        'TERLAMBAT_TANPA_SPTJM' => [
            'keparahan' => 'sedang',
            'rekomendasi' => 'Minta unggah SPTJM Kebenaran Data Kematian.',
        ],
        'BERKAS_TIDAK_LENGKAP' => [
            'keparahan' => 'sedang',
            'rekomendasi' => 'Minta berkas wajib yang masih kurang.',
        ],
        'DOKUMEN_TIDAK_TERBACA' => [
            'keparahan' => 'sedang',
            'rekomendasi' => 'Verifikator membaca dokumen secara manual.',
        ],
        'NAMA_BEDA_TIPIS' => [
            'keparahan' => 'rendah',
            'rekomendasi' => 'Verifikator mengoreksi nama bila perlu.',
        ],
        'ALAMAT_DI_LUAR_WILAYAH' => [
            'keparahan' => 'info',
            'rekomendasi' => 'Hanya catatan; lanjutkan verifikasi.',
        ],
        'LAYANAN_TIDAK_TERSEDIA' => [
            'keparahan' => 'info',
            'rekomendasi' => 'Lanjut manual dan catat pada log audit.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pemetaan Anomali ke Saran Tindakan Verifikator
    |--------------------------------------------------------------------------
    |
    | Mesin hanya menyarankan. Verifikator tetap memutuskan dan wajib mengisi
    | alasan_override bila memilih tindakan berbeda dari saran tertinggi.
    |
    */

    'tindakan_anomali' => [
        'PENGAJUAN_GANDA' => 'tolak_fatal',
        'SIAK_SUDAH_MENINGGAL' => 'tandai_anomali',
        'KASUS_KHUSUS' => 'tandai_anomali',
        'NIK_TIDAK_VALID' => 'minta_perbaikan',
        'NIK_TIDAK_DITEMUKAN' => 'minta_perbaikan',
        'JENIS_KELAMIN_TIDAK_COCOK' => 'tandai_anomali',
        'TANGGAL_TIDAK_KONSISTEN' => 'minta_perbaikan',
        'NAMA_TIDAK_COCOK' => 'minta_perbaikan',
        'TERLAMBAT_TANPA_SPTJM' => 'minta_perbaikan',
        'BERKAS_TIDAK_LENGKAP' => 'minta_perbaikan',
        'DOKUMEN_TIDAK_TERBACA' => 'tandai_anomali',
        'NAMA_BEDA_TIPIS' => 'minta_perbaikan',
        'ALAMAT_DI_LUAR_WILAYAH' => 'setujui',
        'LAYANAN_TIDAK_TERSEDIA' => 'setujui',
    ],

    'tindakan_bersih' => 'setujui',

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
