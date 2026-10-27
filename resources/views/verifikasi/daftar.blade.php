@extends('tata_letak.utama')

@section('judul', 'Verifikasi Berkas')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
        <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Menunggu Verifikasi</h1>
        <p class="mt-1 text-sm text-gray-500">
            Daftar pengajuan yang menunggu pemeriksaan berkas. Skor kecurigaan hanya alat bantu
            prioritas dan tidak mengubah status pengajuan.
        </p>

        <form method="GET" action="{{ route('verifikasi.daftar') }}"
              class="mt-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
            <div>
                <label for="prioritas" class="block text-xs font-medium text-gray-600">Prioritas</label>
                <select id="prioritas" name="prioritas"
                        class="mt-1 rounded-md border-gray-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    @foreach (['semua' => 'Semua', 'tinggi' => 'Tinggi (60-100)', 'sedang' => 'Sedang (30-59)', 'rendah' => 'Rendah (0-29)'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($prioritas === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="urut" class="block text-xs font-medium text-gray-600">Urutkan</label>
                <select id="urut" name="urut"
                        class="mt-1 rounded-md border-gray-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    @foreach (['prioritas' => 'Skor tertinggi', 'terbaru' => 'Terbaru', 'terlama' => 'Terlama'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($urut === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Terapkan
            </button>
        </form>

        @if ($daftar->isEmpty())
            <p class="mt-6 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                @if ($prioritas === 'semua')
                    Belum ada pengajuan yang menunggu verifikasi.
                @else
                    Tidak ada pengajuan dengan prioritas {{ $prioritas }}.
                @endif
            </p>
        @else
            <div class="mt-6 hidden overflow-hidden rounded-lg border border-gray-200 sm:block">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3 font-medium">Nomor Pengajuan</th>
                            <th class="px-4 py-3 font-medium">Almarhum/Almarhumah</th>
                            <th class="px-4 py-3 font-medium">Tanggal Pengajuan</th>
                            <th class="px-4 py-3 font-medium">Skor</th>
                            <th class="px-4 py-3 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($daftar as $laporan)
                            @php
                                $skor = $skorPerLaporan[$laporan->id_laporan] ?? ['skor' => 0, 'kategori' => 'rendah'];
                                $warnaSkor = match ($skor['kategori']) {
                                    'tinggi' => 'bg-rose-100 text-rose-800',
                                    'sedang' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-green-100 text-green-800',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $laporan->no_pengajuan }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ $laporan->nama_almarhum }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $laporan->labelTanggalPengajuan() }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $warnaSkor }}">
                                        {{ $skor['skor'] }}/100 · {{ $skor['kategori'] }}
                                    </span>
                                </td>
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
                    @php
                        $skor = $skorPerLaporan[$laporan->id_laporan] ?? ['skor' => 0, 'kategori' => 'rendah'];
                        $warnaSkor = match ($skor['kategori']) {
                            'tinggi' => 'bg-rose-100 text-rose-800',
                            'sedang' => 'bg-amber-100 text-amber-800',
                            default => 'bg-green-100 text-green-800',
                        };
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-mono text-xs text-slate-600">{{ $laporan->no_pengajuan }}</span>
                            <span class="text-xs text-gray-500">{{ $laporan->labelTanggalPengajuan() }}</span>
                        </div>
                        <p class="mt-2 font-medium text-slate-800">{{ $laporan->nama_almarhum }}</p>
                        <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $warnaSkor }}">
                            Skor {{ $skor['skor'] }}/100 · {{ $skor['kategori'] }}
                        </span>
                        <div>
                            <a href="{{ route('verifikasi.detail', $laporan) }}"
                               class="mt-3 inline-flex rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900">
                                Periksa
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $daftar->links() }}</div>
        @endif
    </div>
@endsection
