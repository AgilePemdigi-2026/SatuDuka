@props([
    'steps' => [],
    'current' => 1,
    'tautkan' => false,
])

@php
    $daftarLangkah = collect($steps)->values();
    $jumlah = $daftarLangkah->count();
    $langkahAktif = max(1, min($jumlah > 0 ? $jumlah : 1, (int) $current));
    $persen = $jumlah > 0 ? (int) round(($langkahAktif / $jumlah) * 100) : 0;
    $labelAktif = $jumlah > 0
        ? (is_array($daftarLangkah[$langkahAktif - 1])
            ? ($daftarLangkah[$langkahAktif - 1]['judul'] ?? $daftarLangkah[$langkahAktif - 1]['label'] ?? '')
            : $daftarLangkah[$langkahAktif - 1])
        : '';
@endphp

<nav aria-label="Langkah formulir" {{ $attributes }}>
    <div class="hidden sm:block">
        <ol class="grid items-start"
            style="grid-template-columns: repeat({{ max(1, $jumlah) }}, minmax(0, 1fr));">
            @foreach ($daftarLangkah as $indeks => $langkah)
                @php
                    $nomor = $indeks + 1;
                    $aktif = $nomor === $langkahAktif;
                    $selesai = is_array($langkah)
                        ? (bool) ($langkah['selesai'] ?? ($nomor < $langkahAktif))
                        : $nomor < $langkahAktif;
                    $label = is_array($langkah)
                        ? ($langkah['judul'] ?? $langkah['label'] ?? 'Langkah '.$nomor)
                        : $langkah;
                @endphp

                <li class="relative flex min-w-0 flex-col items-center text-center">
                    @if (! $loop->last)
                        <span class="absolute left-1/2 top-3.5 h-0.5 w-full
                            {{ $selesai ? 'bg-brand' : 'bg-line-strong' }}"
                            aria-hidden="true"></span>
                    @endif

                    @if ($selesai && $tautkan)
                        <a href="?langkah={{ $nomor }}"
                            class="relative z-10 flex h-7 w-7 items-center justify-center rounded-full bg-brand text-white transition hover:bg-brand-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30 focus-visible:ring-offset-2">
                            <x-icon.check class="h-4 w-4" />
                        </a>
                    @else
                        <span @if ($aktif) aria-current="step" @endif
                            class="relative z-10 flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold
                                {{ $aktif
                                    ? 'bg-brand text-white'
                                    : ($selesai
                                        ? 'bg-brand text-white'
                                        : 'border-2 border-line-strong bg-surface text-muted') }}">
                            @if ($selesai)
                                <x-icon.check class="h-4 w-4" />
                            @else
                                {{ $nomor }}
                            @endif
                        </span>
                    @endif

                    <span class="mt-2 min-w-0 px-1 text-xs font-semibold leading-tight
                        {{ $aktif ? 'text-ink' : ($selesai ? 'text-ink-soft' : 'text-muted') }}">
                        {{ $label }}
                    </span>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="sm:hidden">
        <div class="flex items-center justify-between gap-3">
            <span class="text-xs font-semibold text-ink-soft">
                Langkah {{ $langkahAktif }} dari {{ $jumlah }}
            </span>
            <span class="truncate text-xs font-semibold text-ink">{{ $labelAktif }}</span>
        </div>

        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-line" role="progressbar"
            aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $persen }}"
            aria-label="Kemajuan pengisian formulir">
            <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $persen }}%"></div>
        </div>
    </div>
</nav>
