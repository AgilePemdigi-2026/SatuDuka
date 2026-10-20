<?php

namespace App\Services;

use App\Models\LogAudit;
use App\Models\Pengguna;

/**
 * Satu-satunya cara pencatatan jejak audit SATUDUKA.
 *
 * Seluruh aksi penting (login, registrasi, pengajuan, unggah berkas, verifikasi,
 * rekonsiliasi, penandatanganan TTE, dan pemanggilan layanan eksternal) wajib
 * dicatat melalui kelas ini agar format log konsisten (NFR-06 dan SRS 2.6 butir 4).
 *
 * Tabel `log_audit` bersifat append-only: model menolak pembaruan dan penghapusan.
 */
final class LayananAudit
{
    /**
     * Catat satu aktivitas ke `log_audit`.
     *
     * @param  string  $aksi  Nama aksi, contoh: `mengajukan_laporan`.
     * @param  string|null  $objek  Objek yang dikenai aksi, contoh: `laporan_kematian`.
     * @param  int|null  $idObjek  Kunci utama objek.
     * @param  array<string, mixed>  $detail  Rincian tambahan.
     * @param  Pengguna|null  $pengguna  Pelaku aksi (null untuk proses otomatis).
     * @param  string|null  $layananEksternal  Nama layanan eksternal bila relevan.
     * @param  string|null  $hasil  Hasil aksi, contoh: `Sukses` atau `Gagal`.
     */
    public static function catat(
        string $aksi,
        ?string $objek = null,
        ?int $idObjek = null,
        array $detail = [],
        ?Pengguna $pengguna = null,
        ?string $layananEksternal = null,
        ?string $hasil = null,
    ): LogAudit {
        return LogAudit::create([
            'id_pengguna' => $pengguna?->id_pengguna,
            'aksi' => $aksi,
            'objek' => $objek,
            'id_objek' => $idObjek,
            'detail' => $detail === [] ? null : $detail,
            'layanan_eksternal' => $layananEksternal,
            'hasil' => $hasil,
        ]);
    }

    /**
     * Catat pemanggilan layanan eksternal beserta hasilnya.
     *
     * @param  array<string, mixed>  $detail
     */
    public static function catatLayanan(
        string $layanan,
        string $hasil,
        array $detail = [],
        ?Pengguna $pengguna = null,
    ): LogAudit {
        return self::catat(
            aksi: 'memeriksa_layanan_eksternal',
            objek: 'layanan',
            detail: $detail,
            pengguna: $pengguna,
            layananEksternal: $layanan,
            hasil: $hasil,
        );
    }
}
