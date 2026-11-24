<?php

namespace App\Models;

use Database\Factories\NotifikasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu catatan pusat notifikasi pengguna.
 *
 * Notifikasi dibuat oleh sistem pada perubahan status penting (revisi,
 * anomali, dokumen terbit) dan dibaca lewat Pusat Notifikasi. Jenis yang
 * sah mengikuti tab antarmuka: `validasi_awal`, `perlu_diperbaiki`,
 * `dokumen_terbit`, dan `info`.
 */
class Notifikasi extends Model
{
    /** @use HasFactory<NotifikasiFactory> */
    use HasFactory;

    public const JENIS_VALIDASI_AWAL = 'validasi_awal';

    public const JENIS_PERLU_DIPERBAIKI = 'perlu_diperbaiki';

    public const JENIS_DOKUMEN_TERBIT = 'dokumen_terbit';

    public const JENIS_INFO = 'info';

    /**
     * @var list<string>
     */
    public const DAFTAR_JENIS = [
        self::JENIS_VALIDASI_AWAL,
        self::JENIS_PERLU_DIPERBAIKI,
        self::JENIS_DOKUMEN_TERBIT,
        self::JENIS_INFO,
    ];

    protected $table = 'notifikasi';

    protected $primaryKey = 'id_notifikasi';

    /**
     * Kolom waktu buat dan ubah memakai nama Indonesia.
     */
    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_pengguna',
        'id_laporan',
        'jenis',
        'judul',
        'pesan',
        'sudah_dibaca',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sudah_dibaca' => 'boolean',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    /**
     * Penerima notifikasi.
     */
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }

    /**
     * Pengajuan yang menjadi objek notifikasi (boleh kosong).
     */
    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanKematian::class, 'id_laporan', 'id_laporan');
    }
}
