@extends('tata_letak.utama')

@section('judul', 'Verifikasi Berkas')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    <x-ui.page-header title="Antrean Verifikasi Berkas"
        subtitle="Urutan mengikuti keparahan anomali tertinggi dari mesin, lalu tanggal pengajuan.">
        <x-slot:aksi>
            <x-ui.button :href="route('rekonsiliasi.index')" variant="secondary">Rekonsiliasi Anomali</x-ui.button>
            <x-ui.button :href="route('dashboard')" variant="secondary">Dashboard</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Menunggu Verifikasi" :value="$statistik['menunggu']" variant="warning" />
        <x-ui.stat label="Dalam Rekonsiliasi" :value="$statistik['rekonsiliasi']" variant="purple" />
        <x-ui.stat label="Selesai" :value="$statistik['selesai']" variant="success" />
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
                @php
                    $baris = $pengajuan->getCollection()->map(function ($item) {
                        $anomali = $item->hasil_analisis?->anomali ?? [];
                        $teratas = $anomali[0] ?? null;

                        return [
                            'item' => $item,
                            'teratas' => $teratas,
                            'lainnya' => max(0, count($anomali) - 1),
                            'label' => $teratas
                                ? config('verifikasi.taksonomi_anomali.'.$teratas->kode.'.label', $teratas->kode)
                                : null,
                            'varian' => match ($teratas?->keparahan) {
                                'fatal' => 'danger',
                                'tinggi' => 'warning',
                                'sedang' => 'info',
                                'rendah' => 'neutral',
                                'info' => 'neutral',
                                default => 'success',
                            },
                        ];
                    });
                @endphp

                <x-ui.table class="mt-4 hidden md:block">
                    <x-slot:head>
                        <th>No. Pengajuan &amp; Waktu</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Anomali</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </x-slot:head>

                    @foreach ($baris as $data)
                        @php($item = $data['item'])
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
                                @if ($data['teratas'])
                                    <x-ui.badge :variant="$data['varian']">{{ ucfirst($data['teratas']->keparahan) }}</x-ui.badge>
                                    <span class="mt-1 block text-xs text-muted">{{ $data['label'] }}</span>
                                    @if ($data['lainnya'] > 0)
                                        <span class="mt-1 block text-xs font-semibold text-ink-soft">
                                            +{{ $data['lainnya'] }} lainnya
                                        </span>
                                    @endif
                                @else
                                    <x-ui.badge variant="success">Bersih</x-ui.badge>
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

                <div class="mt-4 space-y-3 md:hidden">
                    @foreach ($baris as $data)
                        @php($item = $data['item'])
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

                            <div class="mt-2">
                                @if ($data['teratas'])
                                    <x-ui.badge :variant="$data['varian']">{{ ucfirst($data['teratas']->keparahan) }}</x-ui.badge>
                                    <span class="text-xs text-muted">{{ $data['label'] }}</span>
                                    @if ($data['lainnya'] > 0)
                                        <span class="block text-xs font-semibold text-ink-soft">
                                            +{{ $data['lainnya'] }} lainnya
                                        </span>
                                    @endif
                                @else
                                    <x-ui.badge variant="success">Bersih</x-ui.badge>
                                @endif
                            </div>

                            <div class="mt-3">
                                <x-ui.button :href="route('verifikasi.show', $item)" size="sm">LIHAT</x-ui.button>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>

                <div class="mt-4">{{ $pengajuan->links() }}</div>
            @endif
        </x-ui.card>
    </section>
@endsection
