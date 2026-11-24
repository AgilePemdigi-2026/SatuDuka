@props([
    'label',
    'value',
    'catatan' => null,
    'variant' => 'neutral',
])

@php
    // Warna aksen kartu statistik. Nilainya sama dengan aksen KPI yang sudah
    // dipakai dashboard: navy untuk netral, kuning menunggu, hijau selesai,
    // merah untuk yang perlu ditindaklanjuti.
    $varian = [
        'neutral' => ['bar' => 'bg-brand', 'teks' => 'text-brand'],
        'brand' => ['bar' => 'bg-brand', 'teks' => 'text-brand'],
        'info' => ['bar' => 'bg-brand', 'teks' => 'text-brand'],
        'warning' => ['bar' => 'bg-[#d99a24]', 'teks' => 'text-[#d99a24]'],
        'success' => ['bar' => 'bg-[#31936c]', 'teks' => 'text-[#31936c]'],
        'danger' => ['bar' => 'bg-[#d65f52]', 'teks' => 'text-[#d65f52]'],
        'purple' => ['bar' => 'bg-[#6b21a8]', 'teks' => 'text-[#6b21a8]'],
    ];

    $warna = $varian[$variant] ?? $varian['neutral'];
@endphp

<div
    {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-line bg-surface px-5 pb-5 pt-7 text-center shadow-[0_1px_2px_rgb(15_23_42_/_0.04)]']) }}>
    <span class="absolute inset-x-0 top-0 h-1 {{ $warna['bar'] }}" aria-hidden="true"></span>

    <p class="text-[11.5px] font-semibold uppercase tracking-[0.07em] {{ $warna['teks'] }}">{{ $label }}</p>

    <p class="mt-3 text-4xl font-extrabold leading-none tracking-tight tabular-nums {{ $warna['teks'] }}">
        {{ $value }}
    </p>

    @if ($catatan)
        <p class="mt-2 text-xs leading-snug text-muted">{{ $catatan }}</p>
    @endif
</div>
