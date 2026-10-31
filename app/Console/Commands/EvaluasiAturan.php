<?php

namespace App\Console\Commands;

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Models\ReferensiWilayah;
use App\Services\Verifikasi\LayananAturanBerkas;
use App\Services\Verifikasi\LayananPencocokNama;
use App\Services\Verifikasi\LayananValidasiNik;
use App\Services\Verifikasi\MesinRekonsiliasi;
use Database\Seeders\ReferensiWilayahSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Mengevaluasi MesinRekonsiliasi terhadap kasus uji berlabel Fase 6.8.
 *
 * Label berasal dari rancangan kasus uji sehingga evaluasi menguji kebenaran
 * implementasi aturan, bukan akurasi terhadap data kependudukan nyata.
 */
class EvaluasiAturan extends Command
{
    /**
     * Nama dan opsi perintah.
     *
     * @var string
     */
    protected $signature = 'aturan:evaluasi
        {--dry-run : Tampilkan laporan tanpa menulis docs/EVALUASI_ATURAN.md}';

    /**
     * Deskripsi perintah.
     *
     * @var string
     */
    protected $description = 'Mengevaluasi mesin aturan verifikasi pada kasus uji berlabel';

    /**
     * Jalankan perintah.
     */
    public function handle(): int
    {
        $kasus = $this->muatKasus();
        $this->pastikanReferensiWilayah();

        $evaluasi = $this->evaluasi($kasus);
        $kalibrasi = $this->kalibrasiNama();
        $laporan = $this->susunMarkdown($kasus, $evaluasi, $kalibrasi);

        if ($this->option('dry-run')) {
            $this->line($laporan);

            return self::SUCCESS;
        }

        $jalur = base_path('docs/EVALUASI_ATURAN.md');
        file_put_contents($jalur, $laporan);

        $this->info('Laporan evaluasi ditulis ke '.$jalur);

        return self::SUCCESS;
    }

    /**
     * Memuat kasus uji dari database/data/kasus_uji_aturan.json.
     *
     * @return list<array<string, mixed>>
     */
    public function muatKasus(): array
    {
        $jalur = database_path('data/kasus_uji_aturan.json');

        if (! is_file($jalur)) {
            throw new RuntimeException('Berkas kasus uji tidak ditemukan: '.$jalur);
        }

        $data = json_decode((string) file_get_contents($jalur), true);

        if (! is_array($data) || ! isset($data['kasus']) || ! is_array($data['kasus'])) {
            throw new RuntimeException('Berkas kasus uji tidak dapat dibaca: '.$jalur);
        }

        return array_values($data['kasus']);
    }

