<?php

namespace App\Models;

use Database\Factories\HasilVerifikasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catatan hasil pemeriksaan berkas oleh verifikator.
 *
 * Sumber: docs/DATABASE.md tabel `hasil_verifikasi` dan Tabel 8.2 SRS.
 */
class HasilVerifikasi extends Model
{
    /** @use HasFactory<HasilVerifikasiFactory> */
    use HasFactory;

    /* Status hasil verifikasi. */
    public const STATUS_DITERIMA = 'Diterima';

    public const STATUS_DITOLAK = 'Ditolak';

    public const STATUS_PERLU_REVISI = 'Perlu Revisi';

    public const STATUS_ANOMALI = 'Anomali';

    /**
     * Daftar status hasil verifikasi yang sah.
     *
     * @var list<string>
     */
    public const DAFTAR_STATUS = [
        self::STATUS_DITERIMA,
        self::STATUS_DITOLAK,
        self::STATUS_PERLU_REVISI,
        self::STATUS_ANOMALI,
    ];

    protected $table = 'hasil_verifikasi';

    protected $primaryKey = 'id_verifikasi';

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
        'id_laporan',
        'id_pengguna',
        'status_verifikasi',
        'catatan',
        'deteksi_otomatis',
        'alasan_override',
        'tanggal_verifikasi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_verifikasi' => 'datetime',
            'deteksi_otomatis' => 'array',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    /**
     * Relasi 3: laporan yang diverifikasi.
     */
    public function laporanKematian(): BelongsTo
    {
        return $this->belongsTo(LaporanKematian::class, 'id_laporan', 'id_laporan');
    }

    /**
     * Relasi 4: verifikator yang melakukan pemeriksaan.
     */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Relasi 5: anomali yang dipicu oleh hasil verifikasi ini.
     */
    public function anomaliDokumen(): HasMany
    {
        return $this->hasMany(AnomaliDokumen::class, 'id_verifikasi', 'id_verifikasi');
    }
}
