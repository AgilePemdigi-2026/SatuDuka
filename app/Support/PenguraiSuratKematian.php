<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Pengurai teks surat keterangan kematian hasil OCR/teks PDF.
 *
 * Penguraian memakai label bahasa Indonesia yang lazim pada surat: baris
 * "Nama", "NIK", "Tanggal Kematian", dan "Tempat Kematian"/"Meninggal di".
 * Keyakinan ditetapkan per pola: pola ketat (NIK 16 digit, tanggal utuh)
 * memperoleh nilai lebih tinggi daripada pola bebas.
 */
final class PenguraiSuratKematian
{
    /**
     * Nama bulan Indonesia -> nilai bulan.
     *
     * @var array<string, int>
     */
    private const BULAN = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
    ];

    /**
     * @return array{kolom: array<string, array{nilai: string|null, keyakinan: float}>, skor: float, pesan: string|null}
     */
    public static function urai(string $teks): array
    {
        $kolom = [
            'nama_almarhum' => self::uraikanNama($teks),
            'nik' => self::uraikanNik($teks),
            'tanggal_kematian' => self::uraikanTanggal($teks),
            'tempat_kematian' => self::uraikanTempat($teks),
        ];

        $terisi = array_filter(
            $kolom,
            static fn (array $baris): bool => $baris['nilai'] !== null,
        );

        $skor = $terisi === []
            ? 0.0
            : round(array_sum(array_column($terisi, 'keyakinan')) / count($terisi), 4);

        return [
            'kolom' => $kolom,
            'skor' => $skor,
            'pesan' => $terisi === []
                ? 'Dokumen tidak terbaca oleh penguraian teks.'
                : null,
        ];
    }

    /**
     * @return array{nilai: string|null, keyakinan: float}
     */
    private static function uraikanNama(string $teks): array
    {
        // Label ketat: "Nama :", "Nama Lengkap:", "Nama Almarhum:", dst.
        // Sebelum titik dua \s* boleh melintasi baris (pdfparser memisah sel
        // tabel), tetapi SETELAH titik dua dibatasi [ \t] agar nilai tidak
        // menyeret isi baris berikutnya pada hasil OCR.
        if (preg_match('/nama(?:\s+(?:lengkap|almarhum(?:ah)?(?:\s*\/\s*almarhum(?:ah)?))?|pelapor)?\s*[:\-][ \t]*(.+)$/imu', $teks, $m) === 1) {
            $nama = self::bersihkanBaris($m[1]);

            if ($nama !== '') {
                return ['nilai' => $nama, 'keyakinan' => 0.75];
            }
        }

        // Fallback: baris pertama setelah baris yang memuat kata "Nama".
        $baris = preg_split('/\R/', $teks) ?: [];

        foreach (array_keys($baris) as $indeks) {
            if (preg_match('/\bnama\b/i', $baris[$indeks]) !== 1) {
                continue;
            }

            $kandidat = $baris[$indeks + 1] ?? null;

            if ($kandidat !== null) {
                $nama = self::bersihkanBaris($kandidat);

                if ($nama !== '' && preg_match('/\b(nik|tanggal|tempat|jenis kelamin)\b/i', $nama) !== 1) {
                    return ['nilai' => $nama, 'keyakinan' => 0.55];
                }
            }
        }

        return ['nilai' => null, 'keyakinan' => 0.0];
    }

    /**
     * @return array{nilai: string|null, keyakinan: float}
     */
    private static function uraikanNik(string $teks): array
    {
        // Pola ketat: 16 digit utuh.
        if (preg_match('/\b\d{16}\b/', $teks, $m) === 1) {
            return ['nilai' => $m[0], 'keyakinan' => 0.9];
        }

        // OCR sering menyela spasi pada baris NIK; rapatkan digit di baris itu.
        $baris = preg_split('/\R/', $teks) ?: [];

        foreach ($baris as $satuBaris) {
            if (preg_match('/\bnik\b/i', $satuBaris) !== 1) {
                continue;
            }

            $digit = preg_replace('/\D+/', '', $satuBaris) ?? '';

            if (strlen($digit) === 16) {
                return ['nilai' => $digit, 'keyakinan' => 0.6];
            }
        }

        return ['nilai' => null, 'keyakinan' => 0.0];
    }

    /**
     * @return array{nilai: string|null, keyakinan: float}
     */
    private static function uraikanTanggal(string $teks): array
    {
        $baris = preg_split('/\R/', $teks) ?: [];

        // 1. Label ketat: baris "Tanggal Kematian : ...".
        foreach ($baris as $satuBaris) {
            if (preg_match('/tanggal\s+kematian/i', $satuBaris) === 1) {
                return self::polaTanggal($satuBaris, true);
            }
        }

        // 2. Baris "Meninggal (pada tanggal) ..." tanpa label eksplisit.
        foreach ($baris as $satuBaris) {
            if (preg_match('/\bmeninggal\b/i', $satuBaris) === 1) {
                $hasil = self::polaTanggal($satuBaris, true);

                if ($hasil['nilai'] !== null) {
                    return $hasil;
                }
            }
        }

        // 3. Tanggal tanpa label: baris apa pun kecuali baris tanggal lahir.
        foreach ($baris as $satuBaris) {
            if (preg_match('/lahir/i', $satuBaris) === 1) {
                continue;
            }

            $hasil = self::polaTanggal($satuBaris, false);

            if ($hasil['nilai'] !== null) {
                return $hasil;
            }
        }

        return ['nilai' => null, 'keyakinan' => 0.0];
    }

    /**
     * Cari satu tanggal pada satu baris teks.
     *
     * @return array{nilai: string|null, keyakinan: float}
     */
    private static function polaTanggal(string $teks, bool $berlabel): array
    {
        // "1 September 2026" / "01 September 2026".
        if (preg_match('/\b(\d{1,2})\s+('.implode('|', array_keys(self::BULAN)).')\s+(\d{4})\b/i', $teks, $m) === 1) {
            $tanggal = self::susunTanggal((int) $m[1], self::BULAN[strtolower($m[2])], (int) $m[3]);

            if ($tanggal !== null) {
                return ['nilai' => $tanggal, 'keyakinan' => $berlabel ? 0.9 : 0.55];
            }
        }

        // "2026-09-01".
        if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $teks, $m) === 1) {
            $tanggal = self::susunTanggal((int) $m[3], (int) $m[2], (int) $m[1]);

            if ($tanggal !== null) {
                return ['nilai' => $tanggal, 'keyakinan' => $berlabel ? 0.85 : 0.55];
            }
        }

        // "12/03/2026" atau "12-03-2026".
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/', $teks, $m) === 1) {
            $tanggal = self::susunTanggal((int) $m[1], (int) $m[2], (int) $m[3]);

            if ($tanggal !== null) {
                return ['nilai' => $tanggal, 'keyakinan' => $berlabel ? 0.65 : 0.45];
            }
        }

        return ['nilai' => null, 'keyakinan' => 0.0];
    }

    /**
     * @return array{nilai: string|null, keyakinan: float}
     */
    private static function uraikanTempat(string $teks): array
    {
        foreach ([
            '/tempat\s+kematian\s*[:\-]\s*(.+)$/imu' => 0.75,
            '/(?:meninggal\s+di|ditemukan\s+di|wafat\s+di)\s+(.+)$/imu' => 0.65,
        ] as $pola => $keyakinan) {
            if (preg_match($pola, $teks, $m) === 1) {
                $tempat = self::bersihkanBaris($m[1]);

                if ($tempat !== '') {
                    return ['nilai' => $tempat, 'keyakinan' => $keyakinan];
                }
            }
        }

        return ['nilai' => null, 'keyakinan' => 0.0];
    }

    /**
     * Buang sisa label di akhir nilai yang ikut tertangkap baris berikutnya.
     */
    private static function bersihkanBaris(string $nilai): string
    {
        // Ambil sampai akhir baris saja (nilai bisa saja di baris yang sama).
        $baris = preg_split('/\R/', trim($nilai)) ?: [];
        $teks = trim((string) ($baris[0] ?? ''));

        $teks = preg_replace('/\s+(nik|tanggal|tempat|jenis kelamin)\b.*$/i', '', $teks) ?? $teks;

        return trim($teks, " \t:;,-");
    }

    private static function susunTanggal(int $hari, int $bulan, int $tahun): ?string
    {
        if ($hari < 1 || $hari > 31 || $bulan < 1 || $bulan > 12) {
            return null;
        }

        try {
            return Carbon::createFromDate($tahun, $bulan, $hari)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
