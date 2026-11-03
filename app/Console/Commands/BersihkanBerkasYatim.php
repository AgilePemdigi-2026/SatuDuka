<?php

namespace App\Console\Commands;

use App\Models\BerkasPendukung;
use App\Models\DokumenResmi;
use App\Models\Pengguna;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BersihkanBerkasYatim extends Command
{
    /**
     * Nama dan opsi perintah.
     *
     * @var string
     */
    protected $signature = 'berkas:bersihkan
        {--dry-run : Tampilkan berkas yang akan dihapus tanpa menghapusnya}';

    /**
     * Deskripsi perintah.
     *
     * @var string
     */
    protected $description = 'Menghapus berkas unggahan yang tidak direferensikan basis data';

    /**
     * Jalankan perintah.
     */
    public function handle(): int
    {
        $disk = Storage::disk('local');

        $terpakai = BerkasPendukung::query()
            ->whereNotNull('path_file')
            ->pluck('path_file')
            ->merge(
                Pengguna::query()
                    ->whereNotNull('path_foto')
                    ->pluck('path_foto')
            )
            ->merge(
                DokumenResmi::query()
                    ->whereNotNull('file_dokumen')
                    ->pluck('file_dokumen')
            )
            ->filter()
            ->unique()
            ->flip();

        $yatim = collect($disk->allFiles())
            ->reject(fn (string $path): bool => $path === '.gitignore' || $terpakai->has($path))
            ->values();

        if ($yatim->isEmpty()) {
            $this->info('Tidak ada berkas yatim.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info($yatim->count().' berkas yatim ditemukan:');
            $yatim->each(fn (string $path) => $this->line('- '.$path));

            return self::SUCCESS;
        }

        $disk->delete($yatim->all());

        $this->info($yatim->count().' berkas yatim dihapus.');

        return self::SUCCESS;
    }
}
