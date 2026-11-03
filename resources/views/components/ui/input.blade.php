@props([
    'name',
    'label' => null,
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id') ?? $name;
    $punyaError = $errors->has($name);
    $kelas = 'block h-[46px] w-full rounded-xl border bg-surface px-3.5 text-sm text-ink outline-none transition placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30 '
        .($punyaError ? 'border-[#ef4444]' : 'border-line-strong');

    $atribut = [
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'placeholder' => $placeholder,
        'value' => old($name, $value),
        'class' => $kelas,
        'aria-invalid' => $punyaError ? 'true' : 'false',
    ];

    if ($required) {
        $atribut['required'] = true;
    }
@endphp

@if ($label)
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-semibold text-ink-soft">
        {{ $label }}
        @if ($required)
            <span class="text-[#ef4444]" aria-hidden="true">*</span>
        @endif
    </label>
@endif

<input {{ $attributes->merge($atribut) }} />

@if ($hint)
    <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
@endif

@error($name)
    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
@enderror
