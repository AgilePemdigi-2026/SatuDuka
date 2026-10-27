@extends('tata_letak.utama')

@section('judul', 'Verifikasi Berkas')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-soft hover:text-brand">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <x-ui.page-header title="Menunggu Verifikasi"
        subtitle="Daftar pengajuan yang menunggu pemeriksaan berkas." />

    @php
        $jumlahAntrean = $daftar->total();
        $kelasPilihan = 'block h-[46px] w-full appearance-none rounded-xl border border-line-strong bg-surface px-3.5 pr-10 text-sm text-ink outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/30';
    @endphp

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Menunggu Verifikasi" :value="$jumlahAntrean" variant="warning" />
    </div>

    <x-ui.card padding="p-4" class="mt-6">
        <form method="GET" action="{{ route('verifikasi.daftar') }}"
            class="flex flex-wrap items-end justify-end gap-3">
            <div class="w-full sm:w-auto">
                <label for="urut" class="mb-1.5 block text-sm font-semibold text-ink-soft">Urutkan</label>
                <div class="relative">
                    <select id="urut" name="urut" class="{{ $kelasPilihan }}">
                        @foreach (['terbaru' => 'Terbaru', 'terlama' => 'Terlama'] as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($urut === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-icon.chevron-down class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" />
                </div>
            </div>

            <x-ui.button type="submit" size="sm">Terapkan</x-ui.button>
        </form>
    </x-ui.card>

    @if ($daftar->isEmpty())
        <div class="mt-6 rounded-2xl border border-dashed border-line-strong bg-surface p-6 text-center text-sm text-muted">
            Belum ada pengajuan yang menunggu verifikasi.
        </div>
    @else
        <x-ui.table class="mt-6 hidden sm:block">
            <x-slot:head>
                <th class="w-12 text-center">No</th>
                <th>No. Pengajuan</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Waktu</th>
                <th>Status</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @foreach ($daftar as $laporan)
                <tr>
                    <td class="text-center text-xs font-semibold text-muted tabular-nums">
                        {{ $daftar->firstItem() + $loop->index }}
                    </td>
                    <td class="font-mono text-[13px] font-semibold text-ink tabular-nums">
                        {{ $laporan->no_pengajuan }}
                    </td>
                    <td class="font-mono text-xs text-ink-soft tabular-nums">
                        {{ \App\Support\MaskerNik::masker($laporan->nik_almarhum) ?? '-' }}
                    </td>
                    <td class="font-medium text-ink">{{ $laporan->nama_almarhum }}</td>
                    <td>
                        @if ($laporan->tanggal_pengajuan)
                            <div class="text-xs font-semibold text-ink">
                                {{ $laporan->tanggal_pengajuan->translatedFormat('j M Y') }}
                            </div>
                            <div class="text-[11px] text-faint">{{ $laporan->tanggal_pengajuan->format('H:i') }} WIB</div>
                        @else
                            <span class="text-xs text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                    </td>
                    <td class="text-right">
                        <x-ui.button :href="route('verifikasi.detail', $laporan)" size="sm">Lihat</x-ui.button>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>

        <div class="mt-6 space-y-3 sm:hidden">
            @foreach ($daftar as $laporan)
                <x-ui.card padding="p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-mono text-xs font-semibold text-ink-soft tabular-nums">
                            {{ $laporan->no_pengajuan }}
                        </span>
                        @if ($laporan->tanggal_pengajuan)
                            <span class="text-xs text-faint">{{ $laporan->labelTanggalPengajuan() }}</span>
                        @endif
                    </div>
                    <p class="mt-2 font-semibold text-ink">{{ $laporan->nama_almarhum }}</p>
                    <p class="mt-1 font-mono text-xs text-muted">
                        NIK: {{ \App\Support\MaskerNik::masker($laporan->nik_almarhum) ?? '-' }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                    </div>
                    <div class="mt-3">
                        <x-ui.button :href="route('verifikasi.detail', $laporan)" size="sm">Lihat</x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-muted">
                Menampilkan {{ $daftar->firstItem() }}&ndash;{{ $daftar->lastItem() }} dari
                {{ $daftar->total() }} pembaruan
            </p>

            @if ($daftar->hasPages())
                @php
                    $kelasHalaman = 'inline-flex h-8 min-w-8 items-center justify-center rounded-lg border px-2 text-xs font-semibold transition-colors';
                @endphp
                <nav class="flex flex-wrap items-center gap-1" aria-label="Navigasi halaman">
                    @if ($daftar->onFirstPage())
                        <span class="{{ $kelasHalaman }} cursor-not-allowed border-line bg-canvas text-faint">
                            <x-icon.chevron-left class="h-4 w-4" />
                        </span>
                    @else
                        <a href="{{ $daftar->previousPageUrl() }}"
                            class="{{ $kelasHalaman }} border-line-strong bg-surface text-ink-soft hover:bg-canvas"
                            aria-label="Halaman sebelumnya">
                            <x-icon.chevron-left class="h-4 w-4" />
                        </a>
                    @endif

                    @foreach ($daftar->getUrlRange(max(1, $daftar->currentPage() - 2), min($daftar->lastPage(), $daftar->currentPage() + 2)) as $halaman => $tautan)
                        @if ($halaman === $daftar->currentPage())
                            <span aria-current="page" class="{{ $kelasHalaman }} border-brand bg-brand text-white">
                                {{ $halaman }}
                            </span>
                        @else
                            <a href="{{ $tautan }}"
                                class="{{ $kelasHalaman }} border-line-strong bg-surface text-ink-soft hover:bg-canvas">
                                {{ $halaman }}
                            </a>
                        @endif
                    @endforeach

                    @if ($daftar->hasMorePages())
                        <a href="{{ $daftar->nextPageUrl() }}"
                            class="{{ $kelasHalaman }} border-line-strong bg-surface text-ink-soft hover:bg-canvas"
                            aria-label="Halaman berikutnya">
                            <x-icon.chevron-right class="h-4 w-4" />
                        </a>
                    @else
                        <span class="{{ $kelasHalaman }} cursor-not-allowed border-line bg-canvas text-faint">
                            <x-icon.chevron-right class="h-4 w-4" />
                        </span>
                    @endif
                </nav>
            @endif
        </div>
    @endif
@endsection
