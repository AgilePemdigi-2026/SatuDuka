<?php

namespace App\Services\Dokumen;

/**
 * Kontrak pembuat berkas PDF dokumen resmi.
 *
 * Implementasi harus mengembalikan isi PDF di memori (bukan menulis berkas),
 * sehingga pemanggil dapat menyimpan berkas dan basis data secara terkendali.
 */
interface PembuatPdf
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \Throwable
     */
    public function hasil(string $tampilan, array $data): string;
}
