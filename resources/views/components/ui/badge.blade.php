@props(['variant' => 'neutral'])

@php
    $varian = [
        'warning' => 'border-[#f59e0b] bg-[#fef3c7] text-[#b45309]',
        'success' => 'border-[#22c55e] bg-[#dcfce7] text-[#15803d]',
        'danger' => 'border-[#ef4444] bg-[#fee2e2] text-[#b91c1c]',
        'info' => 'border-[#a5dcf3] bg-[#d9f1fb] text-[#1e3a5f]',
        'neutral' => 'border-line bg-[#f1f5f9] text-[#475569]',
        'purple' => 'border-[#d8b4fe] bg-[#f3e8ff] text-[#6b21a8]',
    ];

    $kelas = $varian[$variant] ?? $varian['neutral'];
@endphp

<span role="status" {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wide '.$kelas]) }}>{{ $slot }}</span>
