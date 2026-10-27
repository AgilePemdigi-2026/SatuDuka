@php
    $labelPeran = $pengguna->labelPeran();
@endphp

<details class="relative">
    <summary
        class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-line bg-surface py-1 pl-1 pr-2 transition-colors hover:bg-canvas focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/30 [&::-webkit-details-marker]:hidden"
        aria-label="Menu pengguna {{ $pengguna->nama_lengkap }}">
        @if ($pengguna->path_foto)
            <img src="{{ route('profil.foto.tampil') }}" alt="Foto profil {{ $pengguna->nama_lengkap }}"
                class="h-9 w-9 rounded-full border border-line object-cover" />
        @else
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-line text-xs font-bold text-ink"
                aria-hidden="true">{{ $pengguna->inisial() }}</span>
        @endif

        <span class="hidden min-w-0 text-left sm:block">
            <span class="block max-w-[10rem] truncate text-sm font-bold leading-tight text-ink">
                {{ $pengguna->nama_lengkap }}
            </span>
            <span class="block text-xs leading-tight text-faint">{{ $labelPeran }}</span>
        </span>

        <x-icon.chevron-down class="h-4 w-4 shrink-0 text-muted" />
    </summary>

    <div
        class="absolute right-0 z-30 mt-2 w-60 overflow-hidden rounded-xl border border-line bg-surface shadow-[0_8px_24px_rgb(15_23_42_/_0.08)]">
        <div class="border-b border-line px-4 py-3">
            <p class="truncate text-sm font-bold text-ink">{{ $pengguna->nama_lengkap }}</p>
            <p class="text-xs text-faint">{{ $labelPeran }}</p>
        </div>

        <div class="p-2">
            <a href="{{ route('profil.tampilkan') }}"
                class="block rounded-lg px-3 py-2 text-sm font-medium text-ink-soft hover:bg-canvas">Kelola Profil</a>

            <form method="POST" action="{{ route('keluar') }}">
                @csrf
                <button type="submit"
                    class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-ink-soft hover:bg-canvas">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</details>
