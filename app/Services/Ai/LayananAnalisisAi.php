<?php

namespace App\Services\Ai;

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\LayananAudit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/**
 * Menjalankan ekstraksi, perbandingan, pencatatan analisis, dan audit.
 *
 * Semua kegagalan layanan AI dikembalikan sebagai hasil tidak tersedia agar
 * pemohon/verifikator dapat melanjutkan secara manual.
 */
class LayananAnalisisAi
{
    public function __construct(
        private readonly AIService $ai,
        private readonly LayananPerbandinganData $perbandingan,
        private readonly LayananSkorKecurigaan $skorKecurigaan,
    ) {}

    /**
     * @return array{
     *     berhasil: bool,
     *     pesan: string|null,
     *     model: string,
     *     ekstraksi: array<string, mixed>|null,
     *     perbandingan: array<string, mixed>|null
     * }
     */
    public function periksa(BerkasPendukung $berkas, Pengguna $pengguna, string $pemicu): array
    {
        $model = $this->ai->namaModel();
        $berkas->loadMissing('laporanKematian');
        $laporan = $berkas->laporanKematian;

        if (! $laporan instanceof LaporanKematian) {
            return $this->gagal($berkas, null, $model, $pengguna, $pemicu, null);
        }

        if (! Storage::disk('local')->exists($berkas->path_file)) {
            return $this->gagal($berkas, $laporan, $model, $pengguna, $pemicu, null);
        }

        $isi = (string) Storage::disk('local')->get($berkas->path_file);
        $hash = hash('sha256', $isi);

        if (! $this->bolehMemanggil($pengguna)) {
            return $this->gagal($berkas, $laporan, $model, $pengguna, $pemicu, $hash);
        }

        try {
            $ekstraksi = Cache::remember(
                'analisis-ai:'.$hash,
                max(60, (int) config('ai.cache_ttl', 3600)),
                fn (): array => $this->ai->ekstrakSuratKematian($berkas),
            );
        } catch (\Throwable) {
            return $this->gagal($berkas, $laporan, $model, $pengguna, $pemicu, $hash);
        }

        if (! is_array($ekstraksi) || ($ekstraksi['berhasil'] ?? false) !== true) {
            return $this->gagal($berkas, $laporan, $model, $pengguna, $pemicu, $hash);
        }

        $this->simpan(
            laporan: $laporan,
            berkas: $berkas,
            jenis: AnalisisAi::JENIS_EKSTRAKSI,
            hasil: $ekstraksi,
            skor: (float) ($ekstraksi['skor'] ?? 0),
            model: $model,
            status: AnalisisAi::STATUS_BERHASIL,
            hash: $hash,
        );

        try {
            $banding = $this->perbandingan->bandingkan($laporan, $ekstraksi);
        } catch (\Throwable) {
            $this->simpan(
                laporan: $laporan,
                berkas: $berkas,
                jenis: AnalisisAi::JENIS_PERBANDINGAN,
                hasil: ['pesan' => 'Perbandingan tidak dapat diselesaikan.'],
                skor: null,
                model: $model,
                status: AnalisisAi::STATUS_GAGAL,
                hash: $hash,
            );

            $this->audit($laporan, $model, $pemicu, $pengguna, 'Gagal');

            return [
                'berhasil' => false,
                'pesan' => 'Analisis AI tidak tersedia, lanjutkan secara manual',
                'model' => $model,
                'ekstraksi' => $ekstraksi,
                'perbandingan' => null,
            ];
        }

        $this->simpan(
            laporan: $laporan,
            berkas: $berkas,
            jenis: AnalisisAi::JENIS_PERBANDINGAN,
            hasil: $banding,
            skor: (float) ($banding['skor'] ?? 0),
            model: $model,
            status: AnalisisAi::STATUS_BERHASIL,
            hash: $hash,
        );

        $this->audit($laporan, $model, $pemicu, $pengguna, 'Sukses');

        return [
            'berhasil' => true,
            'pesan' => $ekstraksi['pesan'] ?? null,
            'model' => $model,
            'ekstraksi' => $ekstraksi,
            'perbandingan' => $banding,
        ];
    }

