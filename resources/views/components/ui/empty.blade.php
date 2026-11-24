@props([
    'judul' => null,
    'ikon' => null,
])

{{-- Keadaan kosong yang dipakai bersama oleh tabel, daftar, dan antrean. --}}
<div
    {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-line-strong bg-surface px-6 py-10 text-center']) }}>
    @if ($ikon)
        <span
            class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-canvas text-lg font-bold text-muted"
            aria-hidden="true">{{ $ikon }}</span>
    @endif

    @if ($judul)
        <p class="mt-3 text-sm font-semibold text-ink">{{ $judul }}</p>
    @endif

    <p class="mt-1 text-xs text-muted">{{ $slot }}</p>
</div>
