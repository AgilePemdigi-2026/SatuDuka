<?php

namespace App\Models;

use Database\Factories\LaporanKematianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Entitas inti pengajuan laporan kematian.
 *
 * Sumber: docs/DATABASE.md tabel `laporan_kematian` dan Tabel 8.2 SRS.
 */
class LaporanKematian extends Model
{
    /** @use HasFactory<LaporanKematianFactory> */
    use HasFactory;

    /* Status pengajuan (docs/DATABASE.md). */
    public const STATUS_DRAF = 'Draf';

    public const STATUS_MENUNGGU_VERIFIKASI = 'Menunggu Verifikasi';

    public const STATUS_DITOLAK_BUTUH_REVISI = 'Ditolak/Butuh Revisi';

    public const STATUS_DISETUJUI_MENUNGGU_TTE = 'Disetujui/Menunggu TTE';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUS_DIKEMBALIKAN = 'Dikembalikan';

    /**
     * Status tambahan yang muncul pada use case 5.2.6 dan 5.2.7 SRS tetapi belum
     * tercatat di docs/DATABASE.md.
     */
    public const STATUS_DALAM_REKONSILIASI = 'Dalam Rekonsiliasi Anomali';

    public const STATUS_MENUNGGU_PERBAIKAN = 'Pending - Menunggu Perbaikan Berkas Pelapor';

    /* Jenis kasus kematian. */
    public const JENIS_KASUS_NORMAL = 'Normal';

    public const JENIS_KASUS_TIDAK_DIKENAL = 'Jenazah Tidak Dikenal';

    public const JENIS_KASUS_TIDAK_DITEMUKAN = 'Jenazah Tidak Ditemukan';

    /**
     * Pilihan jenis kasus yang sah.
     *
     * @var list<string>
     */
    public const DAFTAR_JENIS_KASUS = [
        self::JENIS_KASUS_NORMAL,
        self::JENIS_KASUS_TIDAK_DIKENAL,
        self::JENIS_KASUS_TIDAK_DITEMUKAN,
    ];

    /**
     * Seluruh status pengajuan yang sah.
     *
     * @var list<string>
     */
    public const DAFTAR_STATUS = [
        self::STATUS_DRAF,
        self::STATUS_MENUNGGU_VERIFIKASI,
        self::STATUS_DITOLAK_BUTUH_REVISI,
        self::STATUS_DISETUJUI_MENUNGGU_TTE,
        self::STATUS_SELESAI,
        self::STATUS_DIKEMBALIKAN,
        self::STATUS_DALAM_REKONSILIASI,
        self::STATUS_MENUNGGU_PERBAIKAN,
    ];

    /**
     * Status yang masih boleh diubah oleh pemohon.
     *
     * @var list<string>
     */
    public const DAFTAR_STATUS_BOLEH_DIUBAH = [
        self::STATUS_DRAF,
        self::STATUS_MENUNGGU_PERBAIKAN,
        self::STATUS_DITOLAK_BUTUH_REVISI,
    ];

    protected $table = 'laporan_kematian';

    protected $primaryKey = 'id_laporan';

    /**
     * Kolom waktu buat dan ubah.
     */
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_pengguna',
        'no_pengajuan',
        'nama_pelapor',
        'hubungan_pelapor',
        'nama_almarhum',
        'nik_almarhum',
        'tanggal_kematian',
        'jenis_kelamin',
        'waktu_kematian',
        'jenis_tempat',
        'jenis_kasus',
        'tempat_kematian',
        'sebab_kematian',
        'status_pengajuan',
        'tanggal_pengajuan',
        'catatan_revisi',
        'perlu_verifikasi_manual',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kematian' => 'date',
            'tanggal_pengajuan' => 'datetime',
            'perlu_verifikasi_manual' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi (Tabel 8.2)
    |--------------------------------------------------------------------------
    */

    /**
     * Relasi 1: pemohon yang mengajukan.
     */
    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Relasi 2: laporan_kematian 1:N berkas_pendukung.
     */
    public function berkasPendukung(): HasMany
    {
        return $this->hasMany(BerkasPendukung::class, 'id_laporan', 'id_laporan');
    }

    /**
     * Relasi 3: laporan_kematian 1:N hasil_verifikasi.
     */
    public function hasilVerifikasi(): HasMany
    {
        return $this->hasMany(HasilVerifikasi::class, 'id_laporan', 'id_laporan');
    }

    /**
     * Relasi 6: laporan_kematian 1:1 dokumen_resmi.
     */
    public function dokumenResmi(): HasOne
    {
        return $this->hasOne(DokumenResmi::class, 'id_laporan', 'id_laporan');
    }

    /**
     * Riwayat analisis dokumen berbantuan AI untuk laporan ini.
     */
    public function analisisAi(): HasMany
    {
        return $this->hasMany(AnalisisAi::class, 'id_laporan', 'id_laporan');
    }

    /*
    |--------------------------------------------------------------------------
    | Pembantu
    |--------------------------------------------------------------------------
    */

    /**
     * Apakah pengajuan masih berupa draf.
     */
    public function adalahDraf(): bool
    {
        return $this->status_pengajuan === self::STATUS_DRAF;
    }

    /**
     * Apakah pengajuan masih boleh diperbaiki dan dikirim ulang oleh pemohon.
     */
    public function bolehDiubah(): bool
    {
        return in_array($this->status_pengajuan, self::DAFTAR_STATUS_BOLEH_DIUBAH, true);
    }

    /**
     * Apakah pengajuan sudah disetujui dan menunggu penandatanganan TTE.
     */
    public function menungguTte(): bool
    {
        return $this->status_pengajuan === self::STATUS_DISETUJUI_MENUNGGU_TTE;
    }

    /**
     * Apakah pengajuan telah selesai dan akta telah terbit.
     */
    public function selesai(): bool
    {
        return $this->status_pengajuan === self::STATUS_SELESAI;
    }

    /**
     * Nama almarhum untuk ditampilkan pada daftar pengajuan.
     */
    public function labelNamaAlmarhum(): string
    {
        if ($this->adalahDraf() && blank($this->nama_almarhum)) {
            return 'Draf belum diisi';
        }

        return $this->nama_almarhum ?: '—';
    }

    /**
     * Tanggal pengajuan untuk ditampilkan pada daftar pengajuan.
     */
    public function labelTanggalPengajuan(): string
    {
        if ($this->tanggal_pengajuan !== null) {
            return $this->tanggal_pengajuan->translatedFormat('j F Y');
        }

        if ($this->adalahDraf() && $this->dibuat_pada !== null) {
            return 'Dibuat '.$this->dibuat_pada->translatedFormat('j F Y');
        }

        return '—';
    }
}
