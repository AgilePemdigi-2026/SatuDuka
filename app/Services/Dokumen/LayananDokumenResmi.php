<?php

namespace App\Services\Dokumen;

use App\Models\DokumenResmi;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Services\LayananAudit;
use App\Support\DataSIAK;
use App\Support\PesanGalat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Penerbitan berkas PDF Kutipan Akta Kematian.
 *
 * Aturan penting:
 * - idempoten: nomor dokumen hanya dibuat sekali per dokumen dan dipakai ulang;
 *   berkas yang sudah ada tidak pernah ditimpa;
 * - urutan aman: render di memori, simpan berkas, lalu isi `file_dokumen` di
 *   basis data dalam transaksi; bila update gagal, berkas dihapus;
 * - nama berkas hanya memakai nomor dokumen atau UUID, tidak pernah NIK atau
 *   masukan pengguna;
 * - kegagalan tidak melempar ke pemanggil dan tidak mengubah status pengajuan.
 */
class LayananDokumenResmi
{
    /**
     * Disk privat tempat dokumen resmi disimpan.
     */
    public const DISK = 'local';

    /**
     * Awalan direktori dokumen.
     */
    public const DIREKTORI = 'dokumen';

    public function __construct(private readonly PembuatPdf $pembuatPdf) {}

    /**
     * Terbitkan berkas PDF untuk dokumen yang sudah ditandatangani.
     *
     * @return bool True bila berkas tersedia (baru dibuat atau sudah ada).
     */
    public function terbitkan(DokumenResmi $dokumen): bool
    {
        $dokumen->refresh();

        // Idempoten: berkas resmi yang sudah ada tidak boleh ditimpa.
        if (filled($dokumen->file_dokumen)) {
            return true;
        }

        $laporan = $dokumen->laporanKematian;

        if (! $laporan instanceof LaporanKematian
            || $laporan->status_pengajuan !== LaporanKematian::STATUS_SELESAI
            || $dokumen->status_ttd !== DokumenResmi::STATUS_SELESAI) {
            return false;
        }

        try {
            $nomor = $this->pastikanNomor($dokumen);
        } catch (Throwable $galat) {
            $this->catatGagal($dokumen, $galat);

            return false;
        }

        $jalur = null;

        try {
            $isi = $this->pembuatPdf->hasil('dokumen.akta_pdf', $this->dataTampilan($dokumen, $laporan, $nomor));

            $jalur = self::DIREKTORI.'/'.$this->namaBerkas($nomor);
            Storage::disk(self::DISK)->put($jalur, $isi);
        } catch (Throwable $galat) {
            $this->hapusBerkas($jalur);
            $this->catatGagal($dokumen, $galat);

            return false;
        }

        try {
            DB::transaction(function () use ($dokumen, $jalur): void {
                $terkunci = DokumenResmi::query()
                    ->whereKey($dokumen->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($terkunci === null || filled($terkunci->file_dokumen)) {
                    return;
                }

                $terkunci->update(['file_dokumen' => $jalur]);
            });
        } catch (Throwable $galat) {
            $this->hapusBerkas($jalur);
            $this->catatGagal($dokumen, $galat);

            return false;
        }

        $dokumen->refresh();

        // Balapan: proses lain lebih dulu mengisi berkas; berkas kita yatim.
        if ($dokumen->file_dokumen !== $jalur) {
            $this->hapusBerkas($jalur);

            return filled($dokumen->file_dokumen);
        }

        LayananAudit::catat(
            aksi: 'menerbitkan_dokumen',
            objek: 'dokumen_resmi',
            idObjek: $dokumen->id_dokumen,
            detail: [
                'no_dokumen' => $nomor,
                'no_pengajuan' => $laporan->no_pengajuan,
                'penyedia_tte' => (string) config('satuduka.tte_driver', 'mock'),
            ],
            hasil: LogAudit::HASIL_SUKSES,
        );

        return true;
    }

    /**
     * Hapus rujukan berkas yang hilang dari disk agar dapat diterbitkan ulang.
     *
     * Nomor dokumen dipertahankan sehingga tidak ada nomor baru yang dikonsumsi.
     */
    public function bersihkanBerkasHilang(DokumenResmi $dokumen): bool
    {
        $jalur = $dokumen->file_dokumen;

        if (blank($jalur)) {
            return false;
        }

        if (Storage::disk(self::DISK)->exists($jalur)) {
            return false;
        }

        $dokumen->forceFill(['file_dokumen' => null])->save();

        return true;
    }

    /**
     * Pastikan dokumen memiliki nomor; nomor lama selalu dipakai ulang.
     */
    private function pastikanNomor(DokumenResmi $dokumen): string
    {
        if (filled($dokumen->no_dokumen)) {
            return (string) $dokumen->no_dokumen;
        }

        return LayananNomorDokumen::buat(function (string $kandidat) use ($dokumen): string {
            $terkunci = DokumenResmi::query()
                ->whereKey($dokumen->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (blank($terkunci->no_dokumen)) {
                $terkunci->update(['no_dokumen' => $kandidat]);
            }

            return (string) $terkunci->no_dokumen;
        });
    }

    /**
     * Nama berkas aman: hanya nomor dokumen (sudah dibatasi formatnya).
     */
    private function namaBerkas(string $nomor): string
    {
        $aman = preg_replace('/[^A-Za-z0-9\-_]/', '', $nomor) ?? '';

        if ($aman === '') {
            $aman = (string) Str::uuid();
        }

        return $aman.'.pdf';
    }

    /**
     * @return array<string, mixed>
     */
    private function dataTampilan(DokumenResmi $dokumen, LaporanKematian $laporan, string $nomor): array
    {
        $siak = DataSIAK::cariNik((string) $laporan->nik_almarhum) ?? [];

        $tanggalLahir = null;
        $umur = null;

        if (filled($siak['tanggal_lahir'] ?? null)) {
            try {
                $lahir = Carbon::parse((string) $siak['tanggal_lahir']);
                $tanggalLahir = $lahir->translatedFormat('j F Y');

                if ($laporan->tanggal_kematian !== null) {
                    $umur = (int) $lahir->diffInYears($laporan->tanggal_kematian);
                }
            } catch (Throwable) {
                $tanggalLahir = null;
                $umur = null;
            }
        }

        return [
            'nomorDokumen' => $nomor,
            'laporan' => $laporan,
            'dokumen' => $dokumen,
            'pejabat' => $dokumen->pejabat,
            'siak' => $siak,
            'tanggalLahir' => $tanggalLahir,
            'umur' => $umur,
            'tanggalKematian' => $laporan->tanggal_kematian?->translatedFormat('j F Y'),
            'tanggalTerbit' => $dokumen->tanggal_terbit?->translatedFormat('j F Y'),
            'alamat' => $siak['alamat'] ?? ($laporan->pemohon?->alamat ?: null),
            'mock' => (string) config('satuduka.tte_driver', 'mock') === 'mock',
        ];
    }

    private function hapusBerkas(?string $jalur): void
    {
        if ($jalur === null || $jalur === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($jalur);
    }

    private function catatGagal(DokumenResmi $dokumen, Throwable $galat): void
    {
        LayananAudit::catat(
            aksi: 'gagal_menerbitkan_dokumen',
            objek: 'dokumen_resmi',
            idObjek: $dokumen->id_dokumen,
            detail: PesanGalat::ringkas($galat),
            hasil: LogAudit::HASIL_GAGAL,
        );
    }
}
