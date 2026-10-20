@php
    $labelPeran = $pengguna->labelPeran();
@endphp

<details class="relative">
    <summary
        class="flex cursor-pointer list-none items-center gap-2 rounded-full py-1 pl-1 pr-2 hover:bg-gray-50 [&::-webkit-details-marker]:hidden"
        aria-label="Menu pengguna {{ $pengguna->nama_lengkap }}">
        @if ($pengguna->path_foto)
            <img src="{{ route('profil.foto.tampil') }}" alt="Foto profil {{ $pengguna->nama_lengkap }}"
                class="h-9 w-9 rounded-full border border-gray-200 object-cover" />
        @else
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-xs font-semibold text-white"
                aria-hidden="true">{{ $pengguna->inisial() }}</span>
        @endif

        <span class="hidden max-w-[10rem] truncate text-sm font-medium text-gray-700 sm:inline">
            {{ $pengguna->nama_lengkap }}
        </span>

        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="h-4 w-4 text-gray-500" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </summary>

    <div class="absolute right-0 z-30 mt-2 w-60 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
        <div class="border-b border-gray-100 px-4 py-3">
            <p class="truncate text-sm font-semibold text-slate-800">{{ $pengguna->nama_lengkap }}</p>
            <p class="text-xs text-gray-500">{{ $labelPeran }}</p>
        </div>

        <div class="p-2">
            <a href="{{ route('profil.tampilkan') }}"
                class="block rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Kelola Profil</a>

            <form method="POST" action="{{ route('keluar') }}">
                @csrf
                <button type="submit"
                    class="block w-full rounded-md px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</details>
