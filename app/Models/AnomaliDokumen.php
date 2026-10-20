<?php

namespace App\Models;

use Database\Factories\AnomaliDokumenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ketidaksesuaian data yang ditemukan saat verifikasi berkas.
 *
 * Sumber: docs/DATABASE.md tabel `anomali_dokumen` dan Tabel 8.2 SRS.
 */
class AnomaliDokumen extends Model
{
    /** @use HasFactory<AnomaliDokumenFactory> */
    use HasFactory;

    /* Status rekonsiliasi anomali. */
    public const STATUS_TERBUKA = 'Terbuka';

    public const STATUS_SELESAI = 'Selesai';

    /**
     * Status tambahan untuk alur "Tolak Permanen & Eskalasi" pada use case 5.2.7 SRS.
     */
    public const STATUS_DITOLAK_PERMANEN = 'Ditolak - Anomali Data Fatal';

    /**
     * Daftar status rekonsiliasi yang sah.
     *
     * @var list<string>
     */
    public const DAFTAR_STATUS = [
        self::STATUS_TERBUKA,
        self::STATUS_SELESAI,
        self::STATUS_DITOLAK_PERMANEN,
    ];

    /* Jenis anomali yang lazim ditemukan. */
    public const JENIS_NIK_GANDA = 'NIK Ganda';

    public const JENIS_PERBEDAAN_IDENTITAS = 'Perbedaan Identitas';

    public const JENIS_TANGGAL_KEMATIAN = 'Perbedaan Tanggal Kematian';

    public const JENIS_TEMPAT_KEMATIAN = 'Perbedaan Tempat Kematian';

    public const JENIS_DOKUMEN_TIDAK_TERBACA = 'Dokumen Tidak Terbaca';

    protected $table = 'anomali_dokumen';

    protected $primaryKey = 'id_anomali';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_verifikasi',
        'jenis_anomali',
        'deskripsi',
        'status_rekonsiliasi',
        'tanggal_rekonsiliasi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_rekonsiliasi' => 'datetime',
        ];
    }

    /**
     * Relasi 5: hasil verifikasi yang memicu anomali ini.
     */
    public function hasilVerifikasi(): BelongsTo
    {
        return $this->belongsTo(HasilVerifikasi::class, 'id_verifikasi', 'id_verifikasi');
    }
}
