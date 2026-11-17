<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;
use Smalot\PdfParser\Parser;

/**
 * Mengubah berkas pendukung menjadi teks mentah.
 *
 * PDF dibaca dengan smalot/pdfparser (tanpa proses eksternal), sedangkan
 * gambar (JPG/PNG) dibaca dengan tesseract OCR. Kelas ini tidak mengirim
 * apa pun ke layanan eksternal; teks hasilnya yang akan dikirim ke penyedia
 * AI, sehingga NIK yang ada di dalam dokumen tetap ikut terkirim sesuai
 * tujuan pembacaan dokumen, dan prompt di sekelilingnya tetap dimasker.
 */
final class PenyaringTeksDokumen
{
    /**
     * Ambil teks dokumen sesuai ekstensi berkas; null bila gagal dibaca.
     */
    public static function teks(string $pathFile, string $isi): ?string
    {
        return match (strtolower(pathinfo($pathFile, PATHINFO_EXTENSION))) {
            'pdf' => self::teksPdf($isi),
            'jpg', 'jpeg', 'png' => self::teksOcr($pathFile, $isi),
            default => null,
        };
    }

    /**
     * Baca teks dari berkas PDF secara lokal (tanpa proses eksternal).
     */
    public static function teksPdf(string $isi): ?string
    {
        if (! str_starts_with($isi, '%PDF-')) {
            return null;
        }

        try {
            $teks = trim((new Parser)->parseContent($isi)->getText());

            return $teks === '' ? null : $teks;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Baca teks dari gambar melalui tesseract OCR.
     *
     * Lokasi program dan bahasa dibaca dari `config('ai.tesseract')`.
     * Bila tesseract tidak terpasang atau gagal, hasilnya null dan pemanggil
     * memutuskan fallback manual; pengajuan tidak pernah ditolak otomatis.
     */
    public static function teksOcr(string $pathFile, string $isi): ?string
    {
        $ekstensi = strtolower(pathinfo($pathFile, PATHINFO_EXTENSION));

        if (! in_array($ekstensi, ['jpg', 'jpeg', 'png'], true)) {
            return null;
        }

        $sementara = tempnam(sys_get_temp_dir(), 'satuduka_ocr_');

        if ($sementara === false) {
            return null;
        }

        $jalur = $sementara.'.'.$ekstensi;

        @rename($sementara, $jalur);

        try {
            file_put_contents($jalur, $isi);

            $hasil = Process::timeout((int) config('ai.tesseract.timeout', 30))->run([
                (string) config('ai.tesseract.binary', 'tesseract'),
                $jalur,
                'stdout',
                '-l',
                (string) config('ai.tesseract.bahasa', 'ind+eng'),
            ]);

            if (! $hasil->successful()) {
                // Bahasa terpasang mungkin tidak lengkap; coba bahasa bawaan.
                $hasil = Process::timeout((int) config('ai.tesseract.timeout', 30))->run([
                    (string) config('ai.tesseract.binary', 'tesseract'),
                    $jalur,
                    'stdout',
                ]);
            }

            $teks = trim($hasil->output());

            return $teks === '' ? null : $teks;
        } catch (\Throwable) {
            return null;
        } finally {
            @unlink($jalur);
        }
    }
}
