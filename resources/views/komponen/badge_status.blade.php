@php
    $kelasBadge = match ($status) {
        \App\Models\LaporanKematian::STATUS_DRAF => 'bg-gray-100 text-gray-700',
        \App\Models\LaporanKematian::STATUS_MENUNGGU_VERIFIKASI => 'bg-sky-100 text-sky-800',
        \App\Models\LaporanKematian::STATUS_DALAM_REKONSILIASI => 'bg-purple-100 text-purple-800',
        \App\Models\LaporanKematian::STATUS_MENUNGGU_PERBAIKAN,
        \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI => 'bg-rose-100 text-rose-800',
        \App\Models\LaporanKematian::STATUS_DIKEMBALIKAN => 'bg-orange-100 text-orange-800',
        \App\Models\LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE => 'bg-blue-100 text-blue-800',
        \App\Models\LaporanKematian::STATUS_SELESAI => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $kelasBadge }}">{{ $teks ?? $status }}</span>
