<?php

namespace App\Services\Verifikasi;

use Illuminate\Support\Str;

/**
 * Normalisasi nama untuk pencocokan data pengajuan dengan SIAK atau dokumen.
 *
 * Kelas ini murni: tidak mengakses basis data, HTTP, atau UI.
 */
class LayananNormalisasiNama
{
    /**
     * @var list<list<string>>
     */
    private array $gelarDepan;

    /**
     * @var list<list<string>>
     */
    private array $gelarBelakang;

    /**
     * @var list<string>
     */
    private array $gelarAwalPendek;

    private int $minSisaGelarAwalPendek;

    /**
     * @var array<string, string>
     */
    private array $ejaanLama;

    /**
     * @param  list<string>|null  $gelarDepan
     * @param  list<string>|null  $gelarBelakang
     * @param  list<string>|null  $gelarAwalPendek
     * @param  array<string, string>|null  $ejaanLama
     */
    public function __construct(
        ?array $gelarDepan = null,
        ?array $gelarBelakang = null,
        ?array $gelarAwalPendek = null,
        ?int $minSisaGelarAwalPendek = null,
        ?array $ejaanLama = null,
    ) {
        $this->ejaanLama = $ejaanLama ?? (array) config('verifikasi.nama_ejaan_lama', []);
        $this->gelarDepan = self::siapkanGelar($gelarDepan ?? (array) config('verifikasi.nama_gelar_depan', []));
        $this->gelarBelakang = self::siapkanGelar($gelarBelakang ?? (array) config('verifikasi.nama_gelar_belakang', []));
        $this->gelarAwalPendek = self::siapkanGelarPendek($gelarAwalPendek ?? (array) config('verifikasi.nama_gelar_awal_pendek', []));
        $this->minSisaGelarAwalPendek = $minSisaGelarAwalPendek
            ?? (int) config('verifikasi.nama_gelar_awal_pendek_min_sisa', 2);
    }

    public function normalisasi(string $nama): string
    {
        $token = $this->tokenDasar($nama);

        if ($token === []) {
            return '';
        }

        $token = $this->buangGelarAwalPendek($token);
        $token = $this->buangGelarDepan($token);
        $token = $this->buangGelarBelakang($token);

        return implode(' ', $token);
    }

    /**
     * @return list<string>
     */
    public function token(string $nama): array
    {
        $hasil = $this->normalisasi($nama);

        return $hasil === '' ? [] : explode(' ', $hasil);
    }

    /**
     * @return list<string>
     */
    private function tokenDasar(string $nama): array
    {
        $teks = Str::ascii($nama);
        $teks = mb_strtolower($teks, 'UTF-8');
        $teks = strtr($teks, $this->ejaanLama);
        $teks = preg_replace('/[^a-z0-9]+/', ' ', $teks) ?? '';
        $teks = trim(preg_replace('/\s+/', ' ', $teks) ?? '');

        return $teks === '' ? [] : explode(' ', $teks);
    }

    /**
     * @param  list<string>  $token
     * @return list<string>
     */
    private function buangGelarAwalPendek(array $token): array
    {
        while (count($token) - 1 >= $this->minSisaGelarAwalPendek
            && in_array($token[0] ?? null, $this->gelarAwalPendek, true)) {
            array_shift($token);
        }

        return $token;
    }

    /**
     * @param  list<string>  $token
     * @return list<string>
     */
    private function buangGelarDepan(array $token): array
    {
        do {
            $berubah = false;

            foreach ($this->gelarDepan as $gelar) {
                $jumlah = count($gelar);

                if (count($token) > $jumlah && array_slice($token, 0, $jumlah) === $gelar) {
                    $token = array_slice($token, $jumlah);
                    $berubah = true;
                    break;
                }
            }
        } while ($berubah);

        return $token;
    }

    /**
     * @param  list<string>  $token
     * @return list<string>
     */
    private function buangGelarBelakang(array $token): array
    {
        do {
            $berubah = false;

            foreach ($this->gelarBelakang as $gelar) {
                $jumlah = count($gelar);

                if (count($token) > $jumlah && array_slice($token, -$jumlah) === $gelar) {
                    $token = array_slice($token, 0, -$jumlah);
                    $berubah = true;
                    break;
                }
            }
        } while ($berubah);

        return $token;
    }

    /**
     * @param  list<string>  $daftar
     * @return list<list<string>>
     */
    private function siapkanGelar(array $daftar): array
    {
        $hasil = [];
        $terlihat = [];

        foreach ($daftar as $gelar) {
            $token = $this->tokenDasar((string) $gelar);

            if ($token === []) {
                continue;
            }

            $kunci = implode(' ', $token);

            if (isset($terlihat[$kunci])) {
                continue;
            }

            $terlihat[$kunci] = true;
            $hasil[] = $token;
        }

        usort($hasil, static function (array $kiri, array $kanan): int {
            $bedaJumlah = count($kanan) <=> count($kiri);

            return $bedaJumlah !== 0
                ? $bedaJumlah
                : strlen(implode('', $kanan)) <=> strlen(implode('', $kiri));
        });

        return $hasil;
    }

    /**
     * @param  list<string>  $daftar
     * @return list<string>
     */
    private function siapkanGelarPendek(array $daftar): array
    {
        $hasil = [];

        foreach ($daftar as $gelar) {
            $token = $this->tokenDasar((string) $gelar);

            if ($token !== [] && ! in_array($token[0], $hasil, true)) {
                $hasil[] = $token[0];
            }
        }

        return $hasil;
    }
}
