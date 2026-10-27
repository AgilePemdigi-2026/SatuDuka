<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Referensi kecamatan Kota Yogyakarta dan jumlah kematian tahun 2020.
 *
 * Sumber: docs/data_kecamatan_2020.csv (Open Data Kota Yogyakarta).
 */
class ReferensiWilayah extends Model
{
    protected $table = 'referensi_wilayah';

    protected $primaryKey = 'kode_kecamatan';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode_kecamatan',
        'nama_kecamatan',
        'jumlah_kematian_2020',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_kematian_2020' => 'integer',
        ];
    }
}
