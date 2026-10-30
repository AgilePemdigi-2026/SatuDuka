<?php

namespace App\Services\Verifikasi;

use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Memeriksa struktur dan konsistensi NIK tanpa mengakses basis data atau HTTP.
 *
 * Aturan mengikuti docs/ATURAN_VERIFIKASI.md bagian 3. Tahun empat digit tidak
 * pernah disimpulkan dari NIK; segmen YY hanya dicocokkan dengan dua digit
 * terakhir tanggal lahir lengkap.
 */
class LayananValidasiNik
{
    public const KODE_NIK_PANJANG_SALAH = 'NIK_PANJANG_SALAH';

    public const KODE_NIK_PROVINSI_TIDAK_DIKENAL = 'NIK_PROVINSI_TIDAK_DIKENAL';

    public const KODE_NIK_TANGGAL_TIDAK_VALID = 'NIK_TANGGAL_TIDAK_VALID';

    public const KODE_NIK_JENIS_KELAMIN_TIDAK_COCOK = 'NIK_JENIS_KELAMIN_TIDAK_COCOK';

    public const KODE_NIK_TAHUN_TIDAK_COCOK = 'NIK_TAHUN_TIDAK_COCOK';

    public const KODE_NIK_URUT_NOL = 'NIK_URUT_NOL';

    public const KODE_TANGGAL_TIDAK_KONSISTEN = 'TANGGAL_TIDAK_KONSISTEN';

    /**
     * @var array<string, string>
     */
    private array $daftarProvinsi;

    private int $batasUsia;

    private bool $izinkanNikDemo;

    /**
     * @var array<string, string>|null
     */
    private static ?array $simpananDaftarProvinsi = null;

    /**
     * @param  array<string, string>|null  $daftarProvinsi  Kode dua digit => nama provinsi.
     */
    public function __construct(
        ?array $daftarProvinsi = null,
        ?int $batasUsia = null,
        ?bool $izinkanNikDemo = null,
    ) {
        $this->daftarProvinsi = $daftarProvinsi ?? self::muatDaftarProvinsi();
        $this->batasUsia = $batasUsia ?? (int) config('verifikasi.batas_usia', 120);
        $this->izinkanNikDemo = $izinkanNikDemo ?? (bool) config('verifikasi.izinkan_nik_demo', false);
    }

