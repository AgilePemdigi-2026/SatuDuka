<?php

use App\Services\Ai\AIService;
use App\Services\Ai\DeepSeekService;
use App\Services\Ai\GeminiService;
use App\Services\Ai\MockAIService;
use App\Services\Ai\OpenAiService;
use App\Services\Ai\PabrikAi;
use App\Services\Ai\TesseractService;

it('menolak driver ai yang tidak dikenal', function () {
    PabrikAi::buat('deepsek', 'produksi');
})->throws(InvalidArgumentException::class, 'Driver AI tidak dikenal: deepsek.');

it('menolak driver typo tanpa diam-diam memakai mock', function () {
    $layanan = null;

    try {
        $layanan = PabrikAi::buat('open-ai', 'testing');
    } catch (InvalidArgumentException) {
        // Diharapkan: tidak ada fallback ke mock.
    }

    expect($layanan)->toBeNull();
});

it('menolak driver mock di luar lingkungan lokal dan pengujian', function (string $lingkungan) {
    PabrikAi::buat(PabrikAi::DRIVER_MOCK, $lingkungan);
})->with(['production', 'staging'])
    ->throws(LogicException::class, 'Driver AI mock tidak boleh dipakai pada lingkungan');

it('mengizinkan driver mock pada lingkungan testing dan local', function (string $lingkungan) {
    expect(PabrikAi::buat(PabrikAi::DRIVER_MOCK, $lingkungan))->toBeInstanceOf(MockAIService::class);
})->with(['testing', 'local']);

it('memilih implementasi sesuai driver sah', function (string $driver, string $kelas, string $model) {
    config([
        'ai.openai.api_key' => 'kunci-uji',
        'ai.openai.model' => $model,
        'ai.gemini.api_key' => 'kunci-uji',
        'ai.gemini.model' => $model,
        'ai.deepseek.api_key' => 'kunci-uji',
        'ai.deepseek.model' => $model,
    ]);

    $layanan = PabrikAi::buat($driver, 'production');

    expect($layanan)->toBeInstanceOf($kelas)
        ->and($layanan->namaModel())->toBe($model);
})->with([
    'openai' => [PabrikAi::DRIVER_OPENAI, OpenAiService::class, 'model-uji'],
    'gemini' => [PabrikAi::DRIVER_GEMINI, GeminiService::class, 'model-uji'],
    'deepseek' => [PabrikAi::DRIVER_DEEPSEEK, DeepSeekService::class, 'model-uji'],
    'tesseract' => [PabrikAi::DRIVER_TESSERACT, TesseractService::class, 'tesseract-ocr-v1'],
]);

it('mengikat layanan ai melalui pabrik sesuai konfigurasi driver', function () {
    config([
        'ai.driver' => PabrikAi::DRIVER_DEEPSEEK,
        'ai.deepseek.api_key' => 'kunci-uji',
        'ai.deepseek.model' => 'model-konfigurasi',
    ]);

    expect(app(AIService::class))->toBeInstanceOf(DeepSeekService::class)
        ->and(app(AIService::class)->namaModel())->toBe('model-konfigurasi');
});

it('memakai mock pada lingkungan pengujian bawaan', function () {
    config(['ai.driver' => PabrikAi::DRIVER_MOCK]);

    expect(app(AIService::class))->toBeInstanceOf(MockAIService::class);
});
