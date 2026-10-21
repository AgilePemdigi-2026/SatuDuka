<?php

namespace App\Providers;

use App\Services\Ai\AIService;
use App\Services\Ai\MockAIService;
use App\Services\Ai\OpenAiService;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
