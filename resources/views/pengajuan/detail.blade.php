@extends('tata_letak.utama')

@section('judul', 'Detail Status Pengajuan')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Dashboard</a>
    <span class="text-sm text-gray-700">{{ $pengguna->nama_lengkap }}</span>
    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Keluar</button>
    </form>
@endsection

@section('konten')
    @php
        $warnaStatus = match ($laporan->status_pengajuan) {
            \App\Models\LaporanKematian::STATUS_SELESAI => 'bg-green-100 text-green-800',
            \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI => 'bg-red-100 text-red-800',
            default => 'bg-amber-100 text-amber-800',
        };
    @endphp

    <div class="space-y-4">
        <a href="{{ route('pengajuan.status') }}" class="inline-block text-sm text-slate-700 underline">
            &larr; Kembali ke Status Pengajuan
        </a>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Detail Pengajuan</h1>
                    <p class="mt-1 text-sm text-gray-500">Nomor pengajuan</p>
                    <p class="font-mono text-sm font-semibold text-slate-800">{{ $laporan->no_pengajuan ?? '—' }}</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $warnaStatus }}">
                    {{ $laporan->status_pengajuan }}
                </span>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500">Nama Almarhum/Almarhumah</dt>
                    <dd class="font-medium text-slate-800">{{ $laporan->nama_almarhum ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Tanggal Pengajuan</dt>
                    <dd class="font-medium text-slate-800">
                        {{ $laporan->tanggal_pengajuan?->translatedFormat('j F Y, H:i') ?? '—' }}
                    </dd>
                </div>
            </dl>
        </div>

        @if ($laporan->catatan_revisi)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Catatan Revisi</p>
                <p class="mt-1">{{ $laporan->catatan_revisi }}</p>
            </div>
        @endif

        @if ($laporan->dokumenResmi?->alasan_pengembalian)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Alasan Pengembalian Dokumen</p>
                <p class="mt-1">{{ $laporan->dokumenResmi->alasan_pengembalian }}</p>
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
            <h2 class="text-lg font-semibold text-slate-800">Lini Masa Pengajuan</h2>

            <ol class="relative mt-5 ml-3 border-l border-gray-200">
                @foreach ($liniMasa as $indeks => $tahap)
                    <li class="mb-6 ml-6 last:mb-0">
                        <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold
                            {{ $tahap['selesai'] ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                            @if ($tahap['selesai'])
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l3 3 7-7" />
                                </svg>
                            @else
                                {{ $indeks + 1 }}
                            @endif
                        </span>

                        <p class="text-sm font-medium {{ $tahap['selesai'] ? 'text-slate-800' : 'text-gray-400' }}">
                            {{ $tahap['judul'] }}
                        </p>

                        @if ($tahap['tanggal'] !== null)
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ $tahap['tanggal']->translatedFormat('j F Y, H:i') }} WIB
                            </p>
                        @elseif (! $tahap['selesai'])
                            <p class="mt-0.5 text-xs text-gray-400">Menunggu</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">Dokumen akta dapat diunduh setelah pengajuan berstatus Selesai.</p>

            @if ($laporan->selesai())
                <a href="{{ route('pengajuan.unduh', $laporan) }}"
                   class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                    Unduh Dokumen
                </a>
            @else
                <button type="button" disabled
                        class="cursor-not-allowed rounded-md bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500">
                    Unduh Dokumen
                </button>
            @endif
        </div>
    </div>
@endsection
