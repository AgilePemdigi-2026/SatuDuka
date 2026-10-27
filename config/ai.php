<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Layanan analisis dokumen berbantuan AI
    |--------------------------------------------------------------------------
    |
    | Driver `mock` dipakai secara bawaan agar pengembangan dan pengujian tidak
    | memanggil penyedia nyata. Kunci penyedia hanya dibaca dari environment.
    |
    */

    'driver' => env('AI_DRIVER', 'mock'),

    'timeout' => (int) env('AI_TIMEOUT', 20),

    'cache_ttl' => (int) env('AI_CACHE_TTL', 3600),

    'rate_limit' => [
        'max' => (int) env('AI_RATE_LIMIT', 10),
        'window' => (int) env('AI_RATE_WINDOW', 60),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
    ],

];
