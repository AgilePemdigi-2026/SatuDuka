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
    ): void {
        LayananAudit::catat(
            aksi: 'menganalisis_dokumen_ai',
            objek: 'laporan_kematian',
            idObjek: $laporan->id_laporan,
            detail: [
                'jenis' => AnalisisAi::JENIS_EKSTRAKSI,
                'model' => $model,
                'pemicu' => $pemicu,
            ],
            pengguna: $pengguna,
            layananEksternal: LogAudit::LAYANAN_AI,
            hasil: $hasil,
        );
    }
}
