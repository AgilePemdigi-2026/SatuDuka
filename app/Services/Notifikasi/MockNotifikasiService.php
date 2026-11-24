<?php

namespace App\Services\Notifikasi;

use App\Models\LaporanKematian;
use App\Models\Notifikasi;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Log;

/**
 * Implementasi mock notifikasi untuk pengembangan dan pengujian.
 *
 * Notifikasi hanya ditulis ke log, tidak disimpan ke basis data. Selalu
 * mengembalikan model Notifikasi yang tidak dipersistenkan agar pemanggil
 * dapat bekerja dengan bentuk yang sama.
 */
class MockNotifikasiService implements NotifikasiService
{
    public function kirim(
        Pengguna $penerima,
        string $jenis,
        string $judul,
        string $pesan,
        ?LaporanKematian $laporan = null,
    ): Notifikasi {
        Log::info('Notifikasi (mock)', [
            'penerima' => $penerima->id_pengguna,
            'jenis' => $jenis,
            'judul' => $judul,
            'pesan' => $pesan,
            'id_laporan' => $laporan?->id_laporan,
        ]);

        return (new Notifikasi)->forceFill([
            'id_pengguna' => $penerima->id_pengguna,
            'id_laporan' => $laporan?->id_laporan,
            'jenis' => $jenis,
            'judul' => $judul,
            'pesan' => $pesan,
            'sudah_dibaca' => false,
            'dibuat_pada' => now(),
        ]);
    }

    public function jumlahBelumDibaca(Pengguna $pengguna): int
    {
        return 0;
    }
}
