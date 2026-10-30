@php
    $varianBadge = match ($status) {
        \App\Models\LaporanKematian::STATUS_DRAF => 'neutral',
        \App\Models\LaporanKematian::STATUS_MENUNGGU_VERIFIKASI => 'info',
        \App\Models\LaporanKematian::STATUS_DALAM_REKONSILIASI => 'purple',
        \App\Models\LaporanKematian::STATUS_MENUNGGU_PERBAIKAN,
        \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
        \App\Models\LaporanKematian::STATUS_DITOLAK_ANOMALI_FATAL => 'danger',
        \App\Models\LaporanKematian::STATUS_DIKEMBALIKAN => 'warning',
        \App\Models\LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE => 'info',
        \App\Models\LaporanKematian::STATUS_SELESAI => 'success',
        default => 'neutral',
    };
@endphp

<x-ui.badge :variant="$varianBadge">{{ $teks ?? $status }}</x-ui.badge>
