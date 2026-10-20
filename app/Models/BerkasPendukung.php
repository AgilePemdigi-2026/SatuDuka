<?php

namespace App\Models;

use Database\Factories\BerkasPendukungFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dokumen pendukung yang dilampirkan pada laporan kematian.
 *
 * Sumber: docs/DATABASE.md tabel `berkas_pendukung` dan Tabel 8.2 SRS.
 */
class BerkasPendukung extends Model
{
    /** @use HasFactory<BerkasPendukungFactory> */
    use HasFactory;

    /* Jenis dokumen yang diperbolehkan (use case 5.2.6 SRS). */
    public const JENIS_SURAT_KETERANGAN_KEMATIAN = 'Surat Keterangan Kematian';

    public const JENIS_KTP_ALMARHUM = 'KTP Almarhum';

    public const JENIS_KTP_PELAPOR = 'KTP Pelapor';

    public const JENIS_KARTU_KELUARGA = 'Kartu Keluarga';

    /**
     * Daftar jenis dokumen yang sah.
     *
     * @var list<string>
     */
    public const DAFTAR_JENIS = [
        self::JENIS_SURAT_KETERANGAN_KEMATIAN,
        self::JENIS_KTP_ALMARHUM,
        self::JENIS_KTP_PELAPOR,
        self::JENIS_KARTU_KELUARGA,
    ];

    /* Status unggahan berkas. */
    public const STATUS_MENUNGGU = 'Menunggu';

    public const STATUS_BERHASIL = 'Berhasil';

    public const STATUS_GAGAL = 'Gagal';

    /**
     * Daftar status unggahan yang sah.
     *
     * @var list<string>
     */
    public const DAFTAR_STATUS_UNGGAHAN = [
        self::STATUS_MENUNGGU,
        self::STATUS_BERHASIL,
        self::STATUS_GAGAL,
    ];

    protected $table = 'berkas_pendukung';

    protected $primaryKey = 'id_berkas';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_laporan',
        'jenis_dokumen',
        'nama_file',
        'path_file',
        'status_unggahan',
        'tanggal_unggah',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_unggah' => 'datetime',
        ];
    }

    /**
     * Relasi 2: berkas pendukung milik sebuah laporan kematian.
     */
    public function laporanKematian(): BelongsTo
    {
        return $this->belongsTo(LaporanKematian::class, 'id_laporan', 'id_laporan');
    }
}
