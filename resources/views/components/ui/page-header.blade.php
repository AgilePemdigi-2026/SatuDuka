@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        <h1 class="text-3xl font-bold tracking-tight text-ink">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 max-w-2xl text-sm text-muted sm:text-base">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($aksi)
        <div class="flex flex-wrap items-center gap-2">
            {{ $aksi }}
        </div>
    @endisset
</div>
