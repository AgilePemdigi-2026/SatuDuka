<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pembatas akses berdasarkan peran pengguna (SRS 2.6).
 *
 * Dipakai dengan nama alias `peran`, contoh: `Route::middleware('peran:pemohon')`
 * atau `peran:verifikator,pejabat` untuk beberapa peran sekaligus.
 */
class PastikanPeran
{
    /**
     * Pesan ketika peran tidak diizinkan.
     */
    private const PESAN_AKSES_DITOLAK = 'Anda tidak memiliki hak akses ke halaman tersebut.';

    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $pengguna = $request->user();

        if ($pengguna === null || ! in_array($pengguna->peran, $peran, true)) {
            abort(403, self::PESAN_AKSES_DITOLAK);
        }

        return $next($request);
    }
}
