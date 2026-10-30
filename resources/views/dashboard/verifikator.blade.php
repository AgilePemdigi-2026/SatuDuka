@extends('tata_letak.utama')

@section('judul', 'Dashboard Verifikator')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <x-ui.page-header title="Dashboard Verifikator"
        :subtitle="'Selamat datang, '.$pengguna->nama_lengkap.'. Anda masuk sebagai '.$pengguna->labelPeran().'. Antrean verifikasi berkas pengajuan.'">
        <x-slot:aksi>
            <x-ui.button :href="route('verifikasi.index')">Buka Daftar Verifikasi</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.stat label="Menunggu Verifikasi" :value="$statistik['menunggu']" variant="warning" />
            <x-ui.stat label="Dalam Rekonsiliasi" :value="$statistik['rekonsiliasi']" variant="purple" />
            <x-ui.stat label="Selesai" :value="$statistik['selesai']" variant="success" />
        </div>
    </section>

    <section class="mt-8">
        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-ink">Antrean Terbaru</h2>
                    <p class="mt-1 text-sm text-muted">Lima pengajuan terlama yang menunggu tindakan.</p>
                </div>
                <x-ui.button :href="route('verifikasi.index')" size="sm" variant="secondary">
                    Lihat Semua
                </x-ui.button>
            </div>

            @if ($antrean->isEmpty())
                <x-ui.notice icon="check" class="mt-4">
                    <p class="font-semibold text-ink">Tidak ada antrean</p>
                    <p class="mt-1">Semua pengajuan sudah diperiksa.</p>
                </x-ui.notice>
            @else
                <x-ui.table class="mt-4">
                    <x-slot:head>
                        <th>No. Pengajuan</th>
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </x-slot:head>

                    @foreach ($antrean as $item)
                        <tr>
                            <td class="whitespace-nowrap font-mono text-xs font-semibold text-ink">
                                {{ $item->no_pengajuan ?? '-' }}
                            </td>
                            <td class="font-semibold text-ink">{{ $item->nama_almarhum }}</td>
                            <td class="whitespace-nowrap text-xs text-ink-soft">
                                {{ $item->tanggal_pengajuan?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td>@include('komponen.badge_status', ['status' => $item->status_pengajuan])</td>
                            <td class="text-center">
                                <x-ui.button :href="route('verifikasi.show', $item)" size="sm">LIHAT</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>
    </section>
@endsection