<?php

namespace App\Services\Ai;

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\LayananAudit;
use App\Support\NikAnalisis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
    /**
     * Pesan bagi pengguna saat penyedia AI sibuk (bukan gagal permanen).
     */
    public const PESAN_TERTUNDA = 'Layanan AI sedang sibuk (batas permintaan). '
        .'Analisis belum ditandai gagal; coba lagi beberapa saat lagi.';

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
            // NIK pada hasil ekstraksi langsung disamarkan sebelum masuk cache:
            // cache TIDAK boleh menyimpan NIK utuh, sama seperti basis data.
            $ekstraksi = Cache::remember(
                'analisis-ai:'.$hash,
                max(60, (int) config('ai.cache_ttl', 3600)),
                function () use ($berkas): array {
                    $mentah = $this->denganCobaUlang(
                        fn () => $this->ai->ekstrakSuratKematian($berkas),
                    );

                    return is_array($mentah) ? NikAnalisis::samarkanEkstraksi($mentah) : $mentah;
                },
            );
        } catch (AiRateLimit $e) {
            $this->catatBatasLaju($e, $model, $pemicu);

            return $this->tertunda($berkas, $laporan, $model, $pengguna, $pemicu, $hash, $e);
        } catch (\Throwable $e) {
            $this->catatKegagalan($e, $model, $pemicu);

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
        } catch (\Throwable $e) {
            $this->catatKegagalan($e, $model, $pemicu);

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
     * Membuat ringkasan ketidaksesuaian dengan bantuan AI.
     *
     * Bila AI gagal, ringkasan tidak tersedia dan pengguna dapat melanjutkan
     * pemeriksaan secara manual.
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
            $hasil = $this->denganCobaUlang(
                fn () => $this->ai->ringkasKetidaksesuaian($perbandingan),
            );
        } catch (AiRateLimit $e) {
            $this->catatBatasLaju($e, $model, $pemicu);

            return $this->tertundaRingkasan($laporan, $model, $pengguna, $pemicu, $e);
        } catch (\Throwable $e) {
            $this->catatKegagalan($e, $model, $pemicu);

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
     * Jalankan panggilan AI dan coba ulang HANYA saat penyedia sibuk.
     *
     * Batas: `ai.retry.maks` percobaan ulang (maksimal 2), dengan jeda singkat
     * atau sesuai `Retry-After`. Seluruh waktu tunggu dibatasi
     * `ai.retry.anggaran_detik` yang dipangkas agar total eksekusi tetap di
     * bawah AI_TIMEOUT dan batas eksekusi PHP (180 detik). Bila penyedia
     * meminta waktu tunggu melebihi anggaran, percobaan ulang dilewati dan
     * pemanggil menandai hasil sebagai tertunda.
     */
    private function denganCobaUlang(callable $panggil): mixed
    {
        $maksCobaUlang = max(0, min(2, (int) config('ai.retry.maks', 2)));
        $jedaDasar = max(0, (int) config('ai.retry.jeda_ms', 800));
        $anggaran = $this->anggaranCobaUlang();
        $mulai = microtime(true);
        $percobaan = 0;

        while (true) {
            try {
                return $panggil();
            } catch (AiRateLimit $e) {
                $percobaan++;

                if ($percobaan > $maksCobaUlang) {
                    throw $e;
                }

                $sisa = $anggaran - (microtime(true) - $mulai);

                if ($sisa <= 0) {
                    throw $e;
                }

                // Hormati Retry-After: bila penyedia meminta waktu lebih lama
                // daripada sisa anggaran, jangan menunggu sia-sia.
                if ($e->retryAfter !== null && $e->retryAfter > $sisa) {
                    throw $e;
                }

                $jeda = $e->retryAfter !== null
                    ? (float) $e->retryAfter
                    : ($jedaDasar * $percobaan) / 1000;

                $jeda = min($jeda, $sisa);

                if ($jeda > 0) {
                    usleep((int) round($jeda * 1_000_000));
                }
            }
        }
    }

    /**
     * Anggaran total waktu tunggu percobaan ulang (detik).
     */
    private function anggaranCobaUlang(): float
    {
        $timeout = max(1, (int) config('ai.timeout', 20));
        $batasPhp = max(1, (int) config('ai.retry.batas_eksekusi_php', 180));
        $anggaran = max(0.0, (float) config('ai.retry.anggaran_detik', 20));

        // Sisakan ruang untuk satu percobaan penuh: timeout + anggaran harus
        // tetap di bawah batas eksekusi PHP.
        return max(0.0, min($anggaran, $timeout, $batasPhp - $timeout - 5));
    }

    /**
     * Catat bahwa penyedia AI sibuk. Tidak ada isi dokumen/kunci yang dicatat.
     */
    private function catatBatasLaju(AiRateLimit $e, string $model, string $pemicu): void
    {
        Log::warning('Layanan AI sibuk (batas laju)', [
            'pengecualian' => $e::class,
            'status_http' => $e->statusHttp,
            'retry_after' => $e->retryAfter,
            'model' => $model,
            'pemicu' => $pemicu,
            'tertunda' => true,
        ]);
    }

    /**
     * Pesan bagi pengguna saat penyedia AI sibuk.
     */
    private function pesanTertunda(AiRateLimit $e): string
    {
        if ($e->retryAfter !== null && $e->retryAfter > 0) {
            return 'Layanan AI sedang sibuk (batas permintaan). Coba lagi dalam ±'
                .$e->retryAfter.' detik; analisis belum ditandai gagal.';
        }

        return self::PESAN_TERTUNDA;
    }

    /**
     * Tandai ekstraksi sebagai tertunda (bukan gagal) agar dapat dicoba ulang.
     *
     * @return array{
     *     berhasil: bool,
     *     tertunda: bool,
     *     pesan: string,
     *     model: string,
     *     ekstraksi: null,
     *     perbandingan: null
     * }
     */
    private function tertunda(
        BerkasPendukung $berkas,
        LaporanKematian $laporan,
        string $model,
        Pengguna $pengguna,
        string $pemicu,
        ?string $hash,
        AiRateLimit $e,
    ): array {
        $pesan = $this->pesanTertunda($e);

        $this->simpan(
            laporan: $laporan,
            berkas: $berkas,
            jenis: AnalisisAi::JENIS_EKSTRAKSI,
            hasil: [
                'pesan' => $pesan,
                'kode' => 'batas_laju',
                'status_http' => $e->statusHttp,
            ],
            skor: null,
            model: $model,
            status: AnalisisAi::STATUS_TERTUNDA,
            hash: $hash,
        );

        $this->audit($laporan, $model, $pemicu, $pengguna, 'Tertunda');

        return [
            'berhasil' => false,
            'tertunda' => true,
            'pesan' => $pesan,
            'model' => $model,
            'ekstraksi' => null,
            'perbandingan' => null,
        ];
    }

    /**
     * Tandai ringkasan sebagai tertunda (bukan gagal).
     *
     * @return array{berhasil: bool, tertunda: bool, pesan: string, ringkasan: null, saran: list<string>, model: string}
     */
    private function tertundaRingkasan(
        LaporanKematian $laporan,
        string $model,
        Pengguna $pengguna,
        string $pemicu,
        AiRateLimit $e,
    ): array {
        $pesan = $this->pesanTertunda($e);

        AnalisisAi::create([
            'id_laporan' => $laporan->id_laporan,
            'id_berkas' => null,
            'jenis' => AnalisisAi::JENIS_RINGKASAN,
            'hasil' => [
                'pesan' => $pesan,
                'kode' => 'batas_laju',
                'status_http' => $e->statusHttp,
            ],
            'skor' => null,
            'model' => $model,
            'status' => AnalisisAi::STATUS_TERTUNDA,
            'hash_berkas' => null,
            'dibuat_pada' => now(),
        ]);

        $this->audit(
            laporan: $laporan,
            model: $model,
            pemicu: $pemicu,
            pengguna: $pengguna,
            hasil: 'Tertunda',
            jenis: AnalisisAi::JENIS_RINGKASAN,
            aksi: 'membuat_ringkasan_ai',
        );

        return [
            'berhasil' => false,
            'tertunda' => true,
            'pesan' => $pesan,
            'ringkasan' => null,
            'saran' => [],
            'model' => $model,
        ];
    }

    /**
     * Catat penyebab kegagalan layanan AI tanpa membocorkan data rahasia.
     *
     * Log hanya memuat kelas pengecualian, status HTTP, penyebab ringkas,
     * nama model, dan pemicu. Pesan pengecualian mentah, NIK, isi/teks
     * dokumen, prompt, kunci API, dan header Authorization tidak pernah dicatat.
     */
    private function catatKegagalan(\Throwable $e, string $model, string $pemicu): void
    {
        $aiTidakTersedia = $e instanceof AiTidakTersedia ? $e : null;

        Log::warning('Layanan AI gagal', [
            'pengecualian' => $e::class,
            'status_http' => $aiTidakTersedia?->statusHttp,
            'penyebab' => $aiTidakTersedia?->penyebab,
            'model' => $model,
            'pemicu' => $pemicu,
        ]);
    }

    /**
     * Apakah kuota pemanggilan AI pengguna masih tersedia?
     *
     * Dipakai sebagai pra-cek oleh pemanggil agar permintaan yang tidak punya
     * berkas layak tidak memanggil AI saat kuota sudah habis. Pra-cek ini
     * tidak menambah hitungan kuota.
     */
    public function kuotaTersedia(Pengguna $pengguna): bool
    {
        return ! RateLimiter::tooManyAttempts($this->kunciLaju($pengguna), $this->batasLaju());
    }

    /**
     * Batasi laju pemanggilan per pengguna agar layanan tidak dibanjiri.
     */
    private function bolehMemanggil(Pengguna $pengguna): bool
    {
        $jendela = max(1, (int) config('ai.rate_limit.window', 60));
        $kunci = $this->kunciLaju($pengguna);

        if (RateLimiter::tooManyAttempts($kunci, $this->batasLaju())) {
            return false;
        }

        RateLimiter::hit($kunci, $jendela);

        return true;
    }

    /**
     * Kunci pembatas laju per pengguna.
     */
    private function kunciLaju(Pengguna $pengguna): string
    {
        return 'analisis-ai:'.$pengguna->id_pengguna;
    }

    /**
     * Jumlah pemanggilan AI yang diizinkan per jendela waktu.
     */
    private function batasLaju(): int
    {
        return max(1, (int) config('ai.rate_limit.max', 10));
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
            'hasil' => NikAnalisis::samarkan($jenis, $hasil),
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
