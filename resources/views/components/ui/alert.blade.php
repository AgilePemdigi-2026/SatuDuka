@props(['variant' => 'info'])

@php
    $varian = [
        'success' => 'border-[#22c55e] bg-[#dcfce7] text-[#15803d]',
        'info' => 'border-[#a5dcf3] bg-[#d9f1fb] text-[#1e3a5f]',
        'danger' => 'border-[#ef4444] bg-[#fee2e2] text-[#b91c1c]',
        'warning' => 'border-[#f59e0b] bg-[#fef3c7] text-[#b45309]',
    ];

    $kelas = $varian[$variant] ?? $varian['info'];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm font-medium '.$kelas]) }}>
    {{ $slot }}
</div>
