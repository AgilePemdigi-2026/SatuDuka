<?php

use App\Http\Middleware\PastikanPeran;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias middleware pembatas peran: Route::middleware('peran:pemohon').
        $middleware->alias([
            'peran' => PastikanPeran::class,
        ]);

        // Tamu yang membuka halaman terproteksi diarahkan ke halaman masuk;
        // pengguna yang sudah masuk tidak melihat halaman masuk/registrasi lagi.
        $middleware->redirectGuestsTo(fn () => route('masuk'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Sesi kedaluwarsa saat menyimpan formulir: kembali ke halaman masuk.
        // TokenMismatchException sudah diubah menjadi HttpException 419 oleh
        // penanganan bawaan Laravel, sehingga statusnya yang diperiksa.
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            if ($request->user() !== null) {
                Auth::logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->route('masuk')
                ->with('galat', 'Sesi Anda telah berakhir. Silakan login kembali.');
        });
    })->create();
