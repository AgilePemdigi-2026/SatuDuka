@extends('tata_letak.utama')

@section('judul', 'Lacak Status Pengajuan')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('sidebar')
    @include('komponen.sidebar_pemohon')
@endsection

@section('konten')
    <x-ui.page-header title="Lacak Status Pengajuan"
        subtitle="Pantau perkembangan pengajuan laporan kematian dan unduh akta setelah diterbitkan.">
        <x-slot:aksi>
            <x-ui.button :href="route('dashboard')" variant="secondary">Kembali ke Dashboard</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8">
        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-ink">Daftar Pengajuan</h2>
                    <p class="mt-1 text-sm text-muted">Cari nomor pengajuan untuk melihat status terkininya.</p>
                </div>

                <form method="GET" action="{{ route('pengajuan.status') }}"
                    class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                    <div class="min-w-0 flex-1 sm:w-64">
                        <x-ui.input name="nomor" type="search" :value="$nomor" aria-label="Nomor Pengajuan"
                            placeholder="Masukkan nomor pengajuan" :aria-invalid="$galatNomor ? 'true' : 'false'" />
                    </div>

                    <x-ui.button type="submit">Cari</x-ui.button>
                </form>
            </div>

            @if ($galatNomor)
                <x-ui.alert variant="danger" class="mt-4">{{ $galatNomor }}</x-ui.alert>
            @endif

            @if ($galatCari)
                <x-ui.alert variant="danger" class="mt-4">{{ $galatCari }}</x-ui.alert>
            @endif

            @if ($daftar->isEmpty())
                <x-ui.empty class="mt-4" judul="Belum ada pengajuan">
                    Pengajuan yang sudah dikirim akan muncul di sini.
                </x-ui.empty>
            @else
                <x-ui.table class="mt-4 hidden lg:block">
                    <x-slot:head>
                        <th>Nomor</th>
                        <th>Nama Almarhum</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Status</th>
                    </x-slot:head>

                    @foreach ($daftar as $laporan)
                        <tr>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('pengajuan.detail', $laporan) }}"
                                    class="font-mono text-xs font-semibold text-brand hover:underline">
                                    {{ $laporan->no_pengajuan ?? '-' }}
                                </a>
                            </td>
                            <td class="font-semibold text-ink">{{ $laporan->nama_almarhum ?: '-' }}</td>
                            <td class="text-muted">
                                {{ $laporan->tanggal_pengajuan?->translatedFormat('j F Y, H:i') ?? '-' }}
                            </td>
                            <td>@include('komponen.badge_status', ['status' => $laporan->status_pengajuan])</td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4 space-y-3 lg:hidden">
                    @foreach ($daftar as $laporan)
                        <x-ui.card padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <a href="{{ route('pengajuan.detail', $laporan) }}"
                                    class="font-mono text-xs font-semibold text-brand hover:underline">
                                    {{ $laporan->no_pengajuan ?? '-' }}
                                </a>
                                @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                            </div>

                            <p class="mt-2 font-semibold text-ink">{{ $laporan->nama_almarhum ?: '-' }}</p>
                            <p class="mt-1 text-xs text-muted">
                                {{ $laporan->tanggal_pengajuan?->translatedFormat('j F Y, H:i') ?? '-' }}
                            </p>
                        </x-ui.card>
                    @endforeach
                </div>

                @if ($daftar->hasPages())
                    <div class="mt-4">{{ $daftar->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </section>
@endsection
