@props(['steps' => []])

@php
    $adaYangBelumSelesai = false;
@endphp

<ol class="space-y-0">
    @foreach ($steps as $indeks => $tahap)
        @php
            $selesai = (bool) ($tahap['selesai'] ?? false);
            $sedang = ! $selesai && ! $adaYangBelumSelesai;
            $adaYangBelumSelesai = $adaYangBelumSelesai || ! $selesai;
            $waktu = $tahap['waktu'] ?? $tahap['tanggal'] ?? null;
        @endphp

        <li class="relative flex gap-3 pb-8 last:pb-0">
            @if (! $loop->last)
                <span class="absolute left-[13px] top-7 h-[calc(100%-1.75rem)] w-0.5 bg-line-strong"
                    aria-hidden="true"></span>
            @endif

            <span
                class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2
                    {{ $selesai
                        ? 'border-brand bg-brand text-white'
                        : ($sedang
                            ? 'border-brand bg-brand-soft text-brand'
                            : 'border-line-strong bg-surface text-muted') }}">
                @if ($selesai)
                    <x-icon.check class="h-4 w-4" />
                @elseif ($sedang)
                    <span class="h-2 w-2 rounded-full bg-brand" aria-hidden="true"></span>
                @else
                    <span class="text-xs font-bold">{{ $indeks + 1 }}</span>
                @endif
            </span>

            <div class="min-w-0 pt-0.5">
                <p class="text-sm font-semibold {{ $selesai || $sedang ? 'text-ink' : 'text-muted' }}">
                    {{ $tahap['judul'] }}
                </p>

                @if ($waktu instanceof \DateTimeInterface)
                    <p class="mt-1 text-xs text-faint">
                        {{ $waktu->translatedFormat('j F Y, H:i') }} WIB
                    </p>
                @elseif ($waktu)
                    <p class="mt-1 text-xs text-faint">{{ $waktu }}</p>
                @elseif ($sedang)
                    <p class="mt-1 text-xs font-medium text-brand">Sedang diproses</p>
                @elseif (! $selesai)
                    <p class="mt-1 text-xs text-faint">Menunggu</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
