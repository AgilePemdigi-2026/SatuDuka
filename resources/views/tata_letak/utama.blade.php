<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SKD') }} - @yield('judul', 'Beranda')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <header class="border-b border-line bg-surface">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-2">
                @hasSection('kembali')
                    <div class="flex min-w-0 items-center gap-2 text-sm font-semibold text-ink-soft">
                        @yield('kembali')
                    </div>
                @else
                    <a href="{{ url('/') }}"
                        class="truncate text-xl font-extrabold tracking-tight text-ink">{{ config('app.name', 'SKD') }}</a>
                @endif
            </div>

            <nav class="flex items-center gap-3">
                @auth
                    @if (auth()->user()->adalahPemohon())
                        @php($jumlahBelumDibacaHeader = \App\Models\Notifikasi::query()
                            ->where('id_pengguna', auth()->id())
                            ->where('sudah_dibaca', false)
                            ->count())
                        <a href="{{ route('notifikasi.index') }}"
                           class="relative rounded-full border border-line px-3 py-1.5 text-sm font-semibold text-ink-soft hover:bg-canvas"
                           aria-label="Pusat Notifikasi">
                            Notifikasi
                            @if ($jumlahBelumDibacaHeader > 0)
                                <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-bold text-white"
                                      style="background: #b91c1c;">{{ $jumlahBelumDibacaHeader }}</span>
                            @endif
                        </a>
                    @endif
                @endauth
                @yield('navigasi')
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-grow px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        @if (session('sukses') || session('success'))
            <x-ui.alert variant="success" class="mb-4">
                {{ session('sukses') ?? session('success') }}
            </x-ui.alert>
        @endif

        @if (session('info'))
            <x-ui.alert variant="info" class="mb-4">
                {{ session('info') }}
            </x-ui.alert>
        @endif

        @if (session('galat') || session('error'))
            <x-ui.alert variant="danger" class="mb-4">
                {{ session('galat') ?? session('error') }}
            </x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="danger" class="mb-4">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @yield('konten')
        {{ $slot ?? '' }}
    </main>

    <footer class="border-t border-line py-4 text-center text-xs text-faint">
        &copy; {{ date('Y') }} {{ config('app.name', 'SKD') }}
    </footer>
</body>
</html>
