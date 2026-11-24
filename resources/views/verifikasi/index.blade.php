@extends('tata_letak.utama')

@section('judul', 'Dashboard Verifikator')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    @php
        // Mesin rekonsiliasi mengirim nama varian lama (badge-*) lewat atribut
        // ringkasan_anomali. Petakan ke varian lencana yang sekarang dipakai.
        $varianAnomali = [
            'badge-bad' => 'danger',
            'badge-warning' => 'warning',
            'badge-brand' => 'info',
        ];
    @endphp

    <x-ui.page-header title="Dashboard Verifikator" subtitle="Ringkasan seluruh proses verifikasi dan rekonsiliasi">
        <x-slot:aksi>
            <x-ui.button :href="route('rekonsiliasi.index')" variant="secondary">Rekonsiliasi Anomali</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat label="Total Permohonan" :value="$ringkasan['total']" variant="brand" />
        <x-ui.stat label="Menunggu Verifikasi" :value="$ringkasan['menunggu']" variant="warning" />
        <x-ui.stat label="Anomali Perlu Rekonsiliasi" :value="$ringkasan['rekonsiliasi']" variant="danger" />
        <x-ui.stat label="Selesai" :value="$ringkasan['selesai']" variant="success" />
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
                    <div class="w-full sm:w-48">
                        <x-ui.select name="status" :value="$statusFilter" :options="[
                            'semua' => 'Semua Status',
                            'disetujui' => 'Disetujui',
                            'menunggu' => 'Menunggu Verifikasi',
                            'rekonsiliasi' => 'Perlu Rekonsiliasi',
                            'selesai' => 'Selesai',
                        ]" onchange="this.form.submit()" />
                    </div>

                    <div class="min-w-0 flex-1 sm:w-64">
                        <x-ui.input name="cari" type="search" :value="request('cari')"
                            placeholder="Cari NIK atau Nama..." />
                    </div>

                    <x-ui.button type="submit">Cari</x-ui.button>

                    @if (request('cari') || $statusFilter !== 'semua')
                        <x-ui.button :href="route('verifikasi.index')" variant="secondary">Reset</x-ui.button>
                    @endif
                </form>
            </div>

            @if ($pengajuan->isEmpty())
                <x-ui.notice icon="check" class="mt-4">
                    <p class="font-semibold text-ink">Tidak ada permohonan yang sesuai</p>
                    <p class="mt-1">Coba ubah status atau kata kunci pencarian.</p>
                </x-ui.notice>
            @else
                <x-ui.table class="mt-4 hidden md:block">
                    <x-slot:head>
                        <th>No.</th>
                        <th>No. Pengajuan</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Anomali</th>
                        <th>Tanggal Masuk</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </x-slot:head>

                    @foreach ($pengajuan as $index => $item)
                        @php($anom = $item->getAttribute('ringkasan_anomali'))
                        <tr>
                            <td class="text-faint">{{ $pengajuan->firstItem() + $index }}.</td>
                            <td class="whitespace-nowrap font-mono text-xs font-semibold text-ink">
                                {{ $item->no_pengajuan }}
                            </td>
                            <td class="whitespace-nowrap font-mono text-xs text-ink-soft">
                                {{ \App\Support\MaskerNik::masker($item->nik_almarhum) ?? '-' }}
                            </td>
                            <td class="font-semibold text-ink">{{ $item->nama_almarhum }}</td>
                            <td>
                                @if (filled($anom['label'] ?? null))
                                    <x-ui.badge :variant="$varianAnomali[$anom['varian']] ?? 'neutral'">
                                        {{ $anom['label'] }}
                                    </x-ui.badge>
                                    @if ($anom['lainnya'] > 0)
                                        <span class="mt-1 block text-xs text-muted">+{{ $anom['lainnya'] }} lainnya</span>
                                    @endif
                                @else
                                    <x-ui.badge variant="success">Bersih</x-ui.badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-muted">
                                {{ $item->tanggal_pengajuan ? \Carbon\Carbon::parse($item->tanggal_pengajuan)->format('d/m/Y') : '-' }}
                            </td>
                            <td>@include('komponen.badge_status', ['status' => $item->status_pengajuan])</td>
                            <td class="text-center">
                                @if ($item->status_pengajuan === \App\Models\LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
                                    <x-ui.button :href="route('verifikasi.show', $item)" size="sm">VERIFIKASI</x-ui.button>
                                @else
                                    <span class="text-faint">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4 space-y-3 md:hidden">
                    @foreach ($pengajuan as $item)
                        @php($anom = $item->getAttribute('ringkasan_anomali'))
                        <x-ui.card padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-mono text-xs font-semibold text-ink">{{ $item->no_pengajuan }}</span>
                                @include('komponen.badge_status', ['status' => $item->status_pengajuan])
                            </div>

                            <p class="mt-2 font-semibold text-ink">{{ $item->nama_almarhum }}</p>
                            <p class="mt-1 font-mono text-xs text-ink-soft">
                                {{ \App\Support\MaskerNik::masker($item->nik_almarhum) ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-muted">
                                Tanggal masuk:
                                {{ $item->tanggal_pengajuan ? \Carbon\Carbon::parse($item->tanggal_pengajuan)->format('d/m/Y') : '-' }}
                            </p>

                            <div class="mt-2">
                                @if (filled($anom['label'] ?? null))
                                    <x-ui.badge :variant="$varianAnomali[$anom['varian']] ?? 'neutral'">
                                        {{ $anom['label'] }}
                                    </x-ui.badge>
                                    @if ($anom['lainnya'] > 0)
                                        <span class="mt-1 block text-xs text-muted">+{{ $anom['lainnya'] }} lainnya</span>
                                    @endif
                                @else
                                    <x-ui.badge variant="success">Bersih</x-ui.badge>
                                @endif
                            </div>

                            @if ($item->status_pengajuan === \App\Models\LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
                                <div class="mt-3">
                                    <x-ui.button :href="route('verifikasi.show', $item)" size="sm">VERIFIKASI</x-ui.button>
                                </div>
                            @endif
                        </x-ui.card>
                    @endforeach
                </div>

                @if ($pengajuan->hasPages())
                    <div class="mt-4">{{ $pengajuan->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </section>
@endsection
