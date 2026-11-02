<?php

namespace App\Services\Dokumen;

use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

/**
 * Pembuat PDF memakai dompdf.
 *
 * Aset eksternal dimatikan (`isRemoteEnabled=false`) agar dokumen tidak pernah
 * mengambil gambar, font, atau URL dari internet. Font bawaan dompdf (DejaVu)
 * dipakai supaya tidak ada unduhan font saat render.
 */
class PembuatPdfDompdf implements PembuatPdf
{
    public function hasil(string $tampilan, array $data): string
    {
        $pdf = Pdf::loadView($tampilan, $data);

        // Aset eksternal dimatikan: tidak ada gambar, font, atau URL dari internet.
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');
        $pdf->setOption('chroot', base_path());

        $isi = $pdf->output();

        if (! str_starts_with($isi, '%PDF-')) {
            throw new RuntimeException('Hasil render bukan berkas PDF yang sah.');
        }

        return $isi;
    }
}
