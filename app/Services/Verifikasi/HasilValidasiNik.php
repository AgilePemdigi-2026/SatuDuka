<?php

namespace App\Services\Verifikasi;

/**
 * Hasil pemeriksaan NIK beserta bagian-bagian yang berhasil diekstrak.
 */
final readonly class HasilValidasiNik
{
    /**
     * @param  list<PelanggaranNik>  $pelanggaran
     */
    public function __construct(
        public array $pelanggaran,
        public bool $valid,
        public ?string $provinsi = null,
        public ?string $namaProvinsi = null,
        public ?string $kabupaten = null,
        public ?string $kecamatan = null,
        public ?int $hari = null,
        public ?int $bulan = null,
        public ?string $tahun = null,
        public ?string $jenisKelamin = null,
        public ?string $nomorUrut = null,
    ) {}

    /**
     * @return list<string>
     */
    public function kodePelanggaran(): array
    {
        return array_map(
            static fn (PelanggaranNik $pelanggaran): string => $pelanggaran->kode,
            $this->pelanggaran,
        );
    }

    public function punyaPelanggaran(string $kode): bool
    {
        return in_array($kode, $this->kodePelanggaran(), true);
    }
}