    /**
     * Menghitung dan menyimpan skor kecurigaan berbasis aturan.
     *
     * Skor tidak memanggil layanan eksternal dan tidak mengubah status.
     *
     * @param  array<string, mixed>|null  $perbandingan
     * @return array{
     *     skor: int,
     *     alasan: list<array{kode: string, bobot: int, keterangan: string}>,
     *     kategori: string
     * }
     */
    public function skor(LaporanKematian $laporan, ?array $perbandingan = null): array
    {
        $hasil = $this->skorKecurigaan->hitung($laporan, $perbandingan);
        $this->simpanSkor($laporan, $hasil);

        return $hasil;
    }

    /**
     * Membuat ringkasan ketidaksesuaian dengan bantuan AI.
     *
     * Bila AI gagal, ringkasan tidak tersedia tetapi skor aturan tetap ada.
     *
     * @param  array<string, mixed>  $perbandingan
     * @return array{
     *     berhasil: bool,
     *     pesan: string|null,
     *     ringkasan: string|null,
     *     saran: list<string>,
     *     model: string
     * }
     */
    public function ringkas(
        LaporanKematian $laporan,
        array $perbandingan,
        Pengguna $pengguna,
        string $pemicu,
    ): array {
        $model = $this->ai->namaModel();

        if (! $this->bolehMemanggil($pengguna)) {
            return $this->gagalRingkasan($laporan, $model, $pengguna, $pemicu);
        }

        try {
            $hasil = $this->ai->ringkasKetidaksesuaian($perbandingan);
        } catch (\Throwable) {
            return $this->gagalRingkasan($laporan, $model, $pengguna, $pemicu);
        }

        if (! is_array($hasil) || ($hasil['berhasil'] ?? false) !== true) {
            return $this->gagalRingkasan($laporan, $model, $pengguna, $pemicu);
        }

        AnalisisAi::create([
            'id_laporan' => $laporan->id_laporan,
            'id_berkas' => null,
            'jenis' => AnalisisAi::JENIS_RINGKASAN,
            'hasil' => $hasil,
            'skor' => null,
            'skor_kecurigaan' => null,
            'model' => $model,
            'status' => AnalisisAi::STATUS_BERHASIL,
            'hash_berkas' => null,
            'dibuat_pada' => now(),
        ]);

        $this->audit(
            laporan: $laporan,
            model: $model,
            pemicu: $pemicu,
            pengguna: $pengguna,
            hasil: 'Sukses',
            jenis: AnalisisAi::JENIS_RINGKASAN,
            aksi: 'membuat_ringkasan_ai',
        );

        return [
            'berhasil' => true,
            'pesan' => null,
            'ringkasan' => $hasil['ringkasan'] ?? null,
            'saran' => $hasil['saran'] ?? [],
            'model' => $model,
        ];
    }

    /**
     * @param  array{skor: int, alasan: list<array<string, mixed>>, kategori: string}  $hasil
     */
    private function simpanSkor(LaporanKematian $laporan, array $hasil): void
    {
        $terakhir = AnalisisAi::query()
            ->where('id_laporan', $laporan->id_laporan)
            ->where('jenis', AnalisisAi::JENIS_SKOR)
            ->orderByDesc('id_analisis')
            ->first();

        if ($terakhir !== null
            && (int) $terakhir->skor_kecurigaan === (int) $hasil['skor']
            && $terakhir->hasil == $hasil) {
            return;
        }

        AnalisisAi::create([
            'id_laporan' => $laporan->id_laporan,
            'id_berkas' => null,
            'jenis' => AnalisisAi::JENIS_SKOR,
            'hasil' => $hasil,
            'skor' => null,
            'skor_kecurigaan' => (float) $hasil['skor'],
            'model' => 'aturan-rule-v1',
            'status' => AnalisisAi::STATUS_BERHASIL,
            'hash_berkas' => null,
            'dibuat_pada' => now(),
        ]);
    }

