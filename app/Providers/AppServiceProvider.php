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
}
