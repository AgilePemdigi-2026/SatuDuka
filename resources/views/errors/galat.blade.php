<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'SKD') }} - {{ $judul }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-canvas">
    <header class="border-b border-line bg-surface">
        <div class="mx-auto flex h-16 max-w-6xl items-center px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-xl font-extrabold tracking-tight text-ink">
                {{ config('app.name', 'SKD') }}
            </a>
        </div>
    </header>

    <main class="flex flex-grow items-center justify-center px-4 py-10">
        <div class="w-full max-w-lg rounded-2xl border border-line bg-surface p-8 text-center shadow-[0_1px_2px_rgb(15_23_42_/_0.04)]">
            <p class="text-6xl font-extrabold tracking-tight text-brand">{{ $kode }}</p>
            <h1 class="mt-3 text-xl font-bold text-ink">{{ $judul }}</h1>
            <p class="mt-2 text-sm text-muted">{{ $pesan }}</p>

            <div class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-brand px-5 text-sm font-semibold text-white hover:bg-brand-hover">
                        Kembali ke Dashboard
                    </a>
                @else
                    <a href="{{ route('masuk') }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-brand px-5 text-sm font-semibold text-white hover:bg-brand-hover">
                        Kembali ke Halaman Masuk
                    </a>
                @endauth

                <a href="{{ url('/') }}"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-line-strong px-5 text-sm font-semibold text-ink-soft hover:bg-canvas">
                    Halaman Awal
                </a>
            </div>

            <p class="mt-6 text-xs text-faint">
                Jika masalah berlanjut, hubungi petugas Dukcapil Kota Yogyakarta.
            </p>
        </div>
    </main>

    <footer class="border-t border-line py-4 text-center text-xs text-faint">
        &copy; {{ date('Y') }} {{ config('app.name', 'SKD') }}
    </footer>
</body>
</html>
