<?php

namespace Tests\Unit\Tte;

use App\Models\Pengguna;
use App\Services\Tte\MockTTEService;
use App\Services\Tte\PabrikTte;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Pengujian pemilihan driver TTE dan penolakan mock pada produksi.
 */
class PabrikTteTest extends TestCase
{
    public function test_driver_mock_diizinkan_pada_lingkungan_lokal(): void
    {
        $layanan = PabrikTte::buat('mock', 'local', '123456');

        $this->assertInstanceOf(MockTTEService::class, $layanan);
        $this->assertSame('mock-tte', $layanan->namaPenyedia());
    }

    public function test_driver_mock_ditolak_pada_produksi(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Driver TTE mock tidak boleh dipakai pada lingkungan production');

        PabrikTte::buat('mock', 'production', '123456');
    }

    public function test_driver_tidak_dikenal_menghasilkan_pengecualian_jelas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver TTE tidak dikenal');

        PabrikTte::buat('bsre', 'production', null);
    }

    public function test_pin_dummy_kosong_tidak_pernah_sah(): void
    {
        $layanan = PabrikTte::buat('mock', 'testing', null);
        $pengguna = new Pengguna;

        $this->assertFalse($layanan->verifikasiKredensial($pengguna, ''));
        $this->assertFalse($layanan->verifikasiKredensial($pengguna, '123456'));
    }
}
