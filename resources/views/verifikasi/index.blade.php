@extends('tata_letak.utama')

@section('judul', 'Dashboard Verifikator')

@section('navigasi')
    <a href="{{ route('verifikasi.daftar') }}" class="hidden text-sm font-semibold text-ink-soft hover:text-brand sm:inline">
        Verifikasi Baca Saja
    </a>
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    <x-ui.page-header title="Dashboard Verifikator"
        subtitle="Ringkasan seluruh proses verifikasi dan rekonsiliasi berkas pengajuan." />

    <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat label="Total Antrean" :value="$pengajuan->total()" variant="warning" />
        <x-ui.stat label="Menunggu Verifikasi" :value="$pengajuan->total()" variant="info" />
        <x-ui.stat label="Hasil Pencarian" :value="$pengajuan->count()" variant="neutral" />
        <x-ui.stat label="Halaman" :value="$pengajuan->currentPage()" variant="success" />
    </section>

    <section class="mt-8">
        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-ink">Daftar Permohonan</h2>
                    <p class="mt-1 text-sm text-muted">Pilih berkas untuk melakukan pemeriksaan detail.</p>
                </div>

                <form method="GET" action="{{ route('verifikasi.index') }}"
                    class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                    <div class="min-w-0 flex-1 sm:w-72">
                        <x-ui.input name="cari" type="search" :value="request('cari')"
                            placeholder="Cari NIK atau Nama..." />
                    </div>
                    <x-ui.button type="submit">Cari</x-ui.button>
                    @if (request('cari'))
                        <x-ui.button :href="route('verifikasi.index')" variant="secondary">Reset</x-ui.button>
                    @endif
                </form>
            </div>

            @if ($pengajuan->isEmpty())
                <x-ui.notice icon="check" class="mt-4">
                    <p class="font-semibold text-ink">Tidak ada berkas yang menunggu verifikasi</p>
                    <p class="mt-1">Semua pengajuan sudah diperiksa.</p>
                </x-ui.notice>
            @else
                <x-ui.table class="mt-4">
                    <x-slot:head>
                        <th class="w-16">No.</th>
                        <th>No. Pengajuan</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </x-slot:head>

                    @foreach ($pengajuan as $index => $item)
                        <tr>
                            <td class="text-xs text-faint">{{ $pengajuan->firstItem() + $index }}.</td>
                            <td class="whitespace-nowrap font-mono text-xs font-semibold text-ink">
                                {{ $item->no_pengajuan ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap font-mono text-xs text-ink-soft">
                                {{ \App\Support\MaskerNik::masker($item->nik_almarhum) ?? '-' }}
                            </td>
                            <td class="font-semibold text-ink">{{ $item->nama_almarhum }}</td>
                            <td class="whitespace-nowrap">
                                @if ($item->tanggal_pengajuan)
                                    <span class="block text-xs font-semibold text-ink">
                                        {{ $item->tanggal_pengajuan->format('d/m/Y') }}
                                    </span>
                                    <span class="text-xs text-faint">{{ $item->tanggal_pengajuan->format('H:i') }}</span>
                                @else
                                    <span class="text-xs text-faint">-</span>
                                @endif
                            </td>
                            <td>
                                @include('komponen.badge_status', ['status' => $item->status_pengajuan])
                            </td>
                            <td class="text-center">
                                <x-ui.button :href="route('verifikasi.show', $item)" size="sm">LIHAT</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4">{{ $pengajuan->links() }}</div>
            @endif
        </x-ui.card>
    </section>
@endsection
