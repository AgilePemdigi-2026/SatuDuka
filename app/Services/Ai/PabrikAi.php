<?php

namespace App\Services\Ai;

use InvalidArgumentException;
use LogicException;

/**
 * Memilih implementasi AIService berdasarkan konfigurasi.
 *
 * Driver `mock` sengaja DITOLAK di luar lingkungan lokal/pengujian: hasil
 * tiruan tidak boleh tampil sebagai analisis AI sungguhan. Driver yang tidak
 * dikenal juga menghasilkan pengecualian yang jelas, bukan diam-diam jatuh
 * ke mock.
 */
final class PabrikAi
{
    public const DRIVER_MOCK = 'mock';

    public const DRIVER_TESSERACT = 'tesseract';

    public const DRIVER_OPENAI = 'openai';

    public const DRIVER_GEMINI = 'gemini';

    public const DRIVER_DEEPSEEK = 'deepseek';

    /**
     * Lingkungan yang boleh memakai driver mock.
     *
     * @var list<string>
     */
    public const LINGKUNGAN_MOCK = ['local', 'testing'];

    public static function buat(string $driver, string $lingkungan): AIService
    {
        return match ($driver) {
            self::DRIVER_MOCK => self::mock($lingkungan),
            self::DRIVER_TESSERACT => new TesseractService,
            self::DRIVER_OPENAI => new OpenAiService(
                apiKey: (string) config('ai.openai.api_key', ''),
                baseUrl: (string) config('ai.openai.base_url', 'https://api.openai.com/v1'),
                model: (string) config('ai.openai.model', 'gpt-4.1-mini'),
                timeout: (int) config('ai.timeout', 20),
            ),
            self::DRIVER_GEMINI => new GeminiService(
                apiKey: (string) config('ai.gemini.api_key', ''),
                baseUrl: (string) config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'),
                model: (string) config('ai.gemini.model', 'gemini-2.5-flash'),
                timeout: (int) config('ai.timeout', 20),
            ),
            self::DRIVER_DEEPSEEK => new DeepSeekService(
                apiKey: (string) config('ai.deepseek.api_key', ''),
                baseUrl: (string) config('ai.deepseek.base_url', 'https://api.deepseek.com'),
                model: (string) config('ai.deepseek.model', 'deepseek-chat'),
                timeout: (int) config('ai.timeout', 20),
            ),
            default => throw new InvalidArgumentException('Driver AI tidak dikenal: '.$driver.'.'),
        };
    }

    private static function mock(string $lingkungan): AIService
    {
        if (! in_array($lingkungan, self::LINGKUNGAN_MOCK, true)) {
            throw new LogicException(
                'Driver AI mock tidak boleh dipakai pada lingkungan '.$lingkungan
                .'. Gunakan penyedia AI sungguhan atau jalankan pada lingkungan lokal/pengujian.'
            );
        }

        return new MockAIService;
    }
}