    /**
     * Menjalankan mesin pada setiap kasus dan menghitung metrik per kode.
     *
     * Setiap kasus berjalan dalam transaksi yang diakhiri rollback agar data
     * uji tidak tersimpan dan tidak memengaruhi kasus berikutnya.
     *
     * @param  list<array<string, mixed>>  $kasus
     * @return array<string, mixed>
     */
    public function evaluasi(array $kasus): array
    {
        $kodeTaksonomi = array_keys((array) config('verifikasi.taksonomi_anomali', []));

        $perKode = [];
        foreach ($kodeTaksonomi as $kode) {
            $perKode[$kode] = [
                'dukungan' => 0,
                'benar_positif' => 0,
                'positif_palsu' => 0,
                'negatif_palsu' => 0,
                'benar_negatif' => 0,
            ];
        }

        $matriksNama = [
            'PERSIS' => ['PERSIS' => 0, 'BEDA_TIPIS' => 0, 'TIDAK_COCOK' => 0, 'NA' => 0],
            'BEDA_TIPIS' => ['PERSIS' => 0, 'BEDA_TIPIS' => 0, 'TIDAK_COCOK' => 0, 'NA' => 0],
            'TIDAK_COCOK' => ['PERSIS' => 0, 'BEDA_TIPIS' => 0, 'TIDAK_COCOK' => 0, 'NA' => 0],
            'NA' => ['PERSIS' => 0, 'BEDA_TIPIS' => 0, 'TIDAK_COCOK' => 0, 'NA' => 0],
        ];

        $detail = [];
        $sesuaiPersis = 0;

        foreach ($kasus as $indeks => $kasusUji) {
            $deteksi = $this->jalankanKasus($kasusUji, (int) $indeks);
            $harapan = array_values(array_unique((array) ($kasusUji['kode_harus_muncul'] ?? [])));

            foreach ($kodeTaksonomi as $kode) {
                $diharapkan = in_array($kode, $harapan, true);
                $terdeteksi = in_array($kode, $deteksi, true);

                if ($diharapkan) {
                    $perKode[$kode]['dukungan']++;
                }

                match (true) {
                    $diharapkan && $terdeteksi => $perKode[$kode]['benar_positif']++,
                    ! $diharapkan && $terdeteksi => $perKode[$kode]['positif_palsu']++,
                    $diharapkan && ! $terdeteksi => $perKode[$kode]['negatif_palsu']++,
                    default => $perKode[$kode]['benar_negatif']++,
                };
            }

            $positifPalsu = array_values(array_diff($deteksi, $harapan));
            $negatifPalsu = array_values(array_diff($harapan, $deteksi));
            $sesuai = $positifPalsu === [] && $negatifPalsu === [];

            if ($sesuai) {
                $sesuaiPersis++;
            }

            $harapanNama = (string) ($kasusUji['kategori_nama_harapan'] ?? 'NA');
            $aktualNama = $this->kategoriNamaAktual($kasusUji, $deteksi);

            if (! isset($matriksNama[$harapanNama])) {
                $harapanNama = 'NA';
            }

            $matriksNama[$harapanNama][$aktualNama]++;

            $detail[] = [
                'id' => (string) ($kasusUji['id'] ?? ''),
                'deskripsi' => (string) ($kasusUji['deskripsi'] ?? ''),
                'harapan' => $harapan,
                'deteksi' => $deteksi,
                'positif_palsu' => $positifPalsu,
                'negatif_palsu' => $negatifPalsu,
                'sesuai' => $sesuai,
            ];
        }

        foreach ($perKode as $kode => $metrik) {
            $pembagiPresisi = $metrik['benar_positif'] + $metrik['positif_palsu'];
            $pembagiRecall = $metrik['benar_positif'] + $metrik['negatif_palsu'];

            $perKode[$kode]['presisi'] = $pembagiPresisi === 0
                ? null
                : (float) ($metrik['benar_positif'] / $pembagiPresisi);
            $perKode[$kode]['recall'] = $pembagiRecall === 0
                ? null
                : (float) ($metrik['benar_positif'] / $pembagiRecall);
        }

        return [
            'total' => count($kasus),
            'sesuai_persis' => $sesuaiPersis,
            'akurasi' => $kasus === [] ? 0.0 : (float) ($sesuaiPersis / count($kasus)),
            'per_kode' => $perKode,
            'matriks_nama' => $matriksNama,
            'detail' => $detail,
        ];
    }

    /**
     * Menghitung skor 39 pasangan nama dan menandai kasus yang meragukan.
     *
     * @return array<string, mixed>
     */
    public function kalibrasiNama(): array
    {
        $jalur = database_path('data/pasangan_nama_uji.json');

        if (! is_file($jalur)) {
            throw new RuntimeException('Berkas pasangan nama uji tidak ditemukan: '.$jalur);
        }

        $data = json_decode((string) file_get_contents($jalur), true);

        if (! is_array($data)) {
            throw new RuntimeException('Berkas pasangan nama uji tidak dapat dibaca: '.$jalur);
        }

        $pencocok = new LayananPencocokNama;
        $batasPersis = (float) config('verifikasi.nama_cocok_persis', 1.0);
        $batasBedaTipis = (float) config('verifikasi.nama_beda_tipis', 0.80);

        $baris = [];

        foreach ($data as $pasangan) {
            $hasil = $pencocok->cocokkan(
                (string) ($pasangan['nama_pertama'] ?? ''),
                (string) ($pasangan['nama_kedua'] ?? ''),
            );

            $harapan = (string) ($pasangan['kategori_harapan'] ?? '');
            $sesuai = $hasil->kategori === $harapan;
            $tepatAmbang = abs($hasil->skor - $batasBedaTipis) < 0.00005;
            $dekatAmbang = abs($hasil->skor - $batasBedaTipis) <= 0.05;
            $dekatPersis = $hasil->skor >= 0.95 && $hasil->skor < $batasPersis;

            $alasan = [];

            if ($tepatAmbang) {
                $alasan[] = 'skor tepat di ambang beda tipis';
            } elseif ($dekatAmbang) {
                $alasan[] = 'skor berjarak maksimal 0,05 dari ambang beda tipis';
            }

            if ($dekatPersis) {
                $alasan[] = 'skor mendekati ambang persis';
            }

            if (! $sesuai) {
                $alasan[] = 'hasil berbeda dari label';
            }

            $baris[] = [
                'nama' => (string) ($pasangan['nama'] ?? ''),
                'nama_pertama' => (string) ($pasangan['nama_pertama'] ?? ''),
                'nama_kedua' => (string) ($pasangan['nama_kedua'] ?? ''),
                'harapan' => $harapan,
                'kategori' => $hasil->kategori,
                'skor' => $hasil->skor,
                'sesuai' => $sesuai,
                'meragukan' => $alasan !== [],
                'alasan' => $alasan,
            ];
        }

        $meragukan = array_values(array_filter($baris, static fn (array $item): bool => $item['meragukan']));
        $tidakSesuai = array_values(array_filter($baris, static fn (array $item): bool => ! $item['sesuai']));

        return [
            'batas_persis' => $batasPersis,
            'batas_beda_tipis' => $batasBedaTipis,
            'jumlah' => count($baris),
            'jumlah_sesuai' => count($baris) - count($tidakSesuai),
            'baris' => $baris,
            'meragukan' => $meragukan,
            'tidak_sesuai' => $tidakSesuai,
            'rekomendasi' => $this->rekomendasiAmbang($tidakSesuai, $baris, $batasBedaTipis),
        ];
    }

