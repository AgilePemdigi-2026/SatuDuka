@extends('tata_letak.utama')

@section('judul', 'Lacak Status Pengajuan')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-soft hover:text-brand">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="mx-auto max-w-3xl">
        <x-ui.page-header title="Lacak Status Pengajuan"
            subtitle="Pantau perkembangan pengajuan laporan kematian dan unduh akta setelah diterbitkan." />

        <form method="GET" action="{{ route('pengajuan.status') }}" class="mt-6 flex items-start gap-3">
            <div class="min-w-0 flex-1">
                <x-ui.input name="nomor" id="nomor" label="Nomor Pengajuan" :value="$nomor"
                    placeholder="Contoh: SD-20261020-0001"
                    :class="$galatNomor ? 'border-red-400' : ''" />

                @if ($galatNomor)
                    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $galatNomor }}</p>
                @endif
            </div>

            <x-ui.button type="submit" class="mt-6 shrink-0">Cari</x-ui.button>
        </form>

        @if ($galatCari)
            <x-ui.alert variant="danger" class="mt-4">
                {{ $galatCari }}
            </x-ui.alert>
        @endif

        <section class="mt-8">
            <h2 class="text-lg font-semibold text-ink">Daftar Pengajuan</h2>

            @if ($daftar->isEmpty())
                <div class="mt-3 rounded-2xl border border-dashed border-line-strong bg-surface p-6 text-center text-sm text-muted">
                    Belum ada pengajuan yang diajukan
                </div>
            @else
                <x-ui.table class="mt-3">
                    <x-slot:head>
                        <th>Nomor</th>
                        <th>Nama Almarhum</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Status</th>
                    </x-slot:head>

                    @foreach ($daftar as $laporan)
                        <tr>
                            <td>
                                <a href="{{ route('pengajuan.detail', $laporan) }}"
                                    class="font-mono text-xs font-semibold text-brand hover:text-brand-hover">
                                    {{ $laporan->no_pengajuan ?? '-' }}
                                </a>
                            </td>
                            <td class="font-medium text-ink">{{ $laporan->nama_almarhum ?: '-' }}</td>
                            <td class="text-xs text-muted">
                                {{ $laporan->tanggal_pengajuan?->translatedFormat('j F Y, H:i') ?? '-' }}
                            </td>
                            <td>
                                @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4">
                    {{ $daftar->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
