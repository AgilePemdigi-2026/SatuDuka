<?php

namespace App\Services\Notifikasi;

use App\Models\LaporanKematian;
use App\Models\Notifikasi;
use App\Models\Pengguna;

/**
 * Kontrak pengiriman notifikasi sistem kepada pengguna.
 *
 * Sesuai AGENTS.md, layanan notifikasi adalah interface dengan
 * implementasi yang diganti lewat konfigurasi; implementasi tidak boleh
 * mengubah status pengajuan dan tidak menyimpan NIK.
 */
interface NotifikasiService
{
    /**
     * Kirim satu notifikasi ke penerima.
     */
    public function kirim(
        Pengguna $penerima,
        string $jenis,
        string $judul,
        string $pesan,
        ?LaporanKematian $laporan = null,
    ): Notifikasi;

    /**
     * Jumlah notifikasi belum dibaca milik satu pengguna.
     */
    public function jumlahBelumDibaca(Pengguna $pengguna): int;
}
