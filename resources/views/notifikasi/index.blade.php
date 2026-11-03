@extends('tata_letak.utama')

@section('judul', 'Pusat Notifikasi')

@section('konten')
<div class="mx-auto max-w-4xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink">Notifikasi Pengajuan</h1>
            <p class="mt-1 text-sm text-muted">Pantau perubahan status, permintaan perbaikan, dan penerbitan dokumen resmi.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-700">
                {{ substr($pengguna->nama ?? 'P', 0, 1) }}
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">{{ $pengguna->nama }}</p>
                <p class="text-xs text-muted">Pemohon</p>
            </div>
        </div>
    </div>

    <!-- Pencarian & Action -->
    <form method="GET" action="{{ route('notifikasi.index') }}" class="flex gap-3">
        <div class="relative flex-1">
            <input type="text" name="cari" value="{{ $cari }}" 
                placeholder="Cari nomor pengajuan atau isi notifikasi" 
                class="w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand focus:outline-none">
        </div>
        <form method="POST" action="{{ route('notifikasi.tandai_semua') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary" class="shrink-0">
                Tandai Semua Dibaca
            </x-ui.button>
        </form>
    </form>

    <!-- Filter Tabs -->
    <div class="flex gap-2 border-b border-line pb-2">
        <a href="{{ route('notifikasi.index', ['tab' => 'semua']) }}" 
           class="px-4 py-1.5 rounded-full text-xs font-semibold {{ $tab === 'semua' ? 'bg-ink text-white' : 'border border-line text-ink hover:bg-slate-50' }}">
            Semua
        </a>
        <a href="{{ route('notifikasi.index', ['tab' => 'belum_dibaca']) }}" 
           class="px-4 py-1.5 rounded-full text-xs font-semibold {{ $tab === 'belum_dibaca' ? 'bg-ink text-white' : 'border border-line text-ink hover:bg-slate-50' }}">
            Belum Dibaca ({{ $jumlahBelumDibaca }})
        </a>
        <a href="{{ route('notifikasi.index', ['tab' => 'perlu_tindakan']) }}" 
           class="px-4 py-1.5 rounded-full text-xs font-semibold {{ $tab === 'perlu_tindakan' ? 'bg-ink text-white' : 'border border-line text-ink hover:bg-slate-50' }}">
            Perlu Tindakan
        </a>
        <a href="{{ route('notifikasi.index', ['tab' => 'selesai']) }}" 
           class="px-4 py-1.5 rounded-full text-xs font-semibold {{ $tab === 'selesai' ? 'bg-ink text-white' : 'border border-line text-ink hover:bg-slate-50' }}">
            Selesai
        </a>
    </div>

    <!-- Daftar Notifikasi -->
    <div class="space-y-3">
        @forelse($notifikasis as $item)
            <div class="flex items-center justify-between rounded-2xl border border-line bg-surface p-4 shadow-sm relative {{ !$item->sudah_dibaca ? 'border-l-4 border-l-brand' : '' }}">
                <div class="flex items-start gap-4">
                    <!-- Icon Berdasarkan Jenis -->
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-ink">
                        @if($item->jenis === 'validasi_awal')
                            <x-icon.check class="h-5 w-5" />
                        @elseif($item->jenis === 'perlu_diperbaiki')
                            <x-icon.exclamation-triangle class="h-5 w-5" />
                        @elseif($item->jenis === 'dokumen_terbit')
                            <x-icon.document-text class="h-5 w-5" />
                        @else
                            <x-icon.clock class="h-5 w-5" />
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-ink text-sm">{{ $item->judul }}</h3>
                            @if(!$item->sudah_dibaca)
                                <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700">BELUM DIBACA</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-xs text-muted">{{ $item->pesan }}</p>
                        <p class="mt-1 text-[11px] text-faint">
                            {{ $item->created_at->translatedFormat('j F Y, H.i') }} WIB 
                            @if($item->laporan) • {{ $item->laporan->no_pengajuan }} @endif
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi Kontekstual -->
                <div>
                    @if($item->jenis === 'perlu_diperbaiki')
                        <a href="{{ route('laporan.ubah', $item->laporan) }}" class="rounded-xl bg-ink px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                            Perbaiki Pengajuan
                        </a>
                    @elseif($item->jenis === 'dokumen_terbit')
                        <a href="{{ route('pengajuan.unduh', $item->laporan) }}" class="rounded-xl bg-ink px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                            Unduh Dokumen
                        </a>
                    @else
                        <a href="{{ route('pengajuan.detail', $item->laporan) }}" class="rounded-xl border border-line px-4 py-2 text-xs font-semibold text-ink hover:bg-slate-50">
                            Lihat Status
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-muted">
                Tidak ada notifikasi.
            </div>
        @forelse
    </div>
</div>
@endsection