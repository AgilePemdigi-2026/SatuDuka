<?php

namespace App\Models;

use Database\Factories\DokumenResmiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Akta kematian resmi hasil terbitan sistem.
 *
 * Sumber: docs/DATABASE.md tabel `dokumen_resmi` dan Tabel 8.2 SRS.
 */
class DokumenResmi extends Model
{
    /** @use HasFactory<DokumenResmiFactory> */
    use HasFactory;

    /* Status tanda tangan dokumen. */
    public const STATUS_MENUNGGU = 'Menunggu';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUS_DIKEMBALIKAN = 'Dikembalikan';

    /**
     * Daftar status tanda tangan yang sah.
     *
     * @var list<string>
     */
    public const DAFTAR_STATUS_TTD = [
        self::STATUS_MENUNGGU,
        self::STATUS_SELESAI,
        self::STATUS_DIKEMBALIKAN,
    ];

    protected $table = 'dokumen_resmi';

    protected $primaryKey = 'id_dokumen';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_laporan',
        'id_pengguna',
        'no_dokumen',
        'tanggal_terbit',
        'status_ttd',
        'file_dokumen',
        'alasan_pengembalian',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'datetime',
        ];
    }

    /**
     * Relasi 6: laporan yang menghasilkan dokumen ini (relasi 1:1).
     */
    public function laporanKematian(): BelongsTo
    {
        return $this->belongsTo(LaporanKematian::class, 'id_laporan', 'id_laporan');
    }

    /**
     * Relasi 7: pejabat penanda tangan (null sampai dokumen ditandatangani).
     */
    public function pejabat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}
