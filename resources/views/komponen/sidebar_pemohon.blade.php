{{--
    Navigasi samping untuk peran pemohon.

    Dipakai lewat @section('sidebar') pada tata letak utama agar semua halaman
    pemohon memakai menu yang sama. Di layar kecil menu tampil sebagai baris
    mendatar di bawah header; mulai ukuran lg menjadi kolom tetap di kiri.
--}}
@php
    $menuPemohon = [
        ['rute' => 'dashboard', 'pola' => 'dashboard', 'label' => 'Dashboard'],
        ['rute' => 'laporan.baru', 'pola' => 'laporan.*', 'label' => 'Ajukan Laporan Baru'],
        ['rute' => 'pengajuan.status', 'pola' => 'pengajuan.*', 'label' => 'Status Pengajuan'],
    ];
@endphp

<div class="flex flex-col justify-between gap-4 px-4 py-3 lg:h-full lg:px-5 lg:py-7">
    <div>
        <div class="hidden px-1 pb-7 text-center lg:block">
            <p class="text-2xl font-extrabold tracking-tight text-brand">{{ config('app.name', 'SKD') }}</p>
            <p class="mt-1 text-[11px] text-muted">Sistem Kependudukan Digital</p>
        </div>

        <p class="hidden px-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-faint lg:block">Menu Utama</p>

        <nav class="flex flex-wrap gap-2 lg:mt-3 lg:flex-col" aria-label="Menu utama">
            @foreach ($menuPemohon as $menu)
                @php($aktif = request()->routeIs($menu['pola']))
                <a href="{{ route($menu['rute']) }}"
                    class="flex shrink-0 items-center gap-3 whitespace-nowrap rounded-xl border px-3.5 py-2.5 text-[13px] font-medium transition-colors lg:px-4 lg:py-3
                        {{ $aktif
                            ? 'border-brand bg-brand font-semibold text-white shadow-[0_8px_16px_-8px_rgb(30_58_95_/_0.6)]'
                            : 'border-line bg-surface text-ink-soft hover:border-brand hover:bg-brand-soft hover:text-brand' }}"
                    @if ($aktif) aria-current="page" @endif>
                    <span class="h-2 w-2 shrink-0 rounded-full bg-current {{ $aktif ? 'opacity-100' : 'opacity-50' }}"
                        aria-hidden="true"></span>
                    {{ $menu['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    <p class="hidden border-t border-line pt-4 text-center text-[11px] text-faint lg:block">
        &copy; {{ date('Y') }} {{ config('app.name', 'SKD') }} Indonesia
    </p>
</div>
