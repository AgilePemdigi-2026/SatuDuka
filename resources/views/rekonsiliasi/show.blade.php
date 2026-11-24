@extends('tata_letak.utama')

@section('judul', 'Detail Rekonsiliasi Anomali')

@section('kembali')
    <a href="{{ route('rekonsiliasi.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-brand">
        <x-icon.arrow-left class="h-4 w-4" />
        Kembali ke Antrean Rekonsiliasi
    </a>
@endsection

@section('navigasi')
    @include('komponen.menu_peran')
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    @php
        $kelasLabel = 'mb-1.5 block text-sm font-semibold text-ink-soft';
        $kelasArea = 'block w-full rounded-xl border border-line-strong bg-surface px-3.5 py-3 text-sm text-ink outline-none transition placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30';
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <x-ui.page-header :title="$laporan->nama_almarhum ?: 'Detail Rekonsiliasi'"
            :subtitle="'Nomor Pengajuan: '.($laporan->no_pengajuan ?? '-')">
            <x-slot:aksi>
                @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                <x-ui.button :href="route('verifikasi.show', $laporan)" variant="secondary" size="sm">
                    Detail Berkas
                </x-ui.button>
            </x-slot:aksi>
        </x-ui.page-header>

        @if ($bannerManual)
            <x-ui.alert variant="warning">
                <p class="font-semibold">Layanan eksternal tidak tersedia</p>
                <p class="mt-1 font-normal">
                    Data SIAK atau hasil ekstraksi surat keterangan tidak dapat dimuat. Kolom terkait ditandai
                    "tidak tersedia"; rekonsiliasi tetap dapat dilanjutkan secara manual.
                </p>
            </x-ui.alert>
        @endif

        <x-ui.card title="Komparasi Data">
            <p class="text-sm text-muted">
                Bandingkan isian pemohon, data master SIAK, dan hasil ekstraksi surat keterangan. Data acuan yang
                sah ditentukan oleh verifikator berdasarkan bukti otentik.
            </p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs uppercase tracking-wide text-muted">
                            <th class="py-2 pr-3">Kolom</th>
                            <th class="px-3 py-2">Data Pemohon</th>
                            <th class="px-3 py-2">Data SIAK</th>
                            <th class="px-3 py-2">Ekstraksi Surat Keterangan</th>
                            <th class="pl-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kolomKomparasi as $baris)
                            <tr class="border-b border-line last:border-0 align-top">
                                <td class="py-3 pr-3 font-semibold text-ink">{{ $baris['label'] }}</td>
                                <td class="px-3 py-3 text-ink">{{ $baris['nilai_pemohon'] ?: '—' }}</td>
                                <td class="px-3 py-3">
                                    @if ($siakTersedia)
                                        <span class="text-ink">{{ $baris['nilai_siak'] ?: '—' }}</span>
                                    @else
                                        <span class="text-muted italic">Tidak tersedia</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($ekstraksiTersedia)
                                        <span class="text-ink">{{ $baris['nilai_ekstraksi'] ?: '—' }}</span>
                                    @else
                                        <span class="text-muted italic">Tidak tersedia</span>
                                    @endif
                                </td>
                                <td class="py-3 pl-3">
                                    @php
                                        $varian = match ($baris['status']) {
                                            'cocok' => 'success',
                                            'beda' => 'warning',
                                            default => 'neutral',
                                        };
                                        $labelStatus = match ($baris['status']) {
                                            'cocok' => 'Cocok',
                                            'beda' => 'Beda',
                                            default => 'Tidak terbaca',
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$varian">{{ $labelStatus }}</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-xs text-muted">
                NIK ditampilkan dalam bentuk termasker. NIK utuh hanya dipakai pada proses verifikasi internal dan
                tidak pernah ditulis ke log audit.
            </p>
        </x-ui.card>

        <x-ui.card title="Anomali Terbuka">
            @if ($anomali === [])
                <x-ui.notice icon="check">
                    <p class="font-semibold text-ink">Tidak ada anomali terbuka</p>
                    <p class="mt-1">Seluruh anomali pada pengajuan ini sudah ditutup.</p>
                </x-ui.notice>
            @else
                <ul class="space-y-3">
                    @foreach ($anomali as $item)
                        <li class="rounded-xl border border-line bg-surface p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-ink">{{ $item->jenis_anomali }}</span>
                                <x-ui.badge variant="purple">Terbuka</x-ui.badge>
                            </div>
                            <p class="mt-2 text-sm text-ink-soft">{{ $item->deskripsi }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Aksi Rekonsiliasi">
            <p class="text-sm text-muted">
                Keputusan sepenuhnya milik verifikator.
            </p>

            @include('rekonsiliasi.keputusan')
        </x-ui.card>
    </div>
@endsection



