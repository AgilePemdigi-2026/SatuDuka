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

    // Batas waktu koneksi TCP/TLS terpisah: jaringan lambat ke penyedia
    // sering melebihi default 10 detik Guzzle padahal koneksi masih sah.
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 30),

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

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
    ],

    // OCR lokal untuk driver `tesseract` (dan teks gambar pada driver lain).
    'tesseract' => [
        'binary' => env('TESSERACT_BINARY', 'tesseract'),
        'bahasa' => env('TESSERACT_LANG', 'ind+eng'),
        'timeout' => (int) env('TESSERACT_TIMEOUT', 30),
    ],

];
