@props([
    'name',
    'label' => null,
    'placeholder' => null,
    'value' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id') ?? $name;
    $punyaError = $errors->has($name);
    $kelas = 'block h-[46px] w-full rounded-xl border bg-surface px-3.5 pr-11 text-sm text-ink outline-none transition placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30 '
        .($punyaError ? 'border-[#ef4444]' : 'border-line-strong');

    $atribut = [
        'id' => $id,
        'name' => $name,
        'type' => 'password',
        'placeholder' => $placeholder,
        'value' => $value,
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

<div class="group relative">
    <input {{ $attributes->merge($atribut) }} />

    <button type="button" data-tampilkan-sandi="{{ $id }}" aria-pressed="false"
        aria-label="Tampilkan kata sandi"
        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-muted transition hover:text-ink-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30">
        {{-- Kata sandi tersembunyi: ikon mata dicoret. Terlihat: ikon mata. --}}
        <x-icon.eye-slash class="h-5 w-5 group-has-[input[type=text]]:hidden" />
        <x-icon.eye class="hidden h-5 w-5 group-has-[input[type=text]]:block" />
    </button>
</div>

@if ($hint)
    <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
@endif

@error($name)
    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
@enderror
