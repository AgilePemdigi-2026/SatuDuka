@if ($daftar->isEmpty())
    <x-ui.empty judul="Belum ada pengajuan">
        {{ $kosong ?? 'Belum ada pengajuan yang diajukan' }}
    </x-ui.empty>
@else
    {{-- Tampilan Desktop (Tabel) --}}
    <x-ui.table class="hidden md:block">
        <x-slot:head>
            <th>Nomor Pengajuan</th>
            <th>Almarhum/Almarhumah</th>
            <th>Tanggal Pengajuan</th>
            <th>Status &amp; Keterangan</th>
            <th class="text-center">Aksi</th>
        </x-slot:head>

        @foreach ($daftar as $laporan)
            @php
                $perluAlasan = in_array($laporan->status_pengajuan, [
                    \App\Models\LaporanKematian::STATUS_DIKEMBALIKAN,
                    \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
                    \App\Models\LaporanKematian::STATUS_MENUNGGU_PERBAIKAN,
                ], true);
                $alasanPerbaikan = $perluAlasan ? $laporan->labelAlasanPerbaikan() : null;
            @endphp
            <tr>
                <td class="whitespace-nowrap font-mono text-xs font-semibold text-ink">
                    {{ $laporan->no_pengajuan ?? 'Belum diterbitkan' }}
                </td>
                <td class="font-semibold text-ink">{{ $laporan->labelNamaAlmarhum() }}</td>
                <td class="text-muted">{{ $laporan->labelTanggalPengajuan() }}</td>
                <td>
                    @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                    @if ($alasanPerbaikan !== null)
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">
                            <span class="font-semibold">Alasan perbaikan:</span> {{ $alasanPerbaikan }}
                        </p>
                    @endif
                </td>
                <td class="text-center">
                    @include('dashboard.aksi_laporan', ['laporan' => $laporan])
                </td>
            </tr>
        @endforeach
    </x-ui.table>

    {{-- Tampilan Mobile (Card List) --}}
    <div class="space-y-3 md:hidden">
        @foreach ($daftar as $laporan)
            @php
                $perluAlasan = in_array($laporan->status_pengajuan, [
                    \App\Models\LaporanKematian::STATUS_DIKEMBALIKAN,
                    \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
                    \App\Models\LaporanKematian::STATUS_MENUNGGU_PERBAIKAN,
                ], true);
                $alasanPerbaikan = $perluAlasan ? $laporan->labelAlasanPerbaikan() : null;
            @endphp
            <x-ui.card padding="p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-mono text-xs font-semibold text-ink-soft tabular-nums">
                        {{ $laporan->no_pengajuan ?? 'Belum diterbitkan' }}
                    </span>
                    @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                </div>

                <p class="mt-2 font-semibold text-ink">{{ $laporan->labelNamaAlmarhum() }}</p>
                <p class="mt-1 text-xs text-muted">
                    Tanggal Pengajuan: {{ $laporan->labelTanggalPengajuan() }}
                </p>

                @if ($alasanPerbaikan !== null)
                    <p class="mt-2 text-xs font-medium text-[#b91c1c]">
                        <span class="font-semibold">Alasan perbaikan:</span> {{ $alasanPerbaikan }}
                    </p>
                @endif

                <div class="mt-3">
                    @include('dashboard.aksi_laporan', ['laporan' => $laporan])
                </div>
            </x-ui.card>
        @endforeach
    </div>
@endif