    /**
     * @param  array<string, mixed>  $kasusUji
     * @return list<string>
     */
    private function jalankanKasus(array $kasusUji, int $indeks): array
    {
        DB::beginTransaction();

        try {
            $pemohon = Pengguna::factory()->pemohon()->create([
                'nik' => '9999'.str_pad((string) ($indeks + 1), 12, '0', STR_PAD_LEFT),
            ]);

            $pengajuan = (array) ($kasusUji['pengajuan'] ?? []);

            $laporan = LaporanKematian::create([
                'id_pengguna' => $pemohon->id_pengguna,
                'no_pengajuan' => 'UJI-'.str_pad((string) ($indeks + 1), 4, '0', STR_PAD_LEFT),
                'nama_pelapor' => 'Pemohon Uji',
                'hubungan_pelapor' => 'Anak Kandung',
                'nama_almarhum' => $pengajuan['nama_almarhum'] ?? '',
                'nik_almarhum' => $pengajuan['nik_almarhum'] ?? '',
                'tanggal_kematian' => $pengajuan['tanggal_kematian'] ?? null,
                'jenis_kelamin' => $pengajuan['jenis_kelamin'] ?? null,
                'jenis_kasus' => $pengajuan['jenis_kasus'] ?? LaporanKematian::JENIS_KASUS_NORMAL,
                'tempat_kematian' => $pengajuan['tempat_kematian'] ?? null,
                'sebab_kematian' => $pengajuan['sebab_kematian'] ?? null,
                'status_pengajuan' => $pengajuan['status_pengajuan'] ?? LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                'tanggal_pengajuan' => $pengajuan['tanggal_pengajuan'] ?? null,
            ]);

            foreach ((array) ($kasusUji['berkas'] ?? []) as $urutan => $jenis) {
                BerkasPendukung::create([
                    'id_laporan' => $laporan->id_laporan,
                    'jenis_dokumen' => (string) $jenis,
                    'nama_file' => 'uji-'.$indeks.'-'.$urutan.'.pdf',
                    'path_file' => 'uji/'.$indeks.'/'.$urutan.'.pdf',
                    'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
                    'tanggal_unggah' => now(),
                ]);
            }

            if ((bool) ($kasusUji['pengajuan_ganda'] ?? false)) {
                LaporanKematian::create([
                    'id_pengguna' => $pemohon->id_pengguna,
                    'no_pengajuan' => 'UJI-G-'.str_pad((string) ($indeks + 1), 4, '0', STR_PAD_LEFT),
                    'nama_pelapor' => 'Pemohon Uji',
                    'hubungan_pelapor' => 'Anak Kandung',
                    'nama_almarhum' => $pengajuan['nama_almarhum'] ?? '',
                    'nik_almarhum' => $pengajuan['nik_almarhum'] ?? '',
                    'tanggal_kematian' => $pengajuan['tanggal_kematian'] ?? null,
                    'jenis_kasus' => LaporanKematian::JENIS_KASUS_NORMAL,
                    'status_pengajuan' => LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                    'tanggal_pengajuan' => now(),
                ]);
            }

            $analisis = $kasusUji['analisis_ai'] ?? null;

            if (is_array($analisis)) {
                AnalisisAi::create([
                    'id_laporan' => $laporan->id_laporan,
                    'id_berkas' => null,
                    'jenis' => (string) ($analisis['jenis'] ?? AnalisisAi::JENIS_EKSTRAKSI),
                    'hasil' => (array) ($analisis['hasil'] ?? ['kolom' => $analisis['kolom'] ?? []]),
                    'skor' => null,
                    'model' => 'kasus-uji-6.8',
                    'status' => (string) ($analisis['status'] ?? AnalisisAi::STATUS_BERHASIL),
                    'hash_berkas' => null,
                    'dibuat_pada' => now(),
                ]);
            }

            $siak = $kasusUji['siak'] ?? null;
            $siakTersedia = (bool) ($kasusUji['siak_tersedia'] ?? true);

            $pencariSiak = function (string $nik) use ($siak, $siakTersedia) {
                if (! $siakTersedia) {
                    throw new RuntimeException('SIAK tidak tersedia pada kasus uji.');
                }

                return $siak;
            };

            $mesin = new MesinRekonsiliasi(
                new LayananValidasiNik,
                new LayananAturanBerkas,
                new LayananPencocokNama,
                $pencariSiak,
            );

            $hasil = $mesin->analisis($laporan, false);

            return array_values(array_unique(array_map(
                static fn ($anomali): string => (string) $anomali->kode,
                $hasil->anomali,
            )));
        } finally {
            DB::rollBack();
        }
    }

