@extends('tata_letak.utama')

@section('judul', 'Detail Status Pengajuan')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-soft hover:text-brand">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4 rounded-2xl border border-line bg-surface px-4 py-3">
            <a href="{{ route('pengajuan.status') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-brand">
                <x-icon.arrow-left class="h-4 w-4" />
                Kembali ke Status Pengajuan
            </a>
            <span class="text-xs text-faint">Detail Dokumen</span>
        </div>

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-3xl font-bold tracking-tight text-ink">
                    {{ $laporan->nama_almarhum ?: 'Detail Pengajuan' }}
                </h1>
                <p class="mt-1 text-sm text-muted">
                    Nomor Dokumen:
                    <span class="font-mono text-ink">{{ $laporan->no_pengajuan ?? 'Belum diterbitkan' }}</span>
                </p>
            </div>

            @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                @if ($laporan->catatan_revisi && $laporan->bolehDiubah())
                    <x-ui.alert variant="warning">
                        <p class="font-semibold">Catatan Revisi</p>
                        <p class="mt-1 font-normal">{{ $laporan->catatan_revisi }}</p>
                    </x-ui.alert>
                @endif

                @if ($laporan->dokumenResmi?->alasan_pengembalian)
                    <x-ui.alert variant="warning">
                        <p class="font-semibold">Alasan Pengembalian Dokumen</p>
                        <p class="mt-1 font-normal">{{ $laporan->dokumenResmi->alasan_pengembalian }}</p>
                    </x-ui.alert>
                @endif

                <x-ui.card title="Lini Masa Pengajuan">
                    <x-ui.stepper :steps="$liniMasa" />
                </x-ui.card>
            </div>

            <aside class="space-y-4">
                <x-ui.card title="Informasi Dokumen" padding="p-5">
                    <dl class="divide-y divide-line">
                        <div class="flex items-start justify-between gap-4 py-3 first:pt-0">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nomor Pengajuan</dt>
                            <dd class="text-right font-mono text-sm font-semibold text-ink">
                                {{ $laporan->no_pengajuan ?? '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nama Almarhum/Almarhumah</dt>
                            <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->nama_almarhum ?: '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Tanggal Pengajuan</dt>
                            <dd class="text-right text-sm font-semibold text-ink">
                                {{ $laporan->tanggal_pengajuan?->translatedFormat('j F Y, H:i') ?? '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">NIK</dt>
                            <dd class="text-right font-mono text-sm font-semibold text-ink">
                                {{ \App\Support\MaskerNik::masker($laporan->nik_almarhum) ?? '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3 last:pb-0">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Status</dt>
                            <dd class="text-right">
                                @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                            </dd>
                        </div>
                    </dl>
                </x-ui.card>

                <x-ui.notice icon="shield-check">
                    <p class="font-semibold text-ink">Panel Verifikasi</p>
                    <p class="mt-1">
                        Pengajuan diperiksa oleh verifikator. Dokumen akta dapat diunduh setelah pengajuan
                        berstatus Selesai.
                    </p>
                </x-ui.notice>

                <div class="flex flex-col gap-2">
                    @if ($laporan->bolehDiubah())
                        <x-ui.button :href="route('laporan.ubah', $laporan)" class="w-full">
                            Perbaiki dan Kirim Ulang
                        </x-ui.button>
                    @endif

                    @if ($laporan->selesai())
                        <x-ui.button :href="route('pengajuan.unduh', $laporan)" class="w-full">
                            <x-icon.arrow-down-tray class="h-4 w-4" />
                            Unduh Dokumen (PDF)
                        </x-ui.button>
                    @else
                        <x-ui.button type="button" disabled class="w-full">
                            <x-icon.arrow-down-tray class="h-4 w-4" />
                            Unduh Dokumen (PDF)
                        </x-ui.button>
                    @endif

                    <x-ui.button type="button" variant="secondary" onclick="window.print()" class="w-full">
                        <x-icon.printer class="h-4 w-4" />
                        Cetak Dokumen
                    </x-ui.button>
                </div>

                <p class="text-xs text-faint">*Aktif setelah status "Selesai"</p>

                <p class="text-xs leading-relaxed text-muted">
                    Dokumen ini diterbitkan secara elektronik dan sah menurut Undang-Undang Informasi dan
                    Transaksi Elektronik.
                </p>
            </aside>
        </div>
    </div>
@endsection
