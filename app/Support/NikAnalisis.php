<?php

namespace App\Support;

/**
 * Penyamaran NIK pada hasil analisis AI sebelum disimpan atau ditampilkan.
 *
 * Tabel `analisis_ai.hasil` TIDAK BOLEH memuat NIK 16 digit utuh. Untuk kolom
 * NIK disimpan dua hal:
 *
 * - `hash` (blind index HMAC-SHA256, kunci sama dengan `nik_hash`), dipakai
 *   untuk membandingkan data tanpa membuka NIK; dan
 * - `nilai` yang sudah dimasker (`MaskerNik`), dipakai untuk tampilan.
 *
 * Fungsi di kelas ini idempoten: nilai yang sudah termasker tidak berubah dan
 * hash yang sudah ada dipertahankan.
 */
final class NikAnalisis
{
    /**
     * Samarkan hasil ekstraksi: `kolom.nik.nilai` -> termasker + `hash`.
     *
     * @param  array<string, mixed>  $hasil
     * @return array<string, mixed>
     */
    public static function samarkanEkstraksi(array $hasil): array
    {
        $kolom = $hasil['kolom'] ?? null;

        if (! is_array($kolom) || ! isset($kolom['nik']) || ! is_array($kolom['nik'])) {
            return $hasil;
        }

        $baris = $kolom['nik'];
        $nilai = $baris['nilai'] ?? null;
        $teks = is_string($nilai) ? $nilai : null;
        $hash = self::hashDari($baris['hash'] ?? null, $teks);

        $hasil['kolom']['nik']['nilai'] = $teks === null ? $nilai : MaskerNik::masker($teks);

        if ($hash !== null) {
            $hasil['kolom']['nik']['hash'] = $hash;
        } else {
            unset($hasil['kolom']['nik']['hash']);
        }

        return $hasil;
    }

    /**
     * Samarkan hasil perbandingan: setiap baris NIK mendapat nilai termasker
     * dan tiga hash blind index (pemohon, SIAK, ekstraksi).
     *
     * @param  array<string, mixed>  $hasil
     * @return array<string, mixed>
     */
    public static function samarkanPerbandingan(array $hasil): array
    {
        $kolom = $hasil['kolom'] ?? null;

        if (! is_array($kolom)) {
            return $hasil;
        }

        foreach ($kolom as $indeks => $baris) {
            if (! is_array($baris) || ($baris['kunci'] ?? null) !== 'nik') {
                continue;
            }

            foreach (['pemohon', 'ekstraksi', 'siak'] as $sumber) {
                $kunciNilai = 'nilai_'.$sumber;
                $nilai = $baris[$kunciNilai] ?? null;
                $teks = is_string($nilai) ? $nilai : null;
                $hash = self::hashDari($baris['hash_'.$sumber] ?? null, $teks);

                $kolom[$indeks][$kunciNilai] = $teks === null ? $nilai : MaskerNik::masker($teks);

                if ($hash !== null) {
                    $kolom[$indeks]['hash_'.$sumber] = $hash;
                } else {
                    unset($kolom[$indeks]['hash_'.$sumber]);
                }
            }
        }

        $hasil['kolom'] = $kolom;

        return $hasil;
    }

    /**
     * Samarkan hasil analisis sesuai jenisnya.
     *
     * @param  array<string, mixed>  $hasil
     * @return array<string, mixed>
     */
    public static function samarkan(string $jenis, array $hasil): array
    {
        return match ($jenis) {
            'ekstraksi' => self::samarkanEkstraksi($hasil),
            'perbandingan' => self::samarkanPerbandingan($hasil),
            default => $hasil,
        };
    }

    /**
     * Hash yang sudah tersimpan dipakai lebih dulu; bila belum ada, hitung
     * dari nilai. Nilai yang sudah termasker tidak menghasilkan hash baru.
     */
    private static function hashDari(mixed $hashTersimpan, ?string $nilai): ?string
    {
        if (is_string($hashTersimpan) && $hashTersimpan !== '') {
            return $hashTersimpan;
        }

        return PengindeksNik::hash($nilai);
    }
}
