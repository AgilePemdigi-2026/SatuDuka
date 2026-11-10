<?php

use App\Models\BerkasPendukung;
use App\Support\PenjagaNikDemo;

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

// Guard: NIK demo tidak boleh aktif di produksi kecuali operator menyalakan
// flag demo eksplisit (SATUDUKA_IZINKAN_MOCK_DEMO) untuk demo terkontrol.
$nikDemoKeputusan = PenjagaNikDemo::putuskan(
    lingkungan: (string) env('APP_ENV', 'production'),
    diminta: filter_var($nikDemo, FILTER_VALIDATE_BOOLEAN),
    izinkanMock: filter_var(env('SATUDUKA_IZINKAN_MOCK_DEMO', false), FILTER_VALIDATE_BOOLEAN),
);

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

    'izinkan_nik_demo' => $nikDemoKeputusan['aktif'],

    // True bila permintaan aktif di produksi ditolak karena tidak ada flag demo
    // eksplisit; dipakai AppServiceProvider untuk mencatat satu peringatan.
    'nik_demo_diabaikan' => $nikDemoKeputusan['diabaikan'],

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
            'label' => 'Pengajuan ganda',
            'keparahan' => 'fatal',
            'rekomendasi' => 'Tolak anomali fatal atau minta verifikasi manual.',
        ],
        'KASUS_KHUSUS' => [
            'label' => 'Kasus khusus',
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Jalur manual; pastikan Surat Keterangan Kepolisian tersedia.',
        ],
        'NIK_TIDAK_VALID' => [
            'label' => 'NIK tidak valid',
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Minta perbaikan berkas dan NIK almarhum.',
        ],
        'TANGGAL_TIDAK_KONSISTEN' => [
            'label' => 'Tanggal tidak konsisten',
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Minta klarifikasi tanggal lahir, tanggal kematian, atau tanggal pengajuan.',
        ],
        'NAMA_TIDAK_COCOK' => [
            'label' => 'Nama tidak cocok',
            'keparahan' => 'tinggi',
            'rekomendasi' => 'Minta perbaikan nama almarhum.',
        ],
        'TERLAMBAT_TANPA_SPTJM' => [
            'label' => 'Terlambat tanpa SPTJM',
            'keparahan' => 'sedang',
            'rekomendasi' => 'Minta unggah SPTJM Kebenaran Data Kematian.',
        ],
        'BERKAS_TIDAK_LENGKAP' => [
            'label' => 'Berkas tidak lengkap',
            'keparahan' => 'sedang',
            'rekomendasi' => 'Minta berkas wajib yang masih kurang.',
        ],
        'DOKUMEN_TIDAK_TERBACA' => [
            'label' => 'Dokumen tidak terbaca',
            'keparahan' => 'sedang',
            'rekomendasi' => 'Verifikator membaca dokumen secara manual.',
        ],
        'LAYANAN_TIDAK_TERSEDIA' => [
            'label' => 'Layanan tidak tersedia',
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
        'KASUS_KHUSUS' => 'tandai_anomali',
        'NIK_TIDAK_VALID' => 'minta_perbaikan',
        'TANGGAL_TIDAK_KONSISTEN' => 'minta_perbaikan',
        'NAMA_TIDAK_COCOK' => 'minta_perbaikan',
        'TERLAMBAT_TANPA_SPTJM' => 'minta_perbaikan',
        'BERKAS_TIDAK_LENGKAP' => 'minta_perbaikan',
        'DOKUMEN_TIDAK_TERBACA' => 'tandai_anomali',
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
