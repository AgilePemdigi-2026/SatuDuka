@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $dasar = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';

    $varian = [
        'primary' => 'bg-brand text-white hover:bg-brand-hover',
        'secondary' => 'border border-line-strong bg-surface text-ink-soft hover:bg-canvas',
        'ghost' => 'text-ink-soft hover:bg-canvas',
        'danger' => 'bg-[#b91c1c] text-white hover:bg-[#ef4444]',
    ];

    $ukuran = [
        'md' => 'h-12 px-5 text-sm',
        'sm' => 'h-8 px-4 text-xs uppercase tracking-wide',
    ];

    $kelas = $dasar.' '.($varian[$variant] ?? $varian['primary']).' '.($ukuran[$size] ?? $ukuran['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</button>
@endif
