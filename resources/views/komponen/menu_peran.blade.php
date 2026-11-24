{{--
    Navigasi utama sesuai peran pengguna.

    Tautan tampil mendatar di header (tampilan "header"). Di layar kecil tautan
    disembunyikan agar header tidak meluap, lalu ditampilkan di dalam menu
    avatar dengan tampilan "dropdown" supaya tetap terjangkau tanpa bilah menu
    samping.
--}}
@php
    $tampilanMenu = $tampilan ?? 'header';
    $penggunaMenu = auth()->user();

    $menuPeran = [];

    if ($penggunaMenu?->adalahPemohon()) {
        $menuPeran = [
            ['rute' => 'dashboard', 'pola' => ['dashboard'], 'label' => 'Dashboard'],
            ['rute' => 'pengajuan.status', 'pola' => ['pengajuan.*'], 'label' => 'Status Pengajuan'],
        ];
    } elseif ($penggunaMenu?->adalahVerifikator()) {
        $menuPeran = [
            ['rute' => 'verifikasi.index', 'pola' => ['verifikasi.*'], 'label' => 'Verifikasi'],
            ['rute' => 'rekonsiliasi.index', 'pola' => ['rekonsiliasi.*'], 'label' => 'Rekonsiliasi'],
        ];
    } elseif ($penggunaMenu?->adalahPejabat()) {
        $menuPeran = [
            ['rute' => 'dashboard', 'pola' => ['dashboard'], 'label' => 'Dashboard'],
            ['rute' => 'tanda_tangan.index', 'pola' => ['tanda_tangan.*'], 'label' => 'Tanda Tangan'],
        ];
    }
@endphp

@if ($menuPeran !== [])
    @if ($tampilanMenu === 'dropdown')
        <div class="border-b border-line pb-1 md:hidden">
            @foreach ($menuPeran as $menu)
                @php($aktif = request()->routeIs($menu['pola']))
                <a href="{{ route($menu['rute']) }}"
                    class="block rounded-lg px-3 py-2 text-sm {{ $aktif ? 'bg-brand-soft font-semibold text-brand' : 'font-medium text-ink-soft hover:bg-canvas' }}"
                    @if ($aktif) aria-current="page" @endif>{{ $menu['label'] }}</a>
            @endforeach
        </div>
    @else
        <nav class="hidden items-center gap-1 md:flex" aria-label="Menu utama">
            @foreach ($menuPeran as $menu)
                @php($aktif = request()->routeIs($menu['pola']))
                <a href="{{ route($menu['rute']) }}"
                    class="rounded-full px-3 py-1.5 text-sm font-semibold transition-colors {{ $aktif ? 'bg-brand-soft text-brand' : 'text-ink-soft hover:bg-canvas hover:text-brand' }}"
                    @if ($aktif) aria-current="page" @endif>{{ $menu['label'] }}</a>
            @endforeach
        </nav>
    @endif
@endif
