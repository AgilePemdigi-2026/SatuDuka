<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;
use Smalot\PdfParser\Parser;

/**
 * Mengubah berkas pendukung menjadi teks mentah.
 *
 * PDF dibaca dengan smalot/pdfparser (tanpa proses eksternal). Bila PDF tidak
 * memiliki text-layer (hasil pindaian/foto), halaman dirasterisasi lebih dulu
 * dengan `pdftoppm` lalu dibaca dengan tesseract OCR. Gambar (JPG/PNG) langsung
 * dibaca dengan tesseract.
 *
 * Kelas ini tidak mengirim apa pun ke layanan eksternal. Teks hasilnya yang
 * akan dikirim ke penyedia AI, sehingga NIK yang ada di dalam dokumen tetap
 * ikut terkirim sesuai tujuan pembacaan dokumen, sedangkan prompt di sekelilingnya
 * tetap dimasker. Pengiriman teks hasil OCR PDF pindaian itu disengaja: tanpa
 * itu dokumen hasil pindaian tidak akan pernah terbaca oleh penyedia AI.
 */
final class PenyaringTeksDokumen
{
    /**
     * Ambil teks dokumen sesuai ekstensi berkas; null bila gagal dibaca.
     */
    public static function teks(string $pathFile, string $isi): ?string
    {
        return match (strtolower(pathinfo($pathFile, PATHINFO_EXTENSION))) {
            'pdf' => self::teksPdf($isi) ?? self::teksPdfOcr($isi),
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
     * Baca teks PDF tanpa text-layer dengan merasterisasi halaman lalu OCR.
     *
     * Halaman diubah menjadi PNG memakai `pdftoppm` (poppler-utils) dengan
     * argumen berbentuk array tanpa shell, lalu setiap halaman dibaca memakai
     * tesseract. Null dikembalikan bila pdftoppm/tesseract tidak tersedia,
     * gagal, atau hasilnya kosong; pemanggil yang memutuskan fallback manual
     * sehingga pengajuan tidak pernah ditolak otomatis.
     */
    public static function teksPdfOcr(string $isi): ?string
    {
        $direktori = null;

        try {
            if (! str_starts_with($isi, '%PDF-')) {
                return null;
            }

            $direktori = self::direktoriSementara();

            if ($direktori === null) {
                return null;
            }

            $berkasPdf = $direktori.DIRECTORY_SEPARATOR.'dokumen.pdf';

            if (file_put_contents($berkasPdf, $isi) === false) {
                return null;
            }

            $hasil = Process::timeout(self::batasWaktuPdfOcr())->run([
                (string) config('ai.pdf_ocr.binary', 'pdftoppm'),
                '-r',
                '200',
                '-f',
                '1',
                '-l',
                (string) self::maksHalamanPdfOcr(),
                '-png',
                $berkasPdf,
                $direktori.DIRECTORY_SEPARATOR.'halaman',
            ]);

            if (! $hasil->successful()) {
                return null;
            }

            $teks = [];

            foreach (self::halamanTerurut($direktori) as $halaman) {
                $isiHalaman = @file_get_contents($halaman);

                if (! is_string($isiHalaman) || $isiHalaman === '') {
                    continue;
                }

                $teksHalaman = self::teksOcr($halaman, $isiHalaman);

                if ($teksHalaman !== null) {
                    $teks[] = $teksHalaman;
                }
            }

            $gabungan = trim(implode(PHP_EOL.PHP_EOL, $teks));

            return $gabungan === '' ? null : $gabungan;
        } catch (\Throwable) {
            return null;
        } finally {
            if ($direktori !== null) {
                self::hapusDirektori($direktori);
            }
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

    /**
     * Direktori kerja sementara khusus satu dokumen (mode 0700).
     */
    private static function direktoriSementara(): ?string
    {
        $jalur = sys_get_temp_dir().DIRECTORY_SEPARATOR.'satuduka_pdf_ocr_'.bin2hex(random_bytes(8));

        if (! @mkdir($jalur, 0700, true) && ! is_dir($jalur)) {
            return null;
        }

        return $jalur;
    }

    /**
     * Daftar berkas halaman PNG hasil rasterisasi, urut sesuai nomor halaman.
     *
     * @return list<string>
     */
    private static function halamanTerurut(string $direktori): array
    {
        $halaman = glob($direktori.DIRECTORY_SEPARATOR.'halaman*.png');

        if ($halaman === false) {
            return [];
        }

        sort($halaman, SORT_NATURAL);

        return array_values($halaman);
    }

    /**
     * Hapus seluruh berkas dan direktori sementara.
     */
    private static function hapusDirektori(string $direktori): void
    {
        $berkas = glob($direktori.DIRECTORY_SEPARATOR.'*');

        if ($berkas !== false) {
            foreach ($berkas as $jalur) {
                @unlink($jalur);
            }
        }

        @rmdir($direktori);
    }

    private static function maksHalamanPdfOcr(): int
    {
        return max(1, (int) config('ai.pdf_ocr.maks_halaman', 3));
    }

    private static function batasWaktuPdfOcr(): int
    {
        return max(1, (int) config('ai.pdf_ocr.timeout', 60));
    }
}