    /**
     * @return array{berhasil: bool, pesan: string, ringkasan: null, saran: list<string>, model: string}
     */
    private function gagalRingkasan(
        LaporanKematian $laporan,
        string $model,
        Pengguna $pengguna,
        string $pemicu,
    ): array {
        AnalisisAi::create([
            'id_laporan' => $laporan->id_laporan,
            'id_berkas' => null,
            'jenis' => AnalisisAi::JENIS_RINGKASAN,
            'hasil' => ['pesan' => 'Layanan AI tidak tersedia.'],
            'skor' => null,
            'skor_kecurigaan' => null,
            'model' => $model,
            'status' => AnalisisAi::STATUS_GAGAL,
            'hash_berkas' => null,
            'dibuat_pada' => now(),
        ]);

        $this->audit(
            laporan: $laporan,
            model: $model,
            pemicu: $pemicu,
            pengguna: $pengguna,
            hasil: 'Gagal',
            jenis: AnalisisAi::JENIS_RINGKASAN,
            aksi: 'membuat_ringkasan_ai',
        );

        return [
            'berhasil' => false,
            'pesan' => 'Analisis AI tidak tersedia, lanjutkan secara manual',
            'ringkasan' => null,
            'saran' => [],
            'model' => $model,
        ];
    }

    /**
     * Batasi laju pemanggilan per pengguna agar layanan tidak dibanjiri.
     */
    private function bolehMemanggil(Pengguna $pengguna): bool
    {
        $maks = max(1, (int) config('ai.rate_limit.max', 10));
        $jendela = max(1, (int) config('ai.rate_limit.window', 60));
        $kunci = 'analisis-ai:'.$pengguna->id_pengguna;

        if (RateLimiter::tooManyAttempts($kunci, $maks)) {
            return false;
        }

        RateLimiter::hit($kunci, $jendela);

        return true;
    }

    /**
     * @param  array<string, mixed>  $hasil
     */
    private function simpan(
        LaporanKematian $laporan,
        BerkasPendukung $berkas,
        string $jenis,
        array $hasil,
        ?float $skor,
        string $model,
        string $status,
        ?string $hash,
    ): void {
        AnalisisAi::create([
            'id_laporan' => $laporan->id_laporan,
            'id_berkas' => $berkas->id_berkas,
            'jenis' => $jenis,
            'hasil' => $hasil,
            'skor' => $skor,
            'model' => $model,
            'status' => $status,
            'hash_berkas' => $hash,
            'dibuat_pada' => now(),
        ]);
    }

    /**
     * @return array{berhasil: bool, pesan: string, model: string, ekstraksi: null, perbandingan: null}
     */
    private function gagal(
        BerkasPendukung $berkas,
        ?LaporanKematian $laporan,
        string $model,
        Pengguna $pengguna,
        string $pemicu,
        ?string $hash,
    ): array {
        if ($laporan instanceof LaporanKematian) {
            $this->simpan(
                laporan: $laporan,
                berkas: $berkas,
                jenis: AnalisisAi::JENIS_EKSTRAKSI,
                hasil: ['pesan' => 'Layanan AI tidak tersedia.'],
                skor: null,
                model: $model,
                status: AnalisisAi::STATUS_GAGAL,
                hash: $hash,
            );

            $this->audit($laporan, $model, $pemicu, $pengguna, 'Gagal');
        }

        return [
            'berhasil' => false,
            'pesan' => 'Analisis AI tidak tersedia, lanjutkan secara manual',
            'model' => $model,
            'ekstraksi' => null,
            'perbandingan' => null,
        ];
    }

    private function audit(
        LaporanKematian $laporan,
        string $model,
        string $pemicu,
        Pengguna $pengguna,
        string $hasil,
        string $jenis = AnalisisAi::JENIS_EKSTRAKSI,
        string $aksi = 'menganalisis_dokumen_ai',
    ): void {
        LayananAudit::catat(
            aksi: $aksi,
            objek: 'laporan_kematian',
            idObjek: $laporan->id_laporan,
            detail: [
                'jenis' => $jenis,
                'model' => $model,
                'pemicu' => $pemicu,
            ],
            pengguna: $pengguna,
            layananEksternal: LogAudit::LAYANAN_AI,
            hasil: $hasil,
        );
    }
}
