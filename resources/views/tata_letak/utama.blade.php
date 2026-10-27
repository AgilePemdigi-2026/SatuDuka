<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SATUDUKA') }} - @yield('judul', 'Beranda')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-bold text-slate-800 tracking-tight">
                {{ config('app.name', 'SATUDUKA') }}
            </a>
            <nav class="flex items-center space-x-4">
                @yield('navigasi')
            </nav>
        </div>
    </header>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (session('sukses') || session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4 border border-green-200 text-green-800 text-sm">
                {{ session('sukses') ?? session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div class="mb-4 rounded-md bg-blue-50 p-4 border border-blue-200 text-blue-800 text-sm">
                {{ session('info') }}
            </div>
        @endif

        @if (session('galat') || session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200 text-red-800 text-sm">
                {{ session('galat') ?? session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 p-4 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('konten')
        {{ $slot ?? '' }}
    </main>

    <footer class="bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
        &copy; {{ date('Y') }} {{ config('app.name', 'SATUDUKA') }} - Sistem Administrasi Terpadu Urusan Kematian Yogyakarta
    </footer>
</body>
</html>