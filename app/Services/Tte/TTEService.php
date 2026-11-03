<?php

namespace App\Services\Tte;

use App\Models\Pengguna;

/**
 * Kontrak layanan tanda tangan elektronik (TTE).
 *
 * SATUDUKA hanya meneruskan kredensial (PIN/OTP) ke layanan TTE; kredensial
 * tidak pernah disimpan, dicatat di log, atau dimasukkan ke audit.
 */
interface TTEService
{
    /**
     * Memverifikasi kredensial TTE milik pejabat.
     *
     * Implementasi wajib mengembalikan `false` untuk kredensial salah dan
     * melempar {@see TteTidakTersedia} bila layanan tidak dapat dihubungi.
     *
     * @throws TteTidakTersedia
     */
    public function verifikasiKredensial(Pengguna $pejabat, string $kredensial): bool;

    /**
     * Nama penyedia/driver untuk catatan audit.
     */
    public function namaPenyedia(): string;
}
