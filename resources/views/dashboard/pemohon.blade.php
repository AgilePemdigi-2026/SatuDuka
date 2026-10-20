@extends('tata_letak.utama')

@section('judul', 'Dashboard Pemohon')

@section('navigasi')
    <a href="{{ route('profil.tampilkan') }}" class="text-sm text-gray-600 hover:underline">Kelola Profil</a>
    <span class="text-sm text-gray-700">{{ $pengguna->nama_lengkap }}</span>
    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Keluar</button>
    </form>
@endsection

@section('konten')
    @php
        $warnaStatus = fn (string $status) => match ($status) {
            \App\Models\LaporanKematian::STATUS_DRAF => 'bg-gray-100 text-gray-700',
            \App\Models\LaporanKematian::STATUS_SELESAI => 'bg-green-100 text-green-800',
            \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI => 'bg-red-100 text-red-800',
            default => 'bg-amber-100 text-amber-800',
        };
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Dashboard Pemohon</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Selamat datang, {{ $pengguna->nama_lengkap }}. Layanan Pencatatan &amp; Administrasi Kematian Kota Yogyakarta.
                </p>
            </div>
            <a href="{{ route('laporan.baru') }}"
               class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                Ajukan Laporan Baru
            </a>
        </div>

        <div class="mt-8">
            <h2 class="text-lg font-semibold text-slate-800">Draf dan Pengajuan Saya</h2>

            @if ($daftarLaporan->isEmpty())
                <p class="mt-3 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                    Belum ada pengajuan. Mulai dengan menekan tombol Ajukan Laporan Baru.
                </p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <th class="py-2 pr-4 font-medium">Nomor Pengajuan</th>
                                <th class="py-2 pr-4 font-medium">Almarhum/Almarhumah</th>
                                <th class="py-2 pr-4 font-medium">Tanggal Kematian</th>
                                <th class="py-2 pr-4 font-medium">Status</th>
                                <th class="py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($daftarLaporan as $laporan)
                                <tr>
                                    <td class="py-3 pr-4 font-mono text-xs text-slate-700">
                                        {{ $laporan->no_pengajuan ?? '—' }}
                                    </td>
                                    <td class="py-3 pr-4 text-slate-800">{{ $laporan->nama_almarhum ?: '—' }}</td>
                                    <td class="py-3 pr-4 text-slate-700">
                                        {{ $laporan->tanggal_kematian?->translatedFormat('j F Y') ?? '—' }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $warnaStatus($laporan->status_pengajuan) }}">
                                            {{ $laporan->status_pengajuan }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        @if ($laporan->adalahDraf())
                                            <a href="{{ route('laporan.ubah', $laporan) }}"
                                               class="text-slate-700 underline">Lanjutkan</a>
                                        @else
                                            <a href="{{ route('laporan.lihat', $laporan) }}"
                                               class="text-slate-700 underline">Lihat</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-semibold text-slate-800">Status Pengajuan</h2>
                    <a href="{{ route('pengajuan.status') }}" class="text-sm text-slate-700 underline">Lihat semua</a>
                </div>

                @if ($ringkasanStatus->isEmpty())
                    <p class="mt-1 text-sm text-gray-500">Belum ada pengajuan yang diajukan.</p>
                @else
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($ringkasanStatus as $status => $jumlah)
                            <li class="flex items-center justify-between gap-2">
                                <span class="text-gray-600">{{ $status }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $warnaStatus($status) }}">{{ $jumlah }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Profil Saya</h2>
                <p class="mt-1 text-sm text-gray-500">
                    <a href="{{ route('profil.tampilkan') }}" class="text-slate-700 underline">Kelola Profil</a>
                </p>
            </div>
        </div>
    </div>
@endsection
