@extends('tata_letak.utama')

@section('judul', 'Tanda Tangan Dokumen')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    <x-ui.page-header title="Tanda Tangan Dokumen"
        subtitle="Dokumen yang telah disetujui verifikator dan menunggu tanda tangan elektronik.">
        <x-slot:aksi>
            <x-ui.button :href="route('dashboard')" variant="secondary">Dashboard</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Menunggu Tindakan" :value="$statistik['menunggu']" variant="warning" />
        <x-ui.stat label="Selesai" :value="$statistik['selesai']" variant="success" />
        <x-ui.stat label="Dikembalikan" :value="$statistik['dikembalikan']" variant="danger" />
    </section>

    <section class="mt-8">
        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-ink">Daftar Dokumen</h2>
                    <p class="mt-1 text-sm text-muted">Cari berdasarkan nomor pengajuan, NIK, atau nama.</p>
                </div>

                <form method="GET" action="{{ route('tanda_tangan.index') }}"
                    class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                    <div class="min-w-0 flex-1 sm:w-64">
                        <x-ui.input name="cari" type="search" :value="$cari"
                            placeholder="Cari nomor pengajuan atau NIK..." />
                    </div>

                    <div class="w-40">
                        <x-ui.select name="status" :value="$status" placeholder="Semua Status" :options="[
                            \App\Models\DokumenResmi::STATUS_MENUNGGU => 'Menunggu',
                            \App\Models\DokumenResmi::STATUS_SELESAI => 'Selesai',
                            \App\Models\DokumenResmi::STATUS_DIKEMBALIKAN => 'Dikembalikan',
                        ]" />
                    </div>

                    <x-ui.button type="submit">Cari</x-ui.button>

                    @if ($cari !== '' || $status !== '')
                        <x-ui.button :href="route('tanda_tangan.index')" variant="secondary">Reset</x-ui.button>
                    @endif
                </form>
            </div>

            @if ($daftar->isEmpty())
                <x-ui.notice icon="check" class="mt-4">
                    <p class="font-semibold text-ink">Tidak ada dokumen</p>
                    <p class="mt-1">Belum ada dokumen yang sesuai dengan pencarian.</p>
                </x-ui.notice>
            @else
                <x-ui.table class="mt-4 hidden md:block">
                    <x-slot:head>
                        <th>No. Pengajuan &amp; Waktu</th>
                        <th>NIK</th>
                        <th>Nama Pemohon/Almarhum</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </x-slot:head>

                    @foreach ($daftar as $dokumen)
                        @php($laporan = $dokumen->laporanKematian)
                        <tr>
                            <td class="whitespace-nowrap">
                                <span class="block font-mono text-xs font-semibold text-ink">
                                    {{ $laporan?->no_pengajuan ?? '-' }}
                                </span>
                                <span class="text-xs text-faint">
                                    {{ $laporan?->tanggal_pengajuan?->format('d/m/Y H:i') ?? '-' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap font-mono text-xs text-ink-soft">
                                {{ \App\Support\MaskerNik::masker($laporan?->nik_almarhum) ?? '-' }}
                            </td>
                            <td class="font-semibold text-ink">{{ $laporan?->nama_almarhum ?? '-' }}</td>
                            <td>
                                <x-ui.badge :variant="match ($dokumen->status_ttd) {
                                    \App\Models\DokumenResmi::STATUS_MENUNGGU => 'warning',
                                    \App\Models\DokumenResmi::STATUS_SELESAI => 'success',
                                    default => 'danger',
                                }">{{ $dokumen->status_ttd }}</x-ui.badge>
                            </td>
                            <td class="text-center">
                                @if ($laporan !== null)
                                    <x-ui.button :href="route('tanda_tangan.show', $laporan)" size="sm">
                                        {{ $dokumen->status_ttd === \App\Models\DokumenResmi::STATUS_MENUNGGU ? 'LIHAT' : 'DETAIL' }}
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4 space-y-3 md:hidden">
                    @foreach ($daftar as $dokumen)
                        @php($laporan = $dokumen->laporanKematian)
                        <x-ui.card padding="p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-mono text-xs font-semibold text-ink">
                                    {{ $laporan?->no_pengajuan ?? '-' }}
                                </span>
                                <x-ui.badge :variant="match ($dokumen->status_ttd) {
                                    \App\Models\DokumenResmi::STATUS_MENUNGGU => 'warning',
                                    \App\Models\DokumenResmi::STATUS_SELESAI => 'success',
                                    default => 'danger',
                                }">{{ $dokumen->status_ttd }}</x-ui.badge>
                            </div>

                            <p class="mt-2 font-semibold text-ink">{{ $laporan?->nama_almarhum ?? '-' }}</p>
                            <p class="mt-1 font-mono text-xs text-ink-soft">
                                {{ \App\Support\MaskerNik::masker($laporan?->nik_almarhum) ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-muted">
                                Waktu: {{ $laporan?->tanggal_pengajuan?->format('d/m/Y H:i') ?? '-' }}
                            </p>

                            @if ($laporan !== null)
                                <div class="mt-3">
                                    <x-ui.button :href="route('tanda_tangan.show', $laporan)" size="sm">
                                        {{ $dokumen->status_ttd === \App\Models\DokumenResmi::STATUS_MENUNGGU ? 'LIHAT' : 'DETAIL' }}
                                    </x-ui.button>
                                </div>
                            @endif
                        </x-ui.card>
                    @endforeach
                </div>

                <div class="mt-4">{{ $daftar->links() }}</div>
            @endif
        </x-ui.card>
    </section>
@endsection
