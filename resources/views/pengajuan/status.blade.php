@extends('tata_letak.utama')

@section('judul', 'Lacak Status Pengajuan')

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
        $warnaStatus = fn (string $status) => match ($status) {
            \App\Models\LaporanKematian::STATUS_SELESAI => 'bg-green-100 text-green-800',
            \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI => 'bg-red-100 text-red-800',
            default => 'bg-amber-100 text-amber-800',
        };
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
        <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Lacak Status Pengajuan</h1>
        <p class="mt-1 text-sm text-gray-500">
            Pantau perkembangan pengajuan laporan kematian dan unduh akta setelah diterbitkan.
        </p>

        <form method="GET" action="{{ route('pengajuan.status') }}" class="mt-5 flex flex-wrap items-start gap-3">
            <div class="min-w-[240px] flex-1">
                <label for="nomor" class="block text-sm font-medium text-gray-700">Nomor Pengajuan</label>
                <input type="text" name="nomor" id="nomor" value="{{ $nomor }}"
                       placeholder="Contoh: SD-20261020-0001"
                       class="mt-1 block w-full rounded-md border bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-1 focus:ring-slate-500 {{ $galatNomor ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-slate-500' }}">
                @if ($galatNomor)
                    <p class="mt-1 text-xs font-medium text-red-600">{{ $galatNomor }}</p>
                @endif
            </div>

            <button type="submit"
                    class="mt-6 rounded-md bg-slate-800 px-5 py-2 text-sm font-medium text-white hover:bg-slate-900">
                Cari
            </button>
        </form>

        @if ($galatCari)
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                {{ $galatCari }}
            </div>
        @endif

        <div class="mt-8">
            <h2 class="text-lg font-semibold text-slate-800">Daftar Pengajuan</h2>

            @if ($daftar->isEmpty())
                <p class="mt-3 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                    Belum ada pengajuan yang diajukan
                </p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <th class="py-2 pr-4 font-medium">Nomor</th>
                                <th class="py-2 pr-4 font-medium">Nama Almarhum</th>
                                <th class="py-2 pr-4 font-medium">Tanggal Pengajuan</th>
                                <th class="py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($daftar as $laporan)
                                <tr>
                                    <td class="py-3 pr-4">
                                        <a href="{{ route('pengajuan.detail', $laporan) }}"
                                           class="font-mono text-xs text-slate-700 underline">
                                            {{ $laporan->no_pengajuan ?? '—' }}
                                        </a>
                                    </td>
                                    <td class="py-3 pr-4 text-slate-800">{{ $laporan->nama_almarhum ?: '—' }}</td>
                                    <td class="py-3 pr-4 text-slate-700">
                                        {{ $laporan->tanggal_pengajuan?->translatedFormat('j F Y, H:i') ?? '—' }}
                                    </td>
                                    <td class="py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $warnaStatus($laporan->status_pengajuan) }}">
                                            {{ $laporan->status_pengajuan }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $daftar->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
