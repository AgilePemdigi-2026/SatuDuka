<?php

namespace App\Models;

use Database\Factories\AnalisisAiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan hasil analisis dokumen berbantuan AI.
 *
 * Hasil analisis bersifat saran dan tidak pernah mengubah status pengajuan.
 */
class AnalisisAi extends Model
{
    /** @use HasFactory<AnalisisAiFactory> */
    use HasFactory;

    public const JENIS_EKSTRAKSI = 'ekstraksi';

    public const JENIS_PERBANDINGAN = 'perbandingan';

    public const JENIS_RINGKASAN = 'ringkasan';

    public const DAFTAR_JENIS = [
        self::JENIS_EKSTRAKSI,
        self::JENIS_PERBANDINGAN,
        self::JENIS_RINGKASAN,
    ];

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_GAGAL = 'gagal';

    protected $table = 'analisis_ai';

    protected $primaryKey = 'id_analisis';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_laporan',
        'id_berkas',
        'jenis',
        'hasil',
        'skor',
        'model',
        'status',
        'hash_berkas',
        'dibuat_pada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hasil' => 'array',
            'skor' => 'float',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanKematian::class, 'id_laporan', 'id_laporan');
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(BerkasPendukung::class, 'id_berkas', 'id_berkas');
    }
}
