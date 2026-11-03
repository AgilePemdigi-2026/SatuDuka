<?php

namespace App\Console\Commands;

use App\Support\PenyelarasNik;
use Illuminate\Console\Command;
use LogicException;

/**
 * Menyelaraskan enkripsi NIK dan blind index.
 *
 * Perintah ini tidak pernah mencetak nilai NIK maupun kunci; hanya jumlah
 * baris. Gunakan `--dry-run` sebelum migrasi untuk melihat dampaknya.
 */
class EnkripsiUlangNik extends Command
{
    protected $signature = 'nik:enkripsi-ulang
        {--dry-run : Hanya laporkan jumlah baris, tanpa mengubah data}
        {--hanya-hash : Hitung ulang hash saja (rotasi kunci hash)}
        {--enkripsi-ulang : Enkripsi ulang nilai yang sudah terenkripsi (rotasi APP_KEY)}';

    protected $description = 'Isi blind index dan enkripsi ulang NIK pada data lama';

    public function handle(): int
    {
        if ($this->option('hanya-hash') && $this->option('enkripsi-ulang')) {
            $this->error('Pilih salah satu: --hanya-hash atau --enkripsi-ulang.');

            return self::FAILURE;
        }

        try {
            if ($this->option('dry-run')) {
                $this->laporkan($this->dryRun(), 'Pratinjau (tidak ada data diubah)');

                return self::SUCCESS;
            }

            $hasil = PenyelarasNik::jalankan(
                hanyaHash: (bool) $this->option('hanya-hash'),
                enkripsiUlang: (bool) $this->option('enkripsi-ulang'),
            );
        } catch (LogicException $galat) {
            $this->error($galat->getMessage());

            return self::FAILURE;
        }

        $this->laporkan($hasil, 'Selesai');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array<string, int>>  $hasil
     */
    private function laporkan(array $hasil, string $judul): void
    {
        $this->info($judul);

        $baris = [];

        foreach ($hasil as $tabel => $angka) {
            $baris[] = [
                $tabel,
                $angka['terisi'] ?? $angka,
                $angka['belum_terenkripsi'] ?? '—',
                $angka['hash_perlu_diperbarui'] ?? '—',
            ];
        }

        $this->table(
            ['Tabel', 'Baris diproses', 'Belum terenkripsi', 'Hash perlu diperbarui'],
            $baris,
        );
    }

    /**
     * @return array<string, array<string, int>>
     */
    private function dryRun(): array
    {
        return PenyelarasNik::dryRun();
    }
}
