@props(['icon' => null])

<div
    {{ $attributes->merge(['class' => 'flex gap-3 rounded-2xl border border-[#bfdbfe] bg-brand-soft p-4 text-sm text-ink-soft']) }}>
    @if ($icon)
        <x-dynamic-component :component="'icon.'.$icon" class="h-5 w-5 shrink-0 text-brand" />
    @endif

    <div class="min-w-0">
        {{ $slot }}
    </div>
</div>
