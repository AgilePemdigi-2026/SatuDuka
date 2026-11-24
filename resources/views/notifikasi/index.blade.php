@extends('tata_letak.utama')

@section('judul', 'Pusat Notifikasi')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('sidebar')
    @include('komponen.sidebar_pemohon')
@endsection

@section('konten')
    <div class="space-y-6">
        <x-ui.page-header title="Notifikasi Pengajuan"
            subtitle="Pantau perubahan status, permintaan perbaikan, dan penerbitan dokumen resmi." />

        <!-- Pencarian & Aksi -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('notifikasi.index') }}" class="flex flex-1 flex-wrap items-end gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="min-w-0 w-full max-w-md">
                    <x-ui.input name="cari" type="search" :value="$cari"
                        placeholder="Cari nomor pengajuan atau isi notifikasi" />
                </div>
                <x-ui.button type="submit" variant="secondary">Cari</x-ui.button>
            </form>

            <form method="POST" action="{{ route('notifikasi.tandai_semua') }}">
                @csrf
                <x-ui.button type="submit" variant="secondary">Tandai Semua Dibaca</x-ui.button>
            </form>
        </div>

        <!-- Filter Tabs -->
        <div class="flex flex-wrap gap-2 border-b border-line pb-2">
            @foreach (['semua' => 'Semua', 'belum_dibaca' => 'Belum Dibaca', 'perlu_tindakan' => 'Perlu Tindakan', 'selesai' => 'Selesai'] as $nilai => $label)
                <a href="{{ route('notifikasi.index', array_filter(['tab' => $nilai, 'cari' => $cari])) }}"
                    class="rounded-full px-4 py-1.5 text-xs font-semibold transition-colors
                        {{ $tab === $nilai
                            ? 'bg-brand text-white'
                            : 'border border-line text-ink-soft hover:bg-canvas' }}">
                    @if ($nilai === 'belum_dibaca')
                        {{ $label }} ({{ $jumlahBelumDibaca }})
                    @else
                        {{ $label }}
                    @endif
                </a>
            @endforeach
        </div>

        <!-- Daftar Notifikasi -->
        <div class="space-y-3">
            @forelse ($notifikasis as $item)
                <div
                    class="relative flex flex-wrap items-center justify-between gap-3 rounded-2xl border bg-surface p-4 shadow-[0_1px_2px_rgb(15_23_42_/_0.04)] {{ !$item->sudah_dibaca ? 'border-l-4 border-l-brand' : 'border-line' }}">
                    <div class="flex items-start gap-4">
                        <!-- Ikon Berdasarkan Jenis -->
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-canvas text-ink">
                            @if ($item->jenis === 'validasi_awal')
                                <x-icon.shield-check class="h-5 w-5" />
                            @elseif ($item->jenis === 'perlu_diperbaiki')
                                <x-icon.cog class="h-5 w-5" />
                            @elseif ($item->jenis === 'dokumen_terbit')
                                <x-icon.arrow-down-tray class="h-5 w-5" />
                            @else
                                <x-icon.check class="h-5 w-5" />
                            @endif
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-bold text-ink">{{ $item->judul }}</h3>
                                @if (!$item->sudah_dibaca)
                                    <x-ui.badge variant="info">Belum dibaca</x-ui.badge>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-muted">{{ $item->pesan }}</p>
                            <p class="mt-1 text-[11px] text-faint">
                                {{ $item->dibuat_pada->translatedFormat('j F Y, H.i') }} WIB
                                @if ($item->laporan)
                                    • {{ $item->laporan->no_pengajuan }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Aksi Kontekstual -->
                    <div class="flex items-center gap-2">
                        @if (!$item->sudah_dibaca)
                            <form method="POST" action="{{ route('notifikasi.tandai', $item) }}">
                                @csrf
                                <x-ui.button type="submit" variant="ghost" size="sm">Tandai dibaca</x-ui.button>
                            </form>
                        @endif

                        @if ($item->jenis === 'perlu_diperbaiki' && $item->laporan)
                            <x-ui.button :href="route('laporan.ubah', $item->laporan)" size="sm">Perbaiki Pengajuan</x-ui.button>
                        @elseif ($item->jenis === 'dokumen_terbit' && $item->laporan)
                            <x-ui.button :href="route('pengajuan.unduh', $item->laporan)" size="sm">Unduh Dokumen</x-ui.button>
                        @elseif ($item->laporan)
                            <x-ui.button :href="route('pengajuan.detail', $item->laporan)" variant="secondary" size="sm">
                                Lihat Status
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            @empty
                <x-ui.empty judul="Tidak ada notifikasi">
                    Pemberitahuan tentang pengajuan Anda akan muncul di sini.
                </x-ui.empty>
            @endforelse
        </div>

        <div class="text-center">{{ $notifikasis->links() }}</div>
    </div>
@endsection
