<?php

namespace App\Services\Ai;

use App\Models\BerkasPendukung;

/**
 * Kontrak layanan ekstraksi dokumen berbantuan AI.
 *
 * Implementasi nyata maupun mock tidak boleh mengubah status pengajuan.
 * Hasil hanya dipakai sebagai bahan pertimbangan pengguna.
 */
interface AIService
{
    /**
     * Membaca surat keterangan kematian dan mengekstrak data almarhum.
     *
     * @return array{
     *     berhasil: bool,
     *     kolom: array<string, array{nilai: string|null, keyakinan: float}>,
     *     skor: float,
     *     pesan: string|null
     * }
     *
     * @throws AiTidakTersedia
     */
    public function ekstrakSuratKematian(BerkasPendukung $berkas): array;

    /**
     * Nama model/driver yang dipakai pada catatan analisis.
     */
    public function namaModel(): string;
}
