<?php

namespace App\Services\Dokumen;

use App\Models\BerkasPendukung;
use App\Support\DataSIAK;
use App\Support\NikDummy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Pembuat isi berkas pendukung dummy (PDF teks via dompdf, gambar via GD).
 *
 * Tujuan: seeder dan perintah `berkas:contoh` menghasilkan berkas nyata di
 * disk privat sehingga alur analisis AI (parser PDF maupun tesseract OCR)
 * dapat diuji tanpa unggahan manual. PDF dibuat sebagai teks nyata agar
 * smalot/pdfparser dapat membacanya; gambar dibuat sederhana agar tesseract
 * dapat membaca labelnya.
 */
class LayananBerkasContoh
{
    /**
     * Lebar dan tinggi gambar contoh (piksel).
     */
    private const GAMBAR_LEBAR = 1200;

    private const GAMBAR_TINGGI = 620;

    public function __construct(private readonly PembuatPdf $pembuatPdf) {}

    /**
     * Pastikan berkas memiliki isi di disk; berkas yang sudah ada dipakai ulang.
     *
     * @return bool True bila berkas tersedia (sudah ada atau baru dibuat).
     */
    public function isi(BerkasPendukung $berkas, bool $paksa = false): bool
    {
        if (! $paksa && $berkas->path_file !== '' && Storage::disk('local')->exists($berkas->path_file)) {
            return true;
        }

        $ekstensi = strtolower(pathinfo($berkas->path_file, PATHINFO_EXTENSION));

        try {
            $isi = $ekstensi === 'pdf'
                ? $this->pembuatPdf->hasil('dokumen.berkas_contoh_pdf', $this->dataDokumen($berkas))
                : $this->gambar($berkas, $ekstensi);
        } catch (\Throwable $galat) {
            report($galat);

            return false;
        }

        if (! Storage::disk('local')->put($berkas->path_file, $isi)) {
            return false;
        }

        if ($berkas->status_unggahan !== BerkasPendukung::STATUS_BERHASIL) {
            $berkas->forceFill([
                'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
                'tanggal_unggah' => now(),
            ])->save();
        }

        return true;
    }

    /**
     * Isi seluruh baris berkas yang berkasnya belum ada di disk.
     *
     * @return array{dibuat: int, lewati: int, gagal: int}
     */
    public function isiSemuaYangKurang(?int $idLaporan = null): array
    {
        $kueri = BerkasPendukung::query()->with('laporanKematian');

        if ($idLaporan !== null) {
            $kueri->where('id_laporan', $idLaporan);
        }

        $hasil = ['dibuat' => 0, 'lewati' => 0, 'gagal' => 0];

        foreach ($kueri->orderBy('id_berkas')->get() as $berkas) {
            if ($berkas->path_file !== '' && Storage::disk('local')->exists($berkas->path_file)) {
                $hasil['lewati']++;

                continue;
            }

            $berhasil = $this->isi($berkas);

            $hasil[$berhasil ? 'dibuat' : 'gagal']++;
        }

        return $hasil;
    }

