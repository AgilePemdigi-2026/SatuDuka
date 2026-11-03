<?php

namespace App\Services\Dokumen;

use GdImage;
use Throwable;

/**
 * Memeriksa dan menormalkan gambar tanda tangan visual pejabat.
 *
 * Tanda tangan visual BUKAN bukti keaslian; hash hanya penanda integritas
 * gambar. Kelas ini tidak menulis ke log atau basis data dan tidak pernah
 * menggemakan kembali isi masukan pada pesan galat.
 */
class LayananTandaTanganVisual
{
    /**
     * Awalan data URL PNG yang diterima.
     */
    public const AWALAN = 'data:image/png;base64,';

    /**
     * Ukuran biner maksimal setelah base64 didekode (150 KB).
     */
    public const BATAS_BYTE = 153600;

    /**
     * Dimensi maksimal gambar.
     */
    public const MAKS_LEBAR = 1200;

    public const MAKS_TINGGI = 600;

    /**
     * Jumlah piksel tak transparan minimal agar goresan dianggap ada.
     */
    public const MIN_PIKSEL = 30;

    /**
     * @return array{berhasil: bool, pesan: string|null, base64: string|null, hash: string|null}
     */
    public function periksa(?string $dataUrl): array
    {
        $dataUrl = (string) $dataUrl;

        if ($dataUrl === '') {
            return $this->gagal('Tanda tangan wajib digambar terlebih dahulu.');
        }

        if (! str_starts_with($dataUrl, self::AWALAN)) {
            return $this->gagal('Tanda tangan harus berupa gambar PNG.');
        }

        $mentah = substr($dataUrl, strlen(self::AWALAN));

        if ($mentah === '' || preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $mentah) !== 1) {
            return $this->gagal('Data tanda tangan tidak valid.');
        }

        $biner = base64_decode($mentah, true);

        if ($biner === false || $biner === '') {
            return $this->gagal('Data tanda tangan tidak valid.');
        }

        if (strlen($biner) > self::BATAS_BYTE) {
            return $this->gagal('Ukuran gambar tanda tangan melebihi 150 KB.');
        }

        $ukuran = @getimagesizefromstring($biner);

        if (! is_array($ukuran) || ($ukuran[2] ?? null) !== IMAGETYPE_PNG) {
            return $this->gagal('Tanda tangan harus berupa gambar PNG.');
        }

        [$lebar, $tinggi] = [(int) $ukuran[0], (int) $ukuran[1]];

        if ($lebar < 1 || $tinggi < 1
            || $lebar > self::MAKS_LEBAR || $tinggi > self::MAKS_TINGGI) {
            return $this->gagal('Dimensi gambar tanda tangan maksimal '.self::MAKS_LEBAR.'x'.self::MAKS_TINGGI.' piksel.');
        }

        if ($this->kosong($biner)) {
            return $this->gagal('Tanda tangan masih kosong; silakan gambar ulang.');
        }

        try {
            $normal = $this->renderUlang($biner);
        } catch (Throwable) {
            return $this->gagal('Gambar tanda tangan tidak dapat diproses.');
        }

        return [
            'berhasil' => true,
            'pesan' => null,
            'base64' => $normal,
            'hash' => hash('sha256', base64_decode($normal, true) ?: ''),
        ];
    }

    /**
     * Apakah gambar tidak memiliki goresan yang cukup.
     */
    private function kosong(string $biner): bool
    {
        $gambar = @imagecreatefromstring($biner);

        if (! $gambar instanceof GdImage) {
            return true;
        }

        $lebar = imagesx($gambar);
        $tinggi = imagesy($gambar);
        $takTransparan = 0;

        for ($y = 0; $y < $tinggi; $y++) {
            for ($x = 0; $x < $lebar; $x++) {
                $warna = imagecolorat($gambar, $x, $y);
                $alpha = ($warna >> 24) & 0x7F;

                if ($alpha < 127) {
                    $takTransparan++;

                    if ($takTransparan >= self::MIN_PIKSEL) {
                        imagedestroy($gambar);

                        return false;
                    }
                }
            }
        }

        imagedestroy($gambar);

        return true;
    }

    /**
     * Render ulang PNG lewat GD agar metadata dan konten tersembunyi hilang.
     *
     * @return string Base64 PNG hasil render ulang.
     */
    private function renderUlang(string $biner): string
    {
        $sumber = imagecreatefromstring($biner);

        if (! $sumber instanceof GdImage) {
            throw new \RuntimeException('Gambar tidak dapat dibaca.');
        }

        $lebar = imagesx($sumber);
        $tinggi = imagesy($sumber);

        $bersih = imagecreatetruecolor($lebar, $tinggi);
        imagealphablending($bersih, false);
        imagesavealpha($bersih, true);
        $transparan = imagecolorallocatealpha($bersih, 0, 0, 0, 127);
        imagefilledrectangle($bersih, 0, 0, $lebar, $tinggi, $transparan);
        imagecopy($bersih, $sumber, 0, 0, 0, 0, $lebar, $tinggi);

        ob_start();
        imagepng($bersih, null, 9);
        $keluaran = (string) ob_get_clean();

        imagedestroy($bersih);
        imagedestroy($sumber);

        if ($keluaran === '' || ! str_starts_with($keluaran, "\x89PNG")) {
            throw new \RuntimeException('Gambar gagal dirender ulang.');
        }

        return base64_encode($keluaran);
    }

    /**
     * @return array{berhasil: bool, pesan: string, base64: null, hash: null}
     */
    private function gagal(string $pesan): array
    {
        return [
            'berhasil' => false,
            'pesan' => $pesan,
            'base64' => null,
            'hash' => null,
        ];
    }
}
