<?php

namespace App\Services\Tte;

use App\Models\Pengguna;

/**
 * Implementasi mock layanan TTE untuk pengembangan dan pengujian.
 *
 * PIN dummy dibaca dari konfigurasi dan hanya diisi otomatis pada lingkungan
 * lokal/pengujian. Kredensial yang dikirim dibandingkan dengan `hash_equals`
 * agar waktu pembandingan tidak membocorkan panjang awalan PIN.
 *
 * PIN tidak pernah disimpan pada properti objek, log, atau basis data.
 */
class MockTTEService implements TTEService
{
    /**
     * @param  string|null  $pinDummy  PIN dummy dari konfigurasi; null berarti tidak ada PIN sah.
     */
    public function __construct(private readonly ?string $pinDummy) {}

    public function verifikasiKredensial(Pengguna $pejabat, string $kredensial): bool
    {
        $pin = $this->pinDummy;

        if ($pin === null || $pin === '') {
            return false;
        }

        return hash_equals($pin, $kredensial);
    }

    public function namaPenyedia(): string
    {
        return 'mock-tte';
    }
}
