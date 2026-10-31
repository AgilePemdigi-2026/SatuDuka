@extends('tata_letak.utama')

@section('judul', 'Rekonsiliasi Anomali')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    <x-ui.page-header title="Antrean Rekonsiliasi Anomali"
        subtitle="Berkas berstatus Dalam Rekonsiliasi Anomali. Bandingkan data pemohon, SIAK, dan surat keterangan sebelum memutuskan.">
        <x-slot:aksi>
            <x-ui.button :href="route('verifikasi.index')" variant="secondary">Antrean Verifikasi</x-ui.button>
            <x-ui.button :href="route('dashboard')" variant="secondary">Dashboard</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8">
        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-ink">Daftar Kasus Anomali</h2>
                    <p class="mt-1 text-sm text-muted">Pilih kasus untuk menelusuri data acuan yang sah.</p>
                </div>

                <form method="GET" action="{{ route('rekonsiliasi.index') }}"
                    class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                    <div class="min-w-0 flex-1 sm:w-72">
                        <x-ui.input name="cari" type="search" :value="request('cari')"
                            placeholder="Cari NIK atau Nama..." />
                    </div>
                    <x-ui.button type="submit">Cari</x-ui.button>
                    @if (request('cari'))
                        <x-ui.button :href="route('rekonsiliasi.index')" variant="secondary">Reset</x-ui.button>
                    @endif
                </form>
            </div>

            @if ($pengajuan->isEmpty())
                <x-ui.notice icon="check" class="mt-4">
                    <p class="font-semibold text-ink">Tidak ada kasus anomali</p>
                    <p class="mt-1">Tidak ada pengajuan yang sedang berada dalam rekonsiliasi.</p>
                </x-ui.notice>
            @else
                <x-ui.table class="mt-4 hidden md:block">
                    <x-slot:head>
                        <th>No. Pengajuan &amp; Waktu</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Anomali Terbuka</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </x-slot:head>

                    @foreach ($pengajuan as $item)
                        @php
                            $terbuka = $item->hasilVerifikasi
                                ->flatMap->anomaliDokumen
                                ->where('status_rekonsiliasi', \App\Models\AnomaliDokumen::STATUS_TERBUKA);
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap">
                                <span class="block font-mono text-xs font-semibold text-ink">
                                    {{ $item->no_pengajuan ?? '-' }}
                                </span>
                                <span class="text-xs text-faint">
                                    {{ $item->tanggal_pengajuan?->format('d/m/Y H:i') ?? '-' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap font-mono text-xs text-ink-soft">
                                {{ \App\Support\MaskerNik::masker($item->nik_almarhum) ?? '-' }}
                            </td>
                            <td class="font-semibold text-ink">{{ $item->nama_almarhum }}</td>
                            <td>
                                @if ($terbuka->isEmpty())
                                    <x-ui.badge variant="neutral">Tidak ada</x-ui.badge>
                                @else
                                    <x-ui.badge variant="purple">{{ $terbuka->count() }} anomali</x-ui.badge>
                                    <span class="mt-1 block text-xs text-muted">
                                        {{ $terbuka->first()->jenis_anomali }}
                                    </span>
                                @endif
                            </td>
                            <td>@include('komponen.badge_status', ['status' => $item->status_pengajuan])</td>
                            <td class="text-center">
                                <x-ui.button :href="route('rekonsiliasi.show', $item)" size="sm">TELUSURI</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4 space-y-3 md:hidden">
                    @foreach ($pengajuan as $item)
                        @php
                            $terbuka = $item->hasilVerifikasi
                                ->flatMap->anomaliDokumen
                                ->where('status_rekonsiliasi', \App\Models\AnomaliDokumen::STATUS_TERBUKA);
                        @endphp
                        <x-ui.card padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-mono text-xs font-semibold text-ink">
                                    {{ $item->no_pengajuan ?? '-' }}
                                </span>
                                @include('komponen.badge_status', ['status' => $item->status_pengajuan])
                            </div>

                            <p class="mt-2 font-semibold text-ink">{{ $item->nama_almarhum }}</p>
                            <p class="mt-1 font-mono text-xs text-ink-soft">
                                {{ \App\Support\MaskerNik::masker($item->nik_almarhum) ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-muted">
                                Waktu: {{ $item->tanggal_pengajuan?->format('d/m/Y H:i') ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-muted">
                                Anomali terbuka: {{ $terbuka->count() }}
                            </p>

                            <div class="mt-3">
                                <x-ui.button :href="route('rekonsiliasi.show', $item)" size="sm">TELUSURI</x-ui.button>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>

                <div class="mt-4">{{ $pengajuan->links() }}</div>
            @endif
        </x-ui.card>
    </section>
@endsection
