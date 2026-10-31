<?php

namespace App\Providers;

use App\Models\AnalisisAi;
use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Services\Ai\AIService;
use App\Services\Ai\MockAIService;
use App\Services\Ai\OpenAiService;
use App\Services\Dokumen\PembuatPdf;
use App\Services\Dokumen\PembuatPdfDompdf;
use App\Services\Tte\PabrikTte;
use App\Services\Tte\TTEService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as Tampilan;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AIService::class, function (): AIService {
            if (config('ai.driver') === 'openai') {
                return new OpenAiService(
                    apiKey: (string) config('ai.openai.api_key', ''),
                    baseUrl: (string) config('ai.openai.base_url', 'https://api.openai.com/v1'),
                    model: (string) config('ai.openai.model', 'gpt-4.1-mini'),
                    timeout: (int) config('ai.timeout', 20),
                );
            }

            return new MockAIService;
        });

        $this->app->bind(TTEService::class, function ($app): TTEService {
            return PabrikTte::buat(
                driver: (string) config('satuduka.tte_driver', PabrikTte::DRIVER_MOCK),
                lingkungan: (string) $app->environment(),
                pinDummy: config('satuduka.tte_pin_dummy') === null
                    ? null
                    : (string) config('satuduka.tte_pin_dummy'),
            );
        });

        $this->app->bind(PembuatPdf::class, PembuatPdfDompdf::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->daftarkanPembatasLaju();

        View::composer('verifikasi.show', function (Tampilan $view): void {
            $laporan = $view->getData()['laporan'] ?? null;

            if (! $laporan instanceof LaporanKematian) {
                return;
            }

            $laporan->loadMissing(['berkasPendukung', 'analisisAi']);

            $analisis = session('analisis_ai');

            if (! is_array($analisis)) {
                $perbandingan = $laporan->analisisAi
                    ->where('jenis', AnalisisAi::JENIS_PERBANDINGAN)
                    ->sortByDesc('id_analisis')
                    ->first();

                if ($perbandingan !== null) {
                    $analisis = [
                        'berhasil' => $perbandingan->status === AnalisisAi::STATUS_BERHASIL,
                        'perbandingan' => $perbandingan->hasil,
                        'model' => $perbandingan->model,
                        'pesan' => null,
                    ];
                }
            }

            $ringkasan = $laporan->analisisAi
                ->where('jenis', AnalisisAi::JENIS_RINGKASAN)
                ->sortByDesc('id_analisis')
                ->first();

            $view->with([
                'analisis' => $analisis,
                'ringkasan' => $ringkasan?->hasil,
                'ringkasanBerhasil' => $ringkasan?->status === AnalisisAi::STATUS_BERHASIL,
                'suratKeterangan' => $laporan->berkasPendukung->firstWhere(
                    'jenis_dokumen',
                    BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
                ),
            ]);
        });
    }

    /**
     * Daftarkan pembatas laju untuk rute sensitif.
     *
     * Batas dibaca dari `config('satuduka.rate_limit')` agar dapat diubah lewat
     * env tanpa menyentuh kode. Mode longgar (demo) hanya mengalikan batas,
     * bukan menghapusnya.
     */
    private function daftarkanPembatasLaju(): void
    {
        $longgar = (bool) config('satuduka.rate_limit.longgar', false);
        $pengali = $longgar ? max(1, (int) config('satuduka.rate_limit.pengali_longgar', 20)) : 1;

        $batas = static fn (string $nama): int => max(1, (int) config('satuduka.rate_limit.'.$nama, 5) * $pengali);

        $kunci = static fn (Request $request, string $awalan): string => $awalan.'|'.$request->ip();

        RateLimiter::for('masuk', function (Request $request) use ($batas, $kunci) {
            $identitas = (string) $request->input('identitas', '');

            return Limit::perMinute($batas('masuk'))->by($kunci($request, 'masuk').'|'.$identitas);
        });

        RateLimiter::for('registrasi', fn (Request $request) => Limit::perMinute($batas('registrasi'))
            ->by($kunci($request, 'registrasi')));

        RateLimiter::for('lupa-kata-sandi', fn (Request $request) => Limit::perMinute($batas('lupa_kata_sandi'))
            ->by($kunci($request, 'lupa-kata-sandi')));

        RateLimiter::for('atur-ulang-kata-sandi', fn (Request $request) => Limit::perMinute($batas('atur_ulang_kata_sandi'))
            ->by($kunci($request, 'atur-ulang-kata-sandi')));

        RateLimiter::for('unggah-berkas', fn (Request $request) => Limit::perMinute($batas('unggah_berkas'))
            ->by($kunci($request, 'unggah-berkas').'|'.($request->user()?->id_pengguna ?? 'tamu')));

        RateLimiter::for('analisis-ai', fn (Request $request) => Limit::perMinute($batas('analisis_ai'))
            ->by($kunci($request, 'analisis-ai').'|'.($request->user()?->id_pengguna ?? 'tamu')));

        RateLimiter::for('tandatangani', fn (Request $request) => Limit::perMinute($batas('tandatangani'))
            ->by($kunci($request, 'tandatangani').'|'.($request->user()?->id_pengguna ?? 'tamu')));

        RateLimiter::for('terbitkan', fn (Request $request) => Limit::perMinute($batas('terbitkan'))
            ->by($kunci($request, 'terbitkan').'|'.($request->user()?->id_pengguna ?? 'tamu')));
    }
}