    /**
     * @param  array<string, mixed>  $kasusUji
     * @param  list<string>  $deteksi
     */
    private function kategoriNamaAktual(array $kasusUji, array $deteksi): string
    {
        if (in_array(MesinRekonsiliasi::KODE_NAMA_TIDAK_COCOK, $deteksi, true)) {
            return 'TIDAK_COCOK';
        }

        if (in_array(MesinRekonsiliasi::KODE_NAMA_BEDA_TIPIS, $deteksi, true)) {
            return 'BEDA_TIPIS';
        }

        $siak = $kasusUji['siak'] ?? null;

        if ((bool) ($kasusUji['siak_tersedia'] ?? true)
            && is_array($siak)
            && trim((string) ($siak['nama_lengkap'] ?? '')) !== '') {
            return 'PERSIS';
        }

        return 'NA';
    }

    /**
     * @param  list<array<string, mixed>>  $tidakSesuai
     * @param  list<array<string, mixed>>  $semua
     */
    private function rekomendasiAmbang(array $tidakSesuai, array $semua, float $batasBedaTipis): array
    {
        $positifTerendah = null;

        foreach ($semua as $baris) {
            if ($baris['harapan'] === 'BEDA_TIPIS' || $baris['harapan'] === 'PERSIS') {
                $positifTerendah = $positifTerendah === null
                    ? $baris['skor']
                    : min($positifTerendah, $baris['skor']);
            }
        }

        return [
            'ubah' => false,
            'batas_dipertahankan' => $batasBedaTipis,
            'alasan' => $tidakSesuai !== []
                ? 'Ambang tidak diubah pada fase ini; hasil berbeda dari label perlu ditinjau manual lebih dahulu.'
                : 'Seluruh pasangan berlabel sudah sesuai dengan ambang saat ini dan tidak ada label negatif '
                    .'yang berjarak dekat dengan ambang. Mengubah ambang tanpa data lapangan berisiko '
                    .'menggeser kategori tanpa bukti.',
            'skor_positif_terendah' => $positifTerendah,
        ];
    }

