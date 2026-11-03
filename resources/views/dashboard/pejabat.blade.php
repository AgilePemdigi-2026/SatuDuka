@extends('tata_letak.utama')

@section('judul', 'Dashboard Pejabat')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <x-ui.page-header title="Dashboard Pejabat Penanda Tangan"
        :subtitle="'Selamat datang, '.$pengguna->nama_lengkap.'. Anda masuk sebagai '.$pengguna->labelPeran().'. Antrean dokumen untuk ditandatangani elektronik.'">
        <x-slot:aksi>
            <x-ui.button :href="route('tanda_tangan.index')">Buka Tanda Tangan Dokumen</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <x-ui.card class="mt-8">
        <div class="flex items-start gap-3">
            <x-icon.shield-check class="h-6 w-6 shrink-0 text-brand" />
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-ink">Tanda Tangan Elektronik (TTE)</h2>
                <p class="mt-1 text-sm text-muted">
                    Dokumen yang sudah disetujui verifikator menunggu tanda tangan elektronik Anda.
                </p>
                <div class="mt-4">
                    <x-ui.button :href="route('tanda_tangan.index')" size="sm">Lihat Antrean Dokumen</x-ui.button>
                </div>
            </div>
        </div>
    </x-ui.card>
@endsection
