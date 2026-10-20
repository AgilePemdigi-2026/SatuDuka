<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Model autentikasi SATUDUKA (menggantikan tabel `users` bawaan Laravel).
 *
 * Sumber: docs/DATABASE.md tabel `pengguna` dan Tabel 8.2 SRS.
 */
class Pengguna extends Authenticatable
{
    /** @use HasFactory<PenggunaFactory> */
    use HasFactory;

    public const PERAN_PEMOHON = 'pemohon';

    public const PERAN_VERIFIKATOR = 'verifikator';

    public const PERAN_PEJABAT = 'pejabat';

    /**
     * Daftar peran yang sah.
     *
     * @var list<string>
     */
    public const DAFTAR_PERAN = [
        self::PERAN_PEMOHON,
        self::PERAN_VERIFIKATOR,
        self::PERAN_PEJABAT,
    ];

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    /**
     * Kolom cipta dan ubah dipetakan ke nama Indonesia.
     */
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_lengkap',
        'nik',
        'nip',
        'email',
        'kata_sandi',
        'no_telepon',
        'peran',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'kata_sandi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kata_sandi' => 'hashed',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Autentikasi
    |--------------------------------------------------------------------------
    */

    /**
     * Nama kolom identitas yang dipakai guard: `id_pengguna`.
     */
    public function getAuthIdentifierName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Nama kolom kata sandi yang disimpan pada tabel `pengguna`.
     */
    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    /**
     * Kata sandi (berbentuk hash) yang dipakai guard.
     */
    public function getAuthPassword(): string
    {
        return $this->kata_sandi;
    }

    /**
     * Alamat surel yang dipakai untuk tautan pengaturan ulang kata sandi.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi (Tabel 8.2)
    |--------------------------------------------------------------------------
    */

    /**
     * Relasi 1: pengguna 1:N laporan_kematian (pemohon).
     */
    public function laporanKematian(): HasMany
    {
        return $this->hasMany(LaporanKematian::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Relasi 4: pengguna 1:N hasil_verifikasi (verifikator).
     */
    public function hasilVerifikasi(): HasMany
    {
        return $this->hasMany(HasilVerifikasi::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Relasi 7: pengguna 1:N dokumen_resmi (pejabat).
     */
    public function dokumenResmi(): HasMany
    {
        return $this->hasMany(DokumenResmi::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Relasi tambahan: pengguna 1:N log_audit.
     */
    public function logAudit(): HasMany
    {
        return $this->hasMany(LogAudit::class, 'id_pengguna', 'id_pengguna');
    }

    /*
    |--------------------------------------------------------------------------
    | Pembantu
    |--------------------------------------------------------------------------
    */

    /**
     * Apakah pengguna berperan pemohon.
     */
    public function adalahPemohon(): bool
    {
        return $this->peran === self::PERAN_PEMOHON;
    }

    /**
     * Apakah pengguna berperan verifikator.
     */
    public function adalahVerifikator(): bool
    {
        return $this->peran === self::PERAN_VERIFIKATOR;
    }

    /**
     * Apakah pengguna berperan pejabat penanda tangan.
     */
    public function adalahPejabat(): bool
    {
        return $this->peran === self::PERAN_PEJABAT;
    }
}
