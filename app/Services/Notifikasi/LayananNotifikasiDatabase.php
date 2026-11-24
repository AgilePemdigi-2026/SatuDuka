<?php

namespace App\Services\Notifikasi;

use App\Models\LaporanKematian;
use App\Models\Notifikasi;
use App\Models\Pengguna;
use Illuminate\Support\Str;

/**
 * Implementasi notifikasi yang menyimpan catatan ke tabel `notifikasi`.
 *
 * Pesan ditulis apa adanya; pemanggil wajib memanggil MaskerNik bila
 * pesan berasal dari masukan pengguna bebas (nilai NIK tidak pernah
 * dimasukkan ke notifikasi).
 */
class LayananNotifikasiDatabase implements NotifikasiService
{
    public function kirim(
        Pengguna $penerima,
        string $jenis,
        string $judul,
        string $pesan,
        ?LaporanKematian $laporan = null,
    ): Notifikasi {
        return Notifikasi::create([
            'id_pengguna' => $penerima->id_pengguna,
            'id_laporan' => $laporan?->id_laporan,
            'jenis' => in_array($jenis, Notifikasi::DAFTAR_JENIS, true) ? $jenis : Notifikasi::JENIS_INFO,
            'judul' => Str::limit(trim($judul), 100, ''),
            'pesan' => trim($pesan),
            'sudah_dibaca' => false,
        ]);
    }

    public function jumlahBelumDibaca(Pengguna $pengguna): int
    {
        return Notifikasi::query()
            ->where('id_pengguna', $pengguna->id_pengguna)
            ->where('sudah_dibaca', false)
            ->count();
    }
}