    /**
     * Data tampilan untuk PDF contoh sesuai jenis dokumen.
     *
     * Kartu identitas (KTP/KK) dirender sebagai fotokopi berbentuk surat agar
     * berkas dengan ekstensi .pdf tetap memiliki konten yang dapat dibaca.
     *
     * @return array{judul: string, instansi: string, alamatInstansi: string, nomor: string, baris: list<array{label: string, nilai: string}>, paragraf: list<string>, ttd: array{kota: string, tanggal: string, jabatan: string, nama: string, nip: string}}
     */
    private function dataDokumen(BerkasPendukung $berkas): array
    {
        $data = $this->dataDasar($berkas);

        return match ($berkas->jenis_dokumen) {
            BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN => [
                'instansi' => 'RSUD Kota Yogyakarta',
                'alamatInstansi' => 'Jl. Marhaen No. 4, Kota Yogyakarta (data dummy)',
                'judul' => 'SURAT KETERANGAN KEMATIAN',
                'nomor' => 'SKK-'.$data['nomor'],
                'baris' => [
                    ['label' => 'Nama Almarhum/Almarhumah', 'nilai' => $data['nama_almarhum']],
                    ['label' => 'NIK', 'nilai' => $data['nik_almarhum']],
                    ['label' => 'Tanggal Kematian', 'nilai' => $data['tanggal_kematian_panjang']],
                    ['label' => 'Tempat Kematian', 'nilai' => $data['tempat_kematian']],
                    ['label' => 'Sebab Kematian', 'nilai' => $data['sebab_kematian']],
                ],
                'paragraf' => ['Pasien dinyatakan meninggal dunia di rumah sakit ini sesuai catatan medis yang tersedia.'],
                'ttd' => ['kota' => 'Yogyakarta', 'tanggal' => $data['tanggal_sekarang'], 'jabatan' => 'Dokter Pemeriksa', 'nama' => 'dr. Contoh Dokter', 'nip' => '999999901234567001'],
            ],
            BerkasPendukung::JENIS_SURAT_KETERANGAN_KELURAHAN => [
                'instansi' => 'KELURAHAN GONDOKUSUMAN KOTA YOGYAKARTA',
                'alamatInstansi' => 'Jl. Malioboro No. 1, Kota Yogyakarta (data dummy)',
                'judul' => 'SURAT KETERANGAN KELURAHAN',
                'nomor' => 'SKKLR-'.$data['nomor'],
                'baris' => [
                    ['label' => 'Nama Pelapor', 'nilai' => $data['nama_pelapor']],
                    ['label' => 'Nama Almarhum/Almarhumah', 'nilai' => $data['nama_almarhum']],
                    ['label' => 'NIK Almarhum', 'nilai' => $data['nik_almarhum']],
                    ['label' => 'Tanggal Kematian', 'nilai' => $data['tanggal_kematian_panjang']],
                    ['label' => 'Tempat Kematian', 'nilai' => $data['tempat_kematian']],
                ],
                'paragraf' => ['Berdasarkan keterangan keluarga, warga di atas benar berdomisili di wilayah kelurahan ini.'],
                'ttd' => ['kota' => 'Yogyakarta', 'tanggal' => $data['tanggal_sekarang'], 'jabatan' => 'Lurah Gondokusuman', 'nama' => 'Contoh Lurah', 'nip' => '999999901234567002'],
            ],
            BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN => [
                'instansi' => 'SPTJM KEBENARAN DATA KEMATIAN (SIMULASI)',
                'alamatInstansi' => 'Surat Pernyataan Tanggung Jawab Mutlak - data dummy',
                'judul' => 'SPTJM KEBENARAN DATA KEMATIAN',
                'nomor' => 'SPTJM-'.$data['nomor'],
                'baris' => [
                    ['label' => 'Nama Pelapor', 'nilai' => $data['nama_pelapor']],
                    ['label' => 'NIK Pelapor', 'nilai' => $data['nik_pelapor']],
                    ['label' => 'Nama Almarhum/Almarhumah', 'nilai' => $data['nama_almarhum']],
                    ['label' => 'NIK Almarhum', 'nilai' => $data['nik_almarhum']],
                    ['label' => 'Tanggal Kematian', 'nilai' => $data['tanggal_kematian_panjang']],
                    ['label' => 'Tempat Kematian', 'nilai' => $data['tempat_kematian']],
                ],
                'paragraf' => ['Saya menyatakan dengan sesungguhnya bahwa seluruh data kematian di atas adalah benar dan saya bersedia memikul segala akibat hukum apabila di kemudian hari dinyatakan tidak benar.'],
                'ttd' => ['kota' => 'Yogyakarta', 'tanggal' => $data['tanggal_sekarang'], 'jabatan' => 'Pelapor', 'nama' => $data['nama_pelapor'], 'nip' => '-'],
            ],
            BerkasPendukung::JENIS_SURAT_KETERANGAN_KEPOLISIAN => [
                'instansi' => 'POLSEK KOTA YOGYAKARTA (SIMULASI)',
                'alamatInstansi' => 'Jl. Surya No. 2, Kota Yogyakarta (data dummy)',
                'judul' => 'SURAT KETERANGAN KEPOLISIAN',
                'nomor' => 'SKP-'.$data['nomor'],
                'baris' => [
                    ['label' => 'Jenis Kasus', 'nilai' => $data['jenis_kasus']],
                    ['label' => 'Nama Almarhum/Almarhumah', 'nilai' => $data['nama_almarhum']],
                    ['label' => 'NIK Almarhum', 'nilai' => $data['nik_almarhum']],
                    ['label' => 'Tanggal Kematian', 'nilai' => $data['tanggal_kematian_panjang']],
                    ['label' => 'Tempat Kematian', 'nilai' => $data['tempat_kematian']],
                ],
                'paragraf' => ['Kepolisian menerangkan bahwa penanganan kasus di atas telah diadministrasikan pada segmen kasus tidak dikenal/tidak ditemukan.'],
                'ttd' => ['kota' => 'Yogyakarta', 'tanggal' => $data['tanggal_sekarang'], 'jabatan' => 'Kepala Polsek', 'nama' => 'AKP Contoh Polisi', 'nip' => '999999901234567003'],
            ],
            BerkasPendukung::JENIS_KARTU_KELUARGA => [
                'instansi' => 'FOTOKOPI KARTU KELUARGA (SIMULASI)',
                'alamatInstansi' => 'Dinas Kependudukan dan Pencatatan Sipil - data dummy',
                'judul' => 'FOTOKOPI KARTU KELUARGA',
                'nomor' => 'KK-'.$data['nomor'],
                'baris' => [
                    ['label' => 'Nomor KK', 'nilai' => $data['nik_kk']],
                    ['label' => 'Nama Kepala Keluarga', 'nilai' => $data['nama_pelapor']],
                    ['label' => 'NIK Almarhum', 'nilai' => $data['nik_almarhum']],
                    ['label' => 'Nama Almarhum', 'nilai' => $data['nama_almarhum']],
                ],
                'paragraf' => ['Fotokopi kartu keluarga simulasi memuat anggota keluarga terkait.'],
                'ttd' => ['kota' => 'Yogyakarta', 'tanggal' => $data['tanggal_sekarang'], 'jabatan' => 'Pejabat Penanggung Jawab', 'nama' => 'Contoh Petugas', 'nip' => '999999901234567004'],
            ],
            default => [
                'instansi' => 'FOTOKOPI KARTU TANDA PENDUDUK (SIMULASI)',
                'alamatInstansi' => 'Dinas Kependudukan dan Pencatatan Sipil - data dummy',
                'judul' => 'FOTOKOPI KARTU TANDA PENDUDUK',
                'nomor' => 'KTP-'.$data['nomor'],
                'baris' => [
                    ['label' => 'NIK', 'nilai' => $berkas->jenis_dokumen === BerkasPendukung::JENIS_KTP_ALMARHUM
                        ? $data['nik_almarhum']
                        : $data['nik_pelapor']],
                    ['label' => 'Nama', 'nilai' => $berkas->jenis_dokumen === BerkasPendukung::JENIS_KTP_ALMARHUM
                        ? $data['nama_almarhum']
                        : $data['nama_pelapor']],
                    ['label' => 'Tempat/Tanggal Lahir', 'nilai' => $data['lahir']],
                    ['label' => 'Jenis Kelamin', 'nilai' => $data['jenis_kelamin']],
                    ['label' => 'Alamat', 'nilai' => $data['alamat']],
                ],
                'paragraf' => ['Fotokopi KTP simulasi untuk pengujian analisis dokumen.'],
                'ttd' => ['kota' => 'Yogyakarta', 'tanggal' => $data['tanggal_sekarang'], 'jabatan' => 'Pejabat Penanggung Jawab', 'nama' => 'Contoh Petugas', 'nip' => '999999901234567005'],
            ],
        };
    }

