<?php

use App\Http\Middleware\KepalaKeamanan;
use App\Http\Middleware\PastikanPeran;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Kenali protokol HTTPS yang diteruskan reverse proxy Coolify.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_PROTO
);
        // Alias middleware pembatas peran: Route::middleware('peran:pemohon').
        $middleware->alias([
            'peran' => PastikanPeran::class,
        ]);

        // Header keamanan dasar pada seluruh respons web.
        $middleware->web(append: [
            KepalaKeamanan::class,
        ]);

        // Tamu yang membuka halaman terproteksi diarahkan ke halaman masuk;
        // pengguna yang sudah masuk tidak melihat halaman masuk/registrasi lagi,
        // tetapi diberi tahu akun mana yang sedang aktif.
        $middleware->redirectGuestsTo(fn () => route('masuk'));
        $middleware->redirectUsersTo(function (Request $request) {
            $pengguna = $request->user();

            if ($pengguna !== null) {
                $request->session()->flash(
                    'info',
                    'Anda sudah masuk sebagai '.$pengguna->nama_lengkap.' ('.$pengguna->labelPeran().'). '
                    .'Keluar terlebih dahulu untuk berganti akun.',
                );
            }

            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Jangan simpan kata sandi ke old input saat validasi gagal. Nama field
        // aplikasi berbeda dari bawaan Laravel, jadi keempatnya didaftarkan
        // tanpa menghapus daftar bawaan (current_password, password, dsb.).
        $exceptions->dontFlash([
            'kata_sandi',
            'kata_sandi_lama',
            'kata_sandi_baru',
            'konfirmasi_kata_sandi',
            'pin',
            'tanda_tangan',
        ]);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Galat tak terduga selalu memakai halaman 500 statis yang tidak
        // memuat pesan pengecualian, bahkan ketika APP_DEBUG=true. Detail
        // tetap tercatat di log (sudah dimasker NIK).
        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($exception instanceof HttpException
                || $exception instanceof HttpResponseException
                || $exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException
                || $exception instanceof ModelNotFoundException
                || $exception instanceof Responsable) {
                return null;
            }

            return response()->view('errors.500', [], 500);
        });

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