    public function periksa(
        string $nik,
        ?string $jenisKelamin = null,
        ?CarbonInterface $tanggalLahir = null,
        ?CarbonInterface $tanggalKematian = null,
    ): HasilValidasiNik {
        $pelanggaran = [];

        if (preg_match('/^\d{16}$/', $nik) !== 1) {
            $pelanggaran[] = new PelanggaranNik(
                self::KODE_NIK_PANJANG_SALAH,
                PelanggaranNik::KEPARAHAN_FATAL,
                'NIK harus tepat 16 digit angka.',
            );

            return new HasilValidasiNik(
                pelanggaran: $pelanggaran,
                valid: false,
            );
        }

        $provinsi = substr($nik, 0, 2);
        $kabupaten = substr($nik, 2, 2);
        $kecamatan = substr($nik, 4, 2);
        $tanggalMentah = (int) substr($nik, 6, 2);
        $bulan = (int) substr($nik, 8, 2);
        $tahun = substr($nik, 10, 2);
        $nomorUrut = substr($nik, 12, 4);

        $perempuan = $tanggalMentah > 40;
        $hari = $perempuan ? $tanggalMentah - 40 : $tanggalMentah;
        $jenisKelaminNik = $perempuan ? 'P' : 'L';

        $namaProvinsi = $this->daftarProvinsi[$provinsi] ?? null;
        $nikDemo = $provinsi === '99';

        if (! ($nikDemo && $this->izinkanNikDemo) && $namaProvinsi === null) {
            $pelanggaran[] = new PelanggaranNik(
                self::KODE_NIK_PROVINSI_TIDAK_DIKENAL,
                PelanggaranNik::KEPARAHAN_FATAL,
                'Kode provinsi pada NIK tidak dikenal.',
            );
        }

        $tanggalNikValid = checkdate($bulan, $hari, 2000 + (int) $tahun);

        if (! $tanggalNikValid) {
            $pelanggaran[] = new PelanggaranNik(
                self::KODE_NIK_TANGGAL_TIDAK_VALID,
                PelanggaranNik::KEPARAHAN_FATAL,
                'Tanggal lahir pada NIK bukan tanggal kalender yang sah.',
            );
        }

        if ($jenisKelamin !== null) {
            $jenisKelaminPembanding = $this->normalisasiJenisKelamin($jenisKelamin);

            if ($jenisKelaminPembanding !== null && $jenisKelaminPembanding !== $jenisKelaminNik) {
                $pelanggaran[] = new PelanggaranNik(
                    self::KODE_NIK_JENIS_KELAMIN_TIDAK_COCOK,
                    PelanggaranNik::KEPARAHAN_PERINGATAN,
                    'Jenis kelamin pada NIK tidak cocok dengan data kependudukan atau dokumen.',
                );
            }
        }

        if ($tanggalLahir !== null && $tanggalNikValid) {
            $hariLahir = (int) $tanggalLahir->format('d');
            $bulanLahir = (int) $tanggalLahir->format('m');
            $tahunLahirDuaDigit = ((int) $tanggalLahir->format('Y')) % 100;

            if ($hari !== $hariLahir || $bulan !== $bulanLahir || (int) $tahun !== $tahunLahirDuaDigit) {
                $pelanggaran[] = new PelanggaranNik(
                    self::KODE_NIK_TAHUN_TIDAK_COCOK,
                    PelanggaranNik::KEPARAHAN_PERINGATAN,
                    'Tanggal, bulan, atau tahun pada NIK tidak cocok dengan tanggal lahir lengkap.',
                );
            }
        }

        if ($nomorUrut === '0000') {
            $pelanggaran[] = new PelanggaranNik(
                self::KODE_NIK_URUT_NOL,
                PelanggaranNik::KEPARAHAN_PERINGATAN,
                'Nomor urut pada NIK tidak boleh 0000.',
            );
        }

        if ($tanggalLahir !== null && $tanggalKematian !== null) {
            if ($tanggalLahir->greaterThan($tanggalKematian)) {
                $pelanggaran[] = new PelanggaranNik(
                    self::KODE_TANGGAL_TIDAK_KONSISTEN,
                    PelanggaranNik::KEPARAHAN_FATAL,
                    'Tanggal lahir harus sebelum atau sama dengan tanggal kematian.',
                );
            } else {
                $usia = (int) $tanggalLahir->diffInYears($tanggalKematian);

                if ($usia > $this->batasUsia) {
                    $pelanggaran[] = new PelanggaranNik(
                        self::KODE_TANGGAL_TIDAK_KONSISTEN,
                        PelanggaranNik::KEPARAHAN_FATAL,
                        'Usia berdasarkan tanggal lahir dan tanggal kematian di luar rentang 0 sampai '
                            .$this->batasUsia.' tahun.',
                    );
                }
            }
        }

        return new HasilValidasiNik(
            pelanggaran: $pelanggaran,
            valid: $pelanggaran === [],
            provinsi: $provinsi,
            namaProvinsi: $namaProvinsi,
            kabupaten: $kabupaten,
            kecamatan: $kecamatan,
            hari: $hari,
            bulan: $bulan,
            tahun: $tahun,
            jenisKelamin: $jenisKelaminNik,
            nomorUrut: $nomorUrut,
        );
    }

    private function normalisasiJenisKelamin(string $jenisKelamin): ?string
    {
        $nilai = strtoupper(trim($jenisKelamin));

        return match ($nilai) {
            'L', 'LAKI-LAKI', 'LAKI LAKI', 'PRIA', 'MALE' => 'L',
            'P', 'PEREMPUAN', 'WANITA', 'FEMALE' => 'P',
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private static function muatDaftarProvinsi(): array
    {
        if (self::$simpananDaftarProvinsi !== null) {
            return self::$simpananDaftarProvinsi;
        }

        $jalur = database_path('data/kode_provinsi.json');

        if (! is_file($jalur)) {
            throw new RuntimeException('Daftar kode provinsi tidak ditemukan: '.$jalur);
        }

        $isi = json_decode((string) file_get_contents($jalur), true);

        if (! is_array($isi)) {
            throw new RuntimeException('Daftar kode provinsi tidak dapat dibaca: '.$jalur);
        }

        $daftar = [];

        foreach ($isi as $baris) {
            if (! is_array($baris) || ! isset($baris['kode'], $baris['nama'])) {
                continue;
            }

            $daftar[(string) $baris['kode']] = (string) $baris['nama'];
        }

        if ($daftar === []) {
            throw new RuntimeException('Daftar kode provinsi kosong: '.$jalur);
        }

        return self::$simpananDaftarProvinsi = $daftar;
    }
}