    /**
     * Gambar contoh (KTP/KK) yang dapat dibaca tesseract.
     *
     * @return string Isi PNG atau JPG biner.
     */
    private function gambar(BerkasPendukung $berkas, string $ekstensi): string
    {
        $data = $this->dataDasar($berkas);

        $gambar = imagecreatetruecolor(self::GAMBAR_LEBAR, self::GAMBAR_TINGGI);
        $putih = imagecolorallocate($gambar, 255, 255, 255);
        $hitam = imagecolorallocate($gambar, 15, 23, 42);
        $abu = imagecolorallocate($gambar, 100, 116, 139);

        imagefill($gambar, 0, 0, $putih);
        imagerectangle($gambar, 8, 8, self::GAMBAR_LEBAR - 9, self::GAMBAR_TINGGI - 9, $hitam);

        [$judul, $baris] = match ($berkas->jenis_dokumen) {
            BerkasPendukung::JENIS_KTP_ALMARHUM => [
                'KARTU TANDA PENDUDUK (DUMMY)',
                [
                    ['NIK', $data['nik_almarhum']],
                    ['Nama', $data['nama_almarhum']],
                    ['Tempat/Tgl Lahir', $data['lahir']],
                    ['Jenis Kelamin', $data['jenis_kelamin']],
                    ['Alamat', $data['alamat']],
                ],
            ],
            BerkasPendukung::JENIS_KTP_PELAPOR, BerkasPendukung::JENIS_KTP_SAKSI_1, BerkasPendukung::JENIS_KTP_SAKSI_2 => [
                'KARTU TANDA PENDUDUK (DUMMY)',
                [
                    ['NIK', $data['nik_pelapor']],
                    ['Nama', $data['nama_pelapor']],
                    ['Tempat/Tgl Lahir', 'Yogyakarta, 05-06-1988'],
                    ['Jenis Kelamin', 'LAKI-LAKI'],
                    ['Alamat', 'Jl. Contoh No. 8, Kota Yogyakarta'],
                ],
            ],
            BerkasPendukung::JENIS_KARTU_KELUARGA => [
                'KARTU KELUARGA (DUMMY)',
                [
                    ['Nomor KK', $data['nik_kk']],
                    ['Nama Kepala Keluarga', $data['nama_pelapor']],
                    ['NIK Almarhum', $data['nik_almarhum']],
                    ['Nama Almarhum', $data['nama_almarhum']],
                    ['Hubungan', 'Anggota keluarga (almarhum/almarhumah)'],
                ],
            ],
            BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN => [
                'SURAT KETERANGAN KEMATIAN (GAMBAR DUMMY)',
                [
                    ['Nama Almarhum/Almarhumah', $data['nama_almarhum']],
                    ['NIK', $data['nik_almarhum']],
                    ['Tanggal Kematian', $data['tanggal_kematian_panjang']],
                    ['Tempat Kematian', $data['tempat_kematian']],
                    ['Sebab Kematian', $data['sebab_kematian']],
                ],
            ],
            BerkasPendukung::JENIS_SPTJM_KEBENARAN_DATA_KEMATIAN => [
                'SPTJM KEBENARAN DATA KEMATIAN (GAMBAR DUMMY)',
                [
                    ['Nama Pelapor', $data['nama_pelapor']],
                    ['NIK Pelapor', $data['nik_pelapor']],
                    ['Nama Almarhum', $data['nama_almarhum']],
                    ['NIK Almarhum', $data['nik_almarhum']],
                    ['Tanggal Kematian', $data['tanggal_kematian_panjang']],
                ],
            ],
            BerkasPendukung::JENIS_SURAT_KETERANGAN_KELURAHAN => [
                'SURAT KETERANGAN KELURAHAN (GAMBAR DUMMY)',
                [
                    ['Nama Pelapor', $data['nama_pelapor']],
                    ['Nama Almarhum', $data['nama_almarhum']],
                    ['NIK Almarhum', $data['nik_almarhum']],
                    ['Tanggal Kematian', $data['tanggal_kematian_panjang']],
                ],
            ],
            default => [
                'SURAT KETERANGAN KEPOLISIAN (GAMBAR DUMMY)',
                [
                    ['Jenis Kasus', $data['jenis_kasus']],
                    ['Nama Almarhum', $data['nama_almarhum']],
                    ['NIK Almarhum', $data['nik_almarhum']],
                    ['Tanggal Kematian', $data['tanggal_kematian_panjang']],
                    ['Tempat Kematian', $data['tempat_kematian']],
                ],
            ],
        };

        $this->tulisTeks($gambar, $judul, 60, 46, 5, $hitam);
        $y = 110;

        foreach ($baris as [$label, $nilai]) {
            $this->tulisTeks($gambar, $label.' :', 60, $y, 4, $abu);
            $this->tulisTeks($gambar, (string) $nilai, 340, $y, 4, $hitam);
            $y += 64;
        }

        $this->tulisTeks($gambar, 'Dokumen dummy SATUDUKA untuk pengujian OCR.', 60, self::GAMBAR_TINGGI - 46, 2, $abu);

        ob_start();

        if ($ekstensi === 'jpg' || $ekstensi === 'jpeg') {
            imagejpeg($gambar, null, 92);
        } else {
            imagepng($gambar, null, 6);
        }

        $isi = (string) ob_get_clean();
        imagedestroy($gambar);

        if ($isi === '') {
            throw new RuntimeException('Gambar contoh gagal dirender.');
        }

        return $isi;
    }

