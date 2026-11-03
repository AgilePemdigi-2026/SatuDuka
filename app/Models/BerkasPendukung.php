<?php

namespace App\Models;

use Database\Factories\BerkasPendukungFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public const JENIS_SURAT_KETERANGAN_KELURAHAN = 'Surat Keterangan Kelurahan';

    public const JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN = 'SPTJM Kebenaran Data Kematian';

    public const JENIS_SURAT_KETERANGAN_KEPOLISIAN = 'Surat Keterangan Kepolisian';

    public const JENIS_KTP_SAKSI_1 = 'KTP Saksi 1';

    public const JENIS_KTP_SAKSI_2 = 'KTP Saksi 2';

    /**
     * Empat jenis berkas lama, dipertahankan untuk kompatibilitas kode lain.
     *
     * Aturan kelengkapan saat ini dibaca dari config('verifikasi.berkas_wajib')
     * melalui jenisYangKurang().
     *
     * @var list<string>
     */
    public const DAFTAR_JENIS_WAJIB = [
        self::JENIS_SURAT_KETERANGAN_KEMATIAN,
        self::JENIS_KTP_ALMARHUM,
        self::JENIS_KTP_PELAPOR,
        self::JENIS_KARTU_KELUARGA,
    ];

    /**
     * Berkas tambahan yang boleh diunggah, tetapi tidak wajib.
     *
     * @var list<string>
     */
    public const DAFTAR_JENIS_OPSIONAL = [
        self::JENIS_SURAT_KETERANGAN_KELURAHAN,
        self::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN,
        self::JENIS_SURAT_KETERANGAN_KEPOLISIAN,
        self::JENIS_KTP_SAKSI_1,
        self::JENIS_KTP_SAKSI_2,
    ];

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
        self::JENIS_SURAT_KETERANGAN_KELURAHAN,
        self::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN,
        self::JENIS_SURAT_KETERANGAN_KEPOLISIAN,
        self::JENIS_KTP_SAKSI_1,
        self::JENIS_KTP_SAKSI_2,
    ];

    /**
     * Jenis dokumen yang belum memenuhi aturan kelengkapan.
     *
     * Bukti kematian dihitung memenuhi bila minimal satu jenis dari
     * config('verifikasi.berkas_wajib.bukti_kematian.jenis') terunggah.
     *
     * @param  array<int, string>  $terunggah
     * @return list<string>
     */
    public static function jenisYangKurang(array $terunggah, bool $terlambat, string $jenisKasus): array
    {
        $terunggah = array_values(array_unique($terunggah));
        $kurang = [];

        foreach ((array) config('verifikasi.berkas_wajib.lainnya', []) as $jenis) {
            if (! in_array($jenis, $terunggah, true)) {
                $kurang[] = $jenis;
            }
        }

        $buktiKematian = array_values((array) config('verifikasi.berkas_wajib.bukti_kematian.jenis', []));

        if ($buktiKematian !== [] && array_intersect($buktiKematian, $terunggah) === []) {
            $kurang[] = self::labelKelompok('Bukti kematian', $buktiKematian);
        }

        if ($terlambat) {
            $berkasKeterlambatan = array_values((array) config('verifikasi.berkas_bersyarat.keterlambatan', []));

            if ($berkasKeterlambatan !== [] && array_intersect($berkasKeterlambatan, $terunggah) === []) {
                $kurang[] = self::labelKelompok('Berkas keterlambatan', $berkasKeterlambatan);
            }
        }

        if ($jenisKasus !== LaporanKematian::JENIS_KASUS_NORMAL) {
            $berkasKasusKhusus = array_values((array) config('verifikasi.berkas_bersyarat.jenis_kasus_khusus', []));

            if ($berkasKasusKhusus !== [] && array_intersect($berkasKasusKhusus, $terunggah) === []) {
                $kurang[] = self::labelKelompok('Berkas jenis kasus khusus', $berkasKasusKhusus);
            }
        }

        return $kurang;
    }

    /**
     * Label yang menyebut nama jenis, atau kelompok bila lebih dari satu.
     *
     * @param  list<string>  $jenis
     */
    private static function labelKelompok(string $label, array $jenis): string
    {
        if (count($jenis) === 1) {
            return $jenis[0];
        }

        return $label.' (salah satu: '.implode(', ', $jenis).')';
    }

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

    /**
     * Riwayat analisis AI yang memakai berkas ini.
     */
    public function analisisAi(): HasMany
    {
        return $this->hasMany(AnalisisAi::class, 'id_berkas', 'id_berkas');
    }
}
