<?php

namespace App\Services\Tte;

use InvalidArgumentException;
use LogicException;

/**
 * Memilih implementasi TTEService berdasarkan konfigurasi.
 *
 * Driver `mock` sengaja DITOLAK pada lingkungan produksi: PIN dummy tidak
 * boleh dipakai untuk menandatangani dokumen nyata. Driver yang tidak dikenal
 * juga menghasilkan pengecualian yang jelas, bukan diam-diam jatuh ke mock.
 */
final class PabrikTte
{
    public const DRIVER_MOCK = 'mock';

    /**
     * Lingkungan yang boleh memakai driver mock.
     *
     * @var list<string>
     */
    public const LINGKUNGAN_MOCK = ['local', 'testing'];

    public static function buat(string $driver, string $lingkungan, ?string $pinDummy): TTEService
    {
        if ($driver === self::DRIVER_MOCK) {
            if (! in_array($lingkungan, self::LINGKUNGAN_MOCK, true)) {
                throw new LogicException(
                    'Driver TTE mock tidak boleh dipakai pada lingkungan '.$lingkungan
                    .'. Gunakan penyedia TTE tersertifikasi atau jalankan pada lingkungan lokal/pengujian.'
                );
            }

            return new MockTTEService($pinDummy);
        }

        throw new InvalidArgumentException('Driver TTE tidak dikenal: '.$driver.'.');
    }
}
