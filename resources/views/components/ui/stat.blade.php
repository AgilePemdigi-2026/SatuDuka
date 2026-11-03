@props([
    'label',
    'value',
    'variant' => 'neutral',
])

@php
    $varian = [
        'warning' => ['bg' => 'bg-[#fef3c7]', 'border' => 'border-[#f59e0b]', 'text' => 'text-[#b45309]'],
        'success' => ['bg' => 'bg-[#dcfce7]', 'border' => 'border-[#22c55e]', 'text' => 'text-[#15803d]'],
        'danger' => ['bg' => 'bg-[#fee2e2]', 'border' => 'border-[#ef4444]', 'text' => 'text-[#b91c1c]'],
        'info' => ['bg' => 'bg-[#d9f1fb]', 'border' => 'border-[#a5dcf3]', 'text' => 'text-[#1e3a5f]'],
        'neutral' => ['bg' => 'bg-[#f1f5f9]', 'border' => 'border-line', 'text' => 'text-[#475569]'],
        'purple' => ['bg' => 'bg-[#f3e8ff]', 'border' => 'border-[#d8b4fe]', 'text' => 'text-[#6b21a8]'],
    ];

    $warna = $varian[$variant] ?? $varian['neutral'];
@endphp

<div
    {{ $attributes->merge(['class' => 'rounded-2xl border p-5 text-center '.$warna['bg'].' '.$warna['border']]) }}>
    <p class="text-sm font-semibold text-ink-soft">{{ $label }}</p>
    <span class="mt-1 block text-5xl font-extrabold leading-none tracking-tight tabular-nums {{ $warna['text'] }}">{{ $value }}</span>
</div>
