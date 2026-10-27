@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id') ?? $name;
    $punyaError = $errors->has($name);
    $nilaiTerpilih = old($name, $value);
    $daftarOpsi = is_array($options) ? $options : [];
    $daftarList = array_is_list($daftarOpsi);
    $kelas = 'block h-[46px] w-full appearance-none rounded-xl border bg-surface px-3.5 pr-10 text-sm text-ink outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/30 '
        .($punyaError ? 'border-[#ef4444]' : 'border-line-strong');

    $atribut = [
        'id' => $id,
        'name' => $name,
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

<div class="relative">
    <select {{ $attributes->merge($atribut) }}>
        @if ($placeholder)
            <option value="" @selected((string) $nilaiTerpilih === '')>{{ $placeholder }}</option>
        @endif

        @foreach ($daftarOpsi as $nilaiOpsi => $labelOpsi)
            @php
                $nilaiOpsi = $daftarList ? $labelOpsi : $nilaiOpsi;
            @endphp
            <option value="{{ $nilaiOpsi }}" @selected((string) $nilaiTerpilih === (string) $nilaiOpsi)>
                {{ $labelOpsi }}
            </option>
        @endforeach
    </select>

    <x-icon.chevron-down class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" />
</div>

@if ($hint)
    <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
@endif

@error($name)
    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
@enderror
