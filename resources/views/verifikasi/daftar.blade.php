@extends('tata_letak.utama')

@section('judul', 'Verifikasi Berkas')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
        <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Menunggu Verifikasi</h1>
        <p class="mt-1 text-sm text-gray-500">Daftar pengajuan yang menunggu pemeriksaan berkas.</p>

        @if ($daftar->isEmpty())
            <p class="mt-6 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                Belum ada pengajuan yang menunggu verifikasi.
            </p>
        @else
            <div class="mt-6 hidden overflow-hidden rounded-lg border border-gray-200 sm:block">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3 font-medium">Nomor Pengajuan</th>
                            <th class="px-4 py-3 font-medium">Almarhum/Almarhumah</th>
                            <th class="px-4 py-3 font-medium">Tanggal Pengajuan</th>
                            <th class="px-4 py-3 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($daftar as $laporan)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $laporan->no_pengajuan }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ $laporan->nama_almarhum }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $laporan->labelTanggalPengajuan() }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('verifikasi.detail', $laporan) }}"
                                       class="inline-flex rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900">
                                        Periksa
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 space-y-3 sm:hidden">
                @foreach ($daftar as $laporan)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-mono text-xs text-slate-600">{{ $laporan->no_pengajuan }}</span>
                            <span class="text-xs text-gray-500">{{ $laporan->labelTanggalPengajuan() }}</span>
                        </div>
                        <p class="mt-2 font-medium text-slate-800">{{ $laporan->nama_almarhum }}</p>
                        <a href="{{ route('verifikasi.detail', $laporan) }}"
                           class="mt-3 inline-flex rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900">
                            Periksa
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $daftar->links() }}</div>
        @endif
    </div>
@endsection
