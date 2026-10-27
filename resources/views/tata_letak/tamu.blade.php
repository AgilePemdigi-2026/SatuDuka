<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SKD') }} - @yield('judul', 'Masuk')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-canvas text-ink-soft">
    <main class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:py-16">
        <div class="w-full max-w-[520px]">
            <div class="text-center">
                <a href="{{ url('/') }}"
                    class="text-[52px] font-extrabold leading-none tracking-tight text-ink">{{ config('app.name', 'SKD') }}</a>
                <p class="mt-2 text-xl font-medium text-ink-soft">Layanan Pencatatan &amp; Administrasi Kematian</p>
            </div>

            <x-ui.card class="mt-8">
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
            </x-ui.card>
        </div>
    </main>

    <footer class="py-4 text-center text-xs text-faint">
        &copy; {{ date('Y') }} {{ config('app.name', 'SKD') }}
    </footer>
</body>
</html>
