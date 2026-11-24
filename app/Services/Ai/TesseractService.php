<?php

namespace App\Services\Ai;

use App\Models\BerkasPendukung;
use App\Support\PenguraiSuratKematian;
use App\Support\PenyaringTeksDokumen;
use Illuminate\Support\Facades\Storage;

/**
 * Ekstraksi dokumen sepenuhnya lokal: tesseract OCR + penguraian label
 * bahasa Indonesia, tanpa memanggil penyedia AI eksternal.
 *
 * Driver ini dipakai bila koneksi penyedia tidak tersedia atau untuk
 * pengujian nyata di lingkungan offline. Hasil tetap hanya saran dan
 * tidak mengubah status pengajuan.
 */
class TesseractService implements AIService
{
    public function ekstrakSuratKematian(BerkasPendukung $berkas): array
    {
        if (! Storage::disk('local')->exists($berkas->path_file)) {
            throw new AiTidakTersedia('Berkas tidak ditemukan.');
        }

        $isi = (string) Storage::disk('local')->get($berkas->path_file);

        $teks = PenyaringTeksDokumen::teks($berkas->path_file, $isi);

        if ($teks === null) {
            throw new AiTidakTersedia('Dokumen tidak dapat dibaca oleh OCR; pastikan tesseract terpasang atau gunakan salinan PDF.');
        }

        $urai = PenguraiSuratKematian::urai($teks);

        return [
            'berhasil' => true,
            'kolom' => $urai['kolom'],
            'skor' => $urai['skor'],
            'pesan' => $urai['pesan'],
        ];
    }

    public function ringkasKetidaksesuaian(array $perbandingan): array
    {
        $kolom = is_array($perbandingan['kolom'] ?? null) ? $perbandingan['kolom'] : [];

        $beda = [];
        $tidakTerbaca = [];

        foreach ($kolom as $baris) {
            if (! is_array($baris)) {
                continue;
            }

            $status = $baris['status'] ?? null;

            if ($status === 'beda') {
                $beda[] = $baris;
            } elseif ($status === 'tidak terbaca') {
                $tidakTerbaca[] = $baris;
            }
        }

        $bagian = [];

        if ($beda !== []) {
            $bagian[] = 'Ditemukan '.count($beda).' kolom berbeda: '
                .$this->labelKolom($beda).'.';
        }

        if ($tidakTerbaca !== []) {
            $bagian[] = 'Ada '.count($tidakTerbaca).' kolom yang tidak terbaca: '
                .$this->labelKolom($tidakTerbaca).'.';
        }

        if ($bagian === []) {
            $bagian[] = 'Tidak ditemukan ketidaksesuaian pada kolom yang diperiksa.';
        }

        $saran = [];

        foreach ($beda as $baris) {
            $saran[] = match ($baris['kunci'] ?? '') {
                'nik' => 'Cocokkan NIK pada dokumen asli dengan data SIAK sebelum melanjutkan.',
                'nama_almarhum' => 'Periksa kembali ejaan nama pada dokumen dan isian pemohon.',
                'tanggal_kematian' => 'Pastikan tanggal kematian pada dokumen sesuai dengan isian pemohon.',
                'tempat_kematian' => 'Konfirmasi tempat kematian pada dokumen asli.',
                default => 'Periksa kembali kolom '.($baris['label'] ?? 'tersebut').' pada dokumen asli.',
            };
        }

        if ($tidakTerbaca !== []) {
            $saran[] = 'Minta dokumen yang lebih jelas atau lakukan verifikasi manual.';
        }

        if ($saran === []) {
            $saran[] = 'Tetap periksa keaslian dokumen secara manual sebelum mengambil keputusan.';
        }

        return [
            'berhasil' => true,
            'ringkasan' => implode(' ', $bagian),
            'saran' => array_values(array_unique($saran)),
            'pesan' => null,
        ];
    }

    public function namaModel(): string
    {
        return 'tesseract-ocr-v1';
    }

    /**
     * @param  list<array<string, mixed>>  $baris
     */
    private function labelKolom(array $baris): string
    {
        $label = array_map(
            static fn (array $item): string => (string) ($item['label'] ?? $item['kunci'] ?? 'kolom'),
            $baris,
        );

        return implode(', ', $label);
    }
}
