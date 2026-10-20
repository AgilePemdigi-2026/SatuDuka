<?php

namespace App\Models;

use Database\Factories\LogAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak audit append-only (NFR-06).
 *
 * Tabel `log_audit` tidak memiliki kolom `diperbarui_pada`; pembaruan dan penghapusan
 * baris log harus dicegah di tingkat aplikasi.
 *
 * Sumber: docs/DATABASE.md tabel `log_audit`.
 */
class LogAudit extends Model
{
    /** @use HasFactory<LogAuditFactory> */
    use HasFactory;

    /* Nama layanan eksternal yang dapat dicatat pada log. */
    public const LAYANAN_SIAK = 'SIAK';

    public const LAYANAN_FASKES = 'Faskes';

    public const LAYANAN_TTE = 'TTE';

    public const LAYANAN_NOTIFIKASI = 'Notifikasi';

    /**
     * Hasil pemanggilan layanan eksternal.
     */
    public const HASIL_SUKSES = 'Sukses';

    public const HASIL_GAGAL = 'Gagal';

    public const HASIL_DOWN = 'Down';

    protected $table = 'log_audit';

    protected $primaryKey = 'id_log';

    /**
     * Kolom waktu cipta dipetakan ke `dibuat_pada`; tidak ada kolom ubah.
     */
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_pengguna',
        'aksi',
        'objek',
        'id_objek',
        'detail',
        'layanan_eksternal',
        'hasil',
        'dibuat_pada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'detail' => 'array',
            'dibuat_pada' => 'datetime',
        ];
    }

    /**
     * Pengguna yang melakukan aksi (null untuk proses otomatis).
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Cegah pembaruan dan penghapusan baris log audit: log bersifat append-only.
     */
    protected static function booted(): void
    {
        static::updating(function (self $log): void {
            throw new \LogicException('log_audit bersifat append-only dan tidak boleh diperbarui.');
        });

        static::deleting(function (self $log): void {
            throw new \LogicException('log_audit bersifat append-only dan tidak boleh dihapus.');
        });
    }
}