    /**
     * Tulis satu baris teks ASCII pada gambar.
     */
    private function tulisTeks($gambar, string $teks, int $x, int $y, int $ukuran, int $warna): void
    {
        // Font bawaan GD hanya mengenal ASCII; karakter lain diubah agar tidak rusak.
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $teks) ?: preg_replace('/[^\x20-\x7E]/', '?', $teks) ?? $teks;

        imagestring($gambar, max(1, min(5, $ukuran)), $x, $y, $ascii, $warna);
    }

    /**
     * Data dasar konten contoh: utamakan laporan pemohon, fallback data SIAK acak.
     *
     * @return array<string, string>
     */
    private function dataDasar(BerkasPendukung $berkas): array
    {
        $laporan = $berkas->laporanKematian;
        $siak = $laporan !== null ? DataSIAK::cariNik((string) $laporan->nik_almarhum) : null;

        $namaAlmarhum = $laporan?->nama_almarhum ?: ($siak['nama_lengkap'] ?? 'Suharto Wiryo');
        $nikAlmarhum = $laporan?->nik_almarhum ?: ($siak['nik'] ?? NikDummy::denganUrutan(9000 + (int) $berkas->id_berkas));
        $namaPelapor = $laporan?->nama_pelapor ?: 'Pemohon Contoh';
        $tanggalKematian = $laporan?->tanggal_kematian
            ?? Carbon::parse('2026-09-01');

        return [
            'nomor' => '2026-'.str_pad((string) $berkas->id_berkas, 4, '0', STR_PAD_LEFT),
            'nama_almarhum' => $namaAlmarhum,
            'nik_almarhum' => (string) $nikAlmarhum,
            'nama_pelapor' => $namaPelapor,
            'nik_pelapor' => NikDummy::denganUrutan(5000 + (int) $berkas->id_berkas, 5, 6, 1988),
            'nik_kk' => NikDummy::denganUrutan(7000 + (int) $berkas->id_berkas, 10, 10, 1975),
            'tanggal_kematian_panjang' => $tanggalKematian->translatedFormat('j F Y'),
            'tempat_kematian' => $laporan?->tempat_kematian ?: 'RSUD Kota Yogyakarta',
            'sebab_kematian' => $laporan?->sebab_kematian ?: 'Gagal jantung akut (data dummy)',
            'jenis_kasus' => $laporan?->jenis_kasus ?? 'Jenazah Tidak Dikenal',
            'jenis_kelamin' => strtoupper(($siak['jenis_kelamin'] ?? 'L') === 'P' ? 'PEREMPUAN' : 'LAKI-LAKI'),
            'lahir' => isset($siak['tempat_lahir'], $siak['tanggal_lahir'])
                ? $siak['tempat_lahir'].', '.Carbon::parse($siak['tanggal_lahir'])->format('d-m-Y')
                : 'Yogyakarta, 12-03-1955',
            'alamat' => $siak['alamat'] ?? 'Jl. Malioboro No. 12, Kota Yogyakarta',
            'tanggal_sekarang' => now()->translatedFormat('j F Y'),
        ];
    }
}