    /**
     * Pastikan referensi wilayah tersedia agar aturan alamat dapat diuji.
     */
    private function pastikanReferensiWilayah(): void
    {
        if (ReferensiWilayah::query()->exists()) {
            return;
        }

        $this->call('db:seed', [
            '--class' => ReferensiWilayahSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $kasus
     * @param  array<string, mixed>  $evaluasi
     * @param  array<string, mixed>  $kalibrasi
     */
    public function susunMarkdown(array $kasus, array $evaluasi, array $kalibrasi): string
    {
        $tanggal = now()->translatedFormat('j F Y H:i').' WIB';
        $jumlahBersih = count(array_filter($kasus, static fn (array $item): bool => ($item['kategori'] ?? '') === 'bersih'));
        $jumlahAmbigu = count(array_filter($kasus, static fn (array $item): bool => ($item['kategori'] ?? '') === 'ambigu'));

        $teks = "# Evaluasi Aturan Verifikasi\n\n";
        $teks .= 'Dokumen ini dihasilkan oleh `php artisan aturan:evaluasi` pada '.$tanggal.'.'."\n\n";
        $teks .= 'Label berasal dari rancangan kasus uji, sehingga evaluasi ini menguji kebenaran implementasi aturan, '
            ."BUKAN akurasi terhadap data kependudukan nyata.\n\n";

        $teks .= "## Ringkasan\n\n";
        $teks .= '- Total kasus: '.$evaluasi['total']."\n";
        $teks .= '- Kasus bersih: '.$jumlahBersih."\n";
        $teks .= '- Kasus ambigu: '.$jumlahAmbigu."\n";
        $teks .= '- Kasus anomali: '.($evaluasi['total'] - $jumlahBersih - $jumlahAmbigu)."\n";
        $teks .= '- Kecocokan himpunan kode persis: '.$evaluasi['sesuai_persis'].' / '.$evaluasi['total']
            .' ('.number_format($evaluasi['akurasi'] * 100, 2, ',', '.')."%).\n\n";

        $teks .= "## Metrik per Kode Anomali\n\n";
        $teks .= "| Kode | Dukungan | Benar Positif | Positif Palsu | Negatif Palsu | Presisi | Recall |\n";
        $teks .= "|---|---:|---:|---:|---:|---:|---:|\n";

        foreach ($evaluasi['per_kode'] as $kode => $metrik) {
            $teks .= '| `'.$kode.'` | '.$metrik['dukungan']
                .' | '.$metrik['benar_positif']
                .' | '.$metrik['positif_palsu']
                .' | '.$metrik['negatif_palsu']
                .' | '.$this->formatRasio($metrik['presisi'])
                .' | '.$this->formatRasio($metrik['recall'])." |\n";
        }

        $teks .= "\n";

        $teks .= "## Matriks Kebingungan Kategori Nama\n\n";
        $teks .= "| Harapan \\ Aktual | PERSIS | BEDA_TIPIS | TIDAK_COCOK | NA |\n";
        $teks .= "|---|---:|---:|---:|---:|\n";

        foreach ($evaluasi['matriks_nama'] as $harapan => $baris) {
            $teks .= '| '.$harapan.' | '.$baris['PERSIS'].' | '.$baris['BEDA_TIPIS']
                .' | '.$baris['TIDAK_COCOK'].' | '.$baris['NA']." |\n";
        }

        $teks .= "\n";

        $teks .= "## Kalibrasi Ambang Nama (39 Pasangan)\n\n";
        $teks .= 'Ambang saat ini: `nama_cocok_persis='.$kalibrasi['batas_persis']
            .'`, `nama_beda_tipis='.$kalibrasi['batas_beda_tipis']."`.\n\n";
        $teks .= 'Hasil: '.$kalibrasi['jumlah_sesuai'].' / '.$kalibrasi['jumlah']." pasangan sesuai label.\n\n";

        $teks .= "| Pasangan | Skor | Harapan | Aktual | Status |\n";
        $teks .= "|---|---:|---|---|---|\n";

        foreach ($kalibrasi['baris'] as $baris) {
            $status = $baris['sesuai'] ? 'Sesuai' : 'Tidak sesuai';

            if ($baris['meragukan']) {
                $status .= ' (meragukan)';
            }

            $teks .= '| '.$baris['nama_pertama'].' vs '.$baris['nama_kedua'].' | '
                .number_format($baris['skor'], 4, ',', '.').' | '.$baris['harapan'].' | '
                .$baris['kategori'].' | '.$status." |\n";
        }

        $teks .= "\n### Kasus Meragukan\n\n";

        if ($kalibrasi['meragukan'] === []) {
            $teks .= "Tidak ada kasus yang ditandai meragukan.\n\n";
        } else {
            foreach ($kalibrasi['meragukan'] as $baris) {
                $teks .= '- `'.$baris['nama_pertama'].'` vs `'.$baris['nama_kedua'].'`: skor '
                    .number_format($baris['skor'], 4, ',', '.').', kategori '.$baris['kategori']
                    .' ('.implode('; ', $baris['alasan']).").\n";
            }

            $teks .= "\n";
        }

        $teks .= "### Keputusan Ambang\n\n";
        $teks .= 'Ambang **tidak diubah** (tetap `'.$kalibrasi['rekomendasi']['batas_dipertahankan']
            .'`). Alasan: '.$kalibrasi['rekomendasi']['alasan']."\n\n";

        if ($kalibrasi['rekomendasi']['skor_positif_terendah'] !== null) {
            $teks .= 'Skor positif terendah pada dataset: `'
                .number_format($kalibrasi['rekomendasi']['skor_positif_terendah'], 4, ',', '.')
                .'`. Pasangan dengan skor tepat di ambang atau mendekati ambang perlu dikonfirmasi ke '
                ."Dukcapil sebelum ambang dinaikkan.\n\n";
        }

        $teks .= "## Sebaran Alamat Kasus Uji\n\n";
        $teks .= "| Kecamatan | Jumlah Kasus |\n|---|---:|\n";

        $sebaran = [];

        foreach ($kasus as $item) {
            $kecamatan = (string) ($item['kecamatan_alamat'] ?? 'Tidak diketahui');
            $sebaran[$kecamatan] = ($sebaran[$kecamatan] ?? 0) + 1;
        }

        arsort($sebaran);

        foreach ($sebaran as $kecamatan => $jumlah) {
            $teks .= '| '.$kecamatan.' | '.$jumlah." |\n";
        }

        $teks .= "\n## Kasus yang Tidak Sesuai Label\n\n";

        $tidakSesuai = array_values(array_filter(
            $evaluasi['detail'],
            static fn (array $item): bool => ! $item['sesuai'],
        ));

        if ($tidakSesuai === []) {
            $teks .= "Tidak ada kasus yang berbeda dari label pada dataset ini.\n\n";
        } else {
            foreach ($tidakSesuai as $item) {
                $teks .= '- `'.$item['id'].'`: positif palsu ['
                    .implode(', ', $item['positif_palsu']).'], negatif palsu ['
                    .implode(', ', $item['negatif_palsu'])."].\n";
            }

            $teks .= "\n";
        }

        $teks .= "## Keterbatasan dan Catatan Jujur\n\n";
        $teks .= '- Label dibuat dari rancangan kasus uji, bukan dari keputusan petugas Dukcapil. '
            .'Karena itu angka presisi dan recall mengukur kesesuaian implementasi dengan rancangan aturan, '
            ."bukan akurasi di lapangan.\n";
        $teks .= '- Sebagian kasus memakai data SIAK fiktif yang ditempelkan langsung; pengujian tidak '
            ."memanggil API SIAK atau AI nyata.\n";
        $teks .= "- Evaluasi berjalan dalam transaksi yang di-rollback sehingga tidak mengubah data aplikasi.\n";
        $teks .= '- Ambang nama belum diuji pada variasi ejaan, gelar, dan nama tunggal yang luas. '
            ."Hasil 39 pasangan hanya menjadi batas awal, bukan bukti statistik.\n";
        $teks .= '- Kasus dengan layanan SIAK/AI gagal hanya menguji jalur fallback manual, bukan perilaku '
            ."penyedia eksternal yang sebenarnya.\n\n";

        $teks .= "## Hal yang Perlu Dikonfirmasi ke Dukcapil\n\n";
        $teks .= "- Daftar berkas wajib dan apakah KTP saksi diwajibkan di Kota Yogyakarta.\n";
        $teks .= "- Bentuk resmi SPTJM Kebenaran Data Kematian dan apakah dipakai untuk keterlambatan lebih dari 30 hari.\n";
        $teks .= "- Perlakuan nama tunggal, gelar adat, inisial, dan ejaan lama pada dokumen kependudukan.\n";
        $teks .= "- Kebijakan penanganan NIK ganda, identitas fiktif, dan sengketa data sebelum penolakan permanen.\n";
        $teks .= "- Apakah surat pengantar RT/RW atau surat kelurahan menjadi syarat lokal.\n";

        return $teks;
    }

    private function formatRasio(?float $nilai): string
    {
        return $nilai === null ? '—' : number_format($nilai, 4, ',', '.');
    }
}
