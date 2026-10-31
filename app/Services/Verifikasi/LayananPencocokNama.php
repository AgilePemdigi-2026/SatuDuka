<?php

namespace App\Services\Verifikasi;

/**
 * Mencocokkan dua nama yang sudah dinormalisasi.
 *
 * Skor memakai rasio Levenshtein, urutan token terurut, dan pencocokan inisial
 * dengan penalti kecil. Kelas ini murni tanpa basis data, HTTP, atau UI.
 */
class LayananPencocokNama
{
    /**
     * Jumlah digit desimal untuk skor dan ambang.
     */
    public const PRESISI_SKOR = 4;

    private LayananNormalisasiNama $normalisasi;

    private float $batasPersis;

    private float $batasBedaTipis;

    private float $penaltiInisial;

    private float $penaltiNamaTunggal;

    public function __construct(
        ?LayananNormalisasiNama $normalisasi = null,
        ?float $batasPersis = null,
        ?float $batasBedaTipis = null,
        ?float $penaltiInisial = null,
        ?float $penaltiNamaTunggal = null,
    ) {
        $this->normalisasi = $normalisasi ?? new LayananNormalisasiNama;
        $this->batasPersis = $batasPersis ?? (float) config('verifikasi.nama_cocok_persis', 1.0);
        $this->batasBedaTipis = $batasBedaTipis ?? (float) config('verifikasi.nama_beda_tipis', 0.80);
        $this->penaltiInisial = $penaltiInisial ?? (float) config('verifikasi.nama_penalti_inisial', 0.05);
        $this->penaltiNamaTunggal = $penaltiNamaTunggal ?? (float) config('verifikasi.nama_penalti_nama_tunggal', 0.15);
    }

    public function cocokkan(string $namaPertama, string $namaKedua): HasilPencocokanNama
    {
        $pertama = $this->normalisasi->normalisasi($namaPertama);
        $kedua = $this->normalisasi->normalisasi($namaKedua);

        if ($pertama === '' || $kedua === '') {
            return new HasilPencocokanNama(
                HasilPencocokanNama::KATEGORI_TIDAK_COCOK,
                0.0,
                $pertama,
                $kedua,
            );
        }

        if ($pertama === $kedua) {
            return new HasilPencocokanNama(
                HasilPencocokanNama::KATEGORI_PERSIS,
                1.0,
                $pertama,
                $kedua,
            );
        }

        $tokenPertama = explode(' ', $pertama);
        $tokenKedua = explode(' ', $kedua);
        $urutPertama = $this->urutkanToken($tokenPertama);
        $urutKedua = $this->urutkanToken($tokenKedua);

        $skor = max(
            $this->skorLevenshtein($pertama, $kedua),
            $this->skorLevenshtein(implode(' ', $urutPertama), implode(' ', $urutKedua)),
            $this->skorToken($tokenPertama, $tokenKedua),
            $this->skorToken($urutPertama, $urutKedua),
        );

        $skor = round(min(1.0, max(0.0, $skor)), self::PRESISI_SKOR);

        // Ambang dan skor dibulatkan dengan presisi yang sama agar perbandingan
        // tidak bergantung pada galat floating-point (mis. 0,8 vs 0,8000000001).
        $batasPersis = round($this->batasPersis, self::PRESISI_SKOR);
        $batasBedaTipis = round($this->batasBedaTipis, self::PRESISI_SKOR);

        $kategori = match (true) {
            $skor >= $batasPersis => HasilPencocokanNama::KATEGORI_PERSIS,
            $skor >= $batasBedaTipis => HasilPencocokanNama::KATEGORI_BEDA_TIPIS,
            default => HasilPencocokanNama::KATEGORI_TIDAK_COCOK,
        };

        return new HasilPencocokanNama($kategori, $skor, $pertama, $kedua);
    }

    private function skorLevenshtein(string $kiri, string $kanan): float
    {
        $panjang = max(strlen($kiri), strlen($kanan));

        if ($panjang === 0) {
            return 0.0;
        }

        return max(0.0, 1.0 - (levenshtein($kiri, $kanan) / $panjang));
    }

    /**
     * @param  list<string>  $kiri
     * @param  list<string>  $kanan
     */
    private function skorToken(array $kiri, array $kanan): float
    {
        if ($kiri === [] || $kanan === []) {
            return 0.0;
        }

        if (count($kiri) === count($kanan)) {
            $penalti = 0.0;

            foreach ($kiri as $indeks => $token) {
                if ($token === $kanan[$indeks]) {
                    continue;
                }

                if ($this->inisialCocok($token, $kanan[$indeks])) {
                    $penalti += $this->penaltiInisial;

                    continue;
                }

                return 0.0;
            }

            return max(0.0, 1.0 - $penalti);
        }

        [$pendek, $panjang] = count($kiri) < count($kanan) ? [$kiri, $kanan] : [$kanan, $kiri];
        $sisa = $panjang;
        $penalti = $this->penaltiNamaTunggal;

        foreach ($pendek as $tokenPendek) {
            $ketemu = false;

            foreach ($sisa as $indeks => $tokenPanjang) {
                if ($tokenPendek === $tokenPanjang) {
                    unset($sisa[$indeks]);
                    $ketemu = true;
                    break;
                }

                if ($this->inisialCocok($tokenPendek, $tokenPanjang)) {
                    unset($sisa[$indeks]);
                    $penalti += $this->penaltiInisial;
                    $ketemu = true;
                    break;
                }
            }

            if (! $ketemu) {
                return 0.0;
            }
        }

        return max(0.0, 1.0 - $penalti);
    }

    private function inisialCocok(string $kiri, string $kanan): bool
    {
        $panjangKiri = strlen($kiri);
        $panjangKanan = strlen($kanan);

        if ($panjangKiri === 1 && $panjangKanan > 1) {
            return str_starts_with($kanan, $kiri);
        }

        if ($panjangKanan === 1 && $panjangKiri > 1) {
            return str_starts_with($kiri, $kanan);
        }

        return false;
    }

    /**
     * @param  list<string>  $token
     * @return list<string>
     */
    private function urutkanToken(array $token): array
    {
        sort($token, SORT_STRING);

        return $token;
    }
}
