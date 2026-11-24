@php
    $berhasil = $analisis['berhasil'] ?? false;
    $tertunda = $analisis['tertunda'] ?? false;
    $perbandingan = $analisis['perbandingan'] ?? null;
    $pesan = $analisis['pesan'] ?? null;
    $keyakinanPersen = (int) round(((float) ($perbandingan['skor'] ?? 0)) * 100);
    $varianKeyakinan = $keyakinanPersen >= 80 ? 'success' : ($keyakinanPersen >= 60 ? 'warning' : 'danger');
    $varianStatus = fn (string $status) => match ($status) {
        'cocok' => 'success',
        'beda' => 'danger',
        default => 'neutral',
    };
@endphp

<x-ui.notice class="mt-4 [&>div]:flex-1" icon="shield-check">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <p class="text-sm font-semibold text-ink">Hasil bantuan AI, hanya saran</p>
            <p class="mt-1 text-xs text-ink-soft">
                AI tidak menyetujui, menolak, atau mengubah status pengajuan. Periksa kembali data dan dokumen asli.
            </p>
        </div>

        @if ($berhasil && $perbandingan !== null)
            <x-ui.badge :variant="$varianKeyakinan">Keyakinan {{ $keyakinanPersen }}%</x-ui.badge>
        @endif
    </div>

    @if ($tertunda)
        <x-ui.alert variant="warning" class="mt-3">
            <p class="font-semibold">Analisis AI tertunda, coba lagi</p>
            <p class="mt-1 font-normal">
                {{ $pesan ?? 'Layanan AI sedang sibuk (batas permintaan). Analisis belum ditandai gagal; coba lagi beberapa saat lagi.' }}
            </p>
        </x-ui.alert>
    @elseif (! $berhasil)
        <x-ui.alert variant="danger" class="mt-3">
            Analisis AI tidak tersedia, lanjutkan secara manual
        </x-ui.alert>
    @elseif ($pesan)
        <p class="mt-3 rounded-xl border border-line bg-surface p-3 text-sm text-ink-soft">{{ $pesan }}</p>
    @endif

    @if (! empty($analisis['berkas']))
        <div class="mt-4 rounded-xl border border-line bg-surface p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Status Pemeriksaan Berkas</p>
            <ul class="mt-2 space-y-1 text-xs text-ink-soft">
                @foreach ($analisis['berkas'] as $statusBerkas)
                    <li class="flex items-start justify-between gap-2">
                        <span class="min-w-0 break-words">{{ $statusBerkas['nama_file'] }}</span>
                        <span class="text-right">
                            @if (($statusBerkas['status'] ?? '') === 'sudah_diperiksa')
                                <span class="font-semibold text-ink">sudah diperiksa</span>
                            @elseif (($statusBerkas['status'] ?? '') === 'belum_diperiksa')
                                <span class="font-semibold text-ink">belum diperiksa</span>
                            @else
                                <span class="font-semibold text-ink">dilewati (tidak relevan)</span>
                            @endif
                            <span class="block text-muted">{{ $statusBerkas['keterangan'] ?? '' }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($berhasil && $perbandingan !== null)
        <div class="mt-4 overflow-x-auto rounded-xl border border-line bg-surface">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-canvas">
                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                        <th class="px-3 py-2 font-semibold">Kolom</th>
                        <th class="px-3 py-2 font-semibold">Isian Pemohon</th>
                        <th class="px-3 py-2 font-semibold">Hasil Ekstraksi AI</th>
                        <th class="px-3 py-2 font-semibold">Data SIAK</th>
                        <th class="px-3 py-2 font-semibold">Status</th>
                        <th class="px-3 py-2 font-semibold">Keyakinan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($perbandingan['kolom'] as $kolom)
                        <tr>
                            <td class="px-3 py-2 font-semibold text-ink-soft">{{ $kolom['label'] }}</td>
                            <td class="px-3 py-2 text-ink-soft">{{ $kolom['nilai_pemohon'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-ink-soft">{{ $kolom['nilai_ekstraksi'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-ink-soft">{{ $kolom['nilai_siak'] ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="$varianStatus($kolom['status'])">
                                    {{ $kolom['status'] }}
                                </x-ui.badge>
                            </td>
                            <td class="px-3 py-2 text-ink-soft">
                                {{ number_format(((float) $kolom['keyakinan']) * 100, 0) }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($perbandingan['ada_beda'])
            <x-ui.alert variant="danger" class="mt-3">
                <p class="font-semibold">Ada kolom yang perlu diperiksa</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($perbandingan['kolom'] as $kolom)
                        @if ($kolom['status'] === 'beda')
                            <li>
                                {{ $kolom['label'] }}:
                                @foreach ($kolom['catatan'] as $catatan)
                                    {{ $catatan }}
                                @endforeach
                            </li>
                        @endif
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @if (! empty($analisis['semua_dokumen']) && count($analisis['semua_dokumen']) > 1)
            <div class="mt-4 rounded-xl border border-line bg-surface p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Pertimbangan Semua Dokumen Pendukung</p>
                <ul class="mt-2 space-y-1 text-xs text-ink-soft">
                    @foreach ($analisis['semua_dokumen'] as $dok)
                        <li class="flex items-center justify-between gap-2">
                            <span>{{ $dok['nama_file'] }}</span>
                            <span>
                                @if ($dok['berhasil'])
                                    Keyakinan {{ (int) round(((float) ($dok['skor'] ?? 0)) * 100) }}%
                                    @if (($dok['jumlah_beda'] ?? 0) > 0)
                                        &middot; {{ $dok['jumlah_beda'] }} kolom berbeda
                                    @endif
                                @else
                                    Tidak dapat dianalisis
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="mt-2 text-xs text-muted">
            Model: {{ $analisis['model'] ?? '-' }} &middot; Keyakinan ekstraksi: {{ $keyakinanPersen }}%
        </p>
    @endif
</x-ui.notice>
