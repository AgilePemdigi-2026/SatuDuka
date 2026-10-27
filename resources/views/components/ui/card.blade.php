@props([
    'title' => null,
    'padding' => true,
])

@php
    $kelasPadding = $padding === false ? '' : ($padding === true ? 'p-5 sm:p-8' : $padding);
@endphp

<div
    {{ $attributes->merge(['class' => trim('rounded-2xl border border-line bg-surface shadow-[0_1px_2px_rgb(15_23_42_/_0.04)] '.$kelasPadding)]) }}>
    @if ($title)
        <h2 class="mb-5 text-lg font-semibold text-ink">{{ $title }}</h2>
    @endif

    {{ $slot }}
</div>
