@extends('tata_letak.utama')

@section('judul', 'Dashboard Pejabat')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <x-ui.page-header title="Dashboard Pejabat Penanda Tangan"
        :subtitle="'Selamat datang, '.$pengguna->nama_lengkap.'. Anda masuk sebagai '.$pengguna->labelPeran().'. Antrean dokumen untuk ditandatangani elektronik.'" />

    <x-ui.card class="mt-8">
        <div class="flex items-start gap-3">
            <x-icon.shield-check class="h-6 w-6 shrink-0 text-brand" />
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-ink">Tanda Tangan Elektronik (TTE)</h2>
                <p class="mt-1 text-sm text-muted">Segera hadir.</p>
            </div>
        </div>
    </x-ui.card>
@endsection
