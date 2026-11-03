<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk seluruh respons HTML.
 *
 * `X-Frame-Options: SAMEORIGIN` dan `frame-ancestors 'self'` dipakai agar
 * pratinjau berkas di dalam iframe pada domain yang sama tetap berjalan,
 * sementara domain lain tetap diblokir.
 *
 * Content Security Policy sengaja dibatasi pada `frame-ancestors` saja; aturan
 * `script-src`/`style-src` tidak dipasang agar Vite dan skrip kanvas tanda
 * tangan tidak rusak.
 */
class KepalaKeamanan
{
    /**
     * Header yang dipasang pada setiap respons.
     *
     * @var array<string, string>
     */
    public const HEADER = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Content-Security-Policy' => "frame-ancestors 'self'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADER as $nama => $nilai) {
            if (! $response->headers->has($nama)) {
                $response->headers->set($nama, $nilai);
            }
        }

        return $response;
    }
}
