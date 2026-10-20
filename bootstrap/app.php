<?php

use App\Http\Middleware\PastikanPeran;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
    })->create();
