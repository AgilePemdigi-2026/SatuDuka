@extends('tata_letak.utama')

@section('judul', 'Dashboard Verifikator')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <x-ui.page-header title="Dashboard Verifikator"
        :subtitle="'Selamat datang, '.$pengguna->nama_lengkap.'. Anda masuk sebagai '.$pengguna->labelPeran().'. Antrean verifikasi berkas pengajuan.'">
        <x-slot:aksi>
            <x-ui.button :href="route('verifikasi.daftar')">Buka Daftar Verifikasi</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.stat label="Menunggu Verifikasi" value="—" variant="warning" />
            <x-ui.stat label="Selesai" value="—" variant="success" />
        </div>
        <p class="mt-2 text-xs text-muted">Statistik mengikuti antrean pada halaman Verifikasi Berkas.</p>
    </section>

    <section class="mt-8 grid gap-4 sm:grid-cols-2">
        <x-ui.card>
            <h2 class="text-lg font-semibold text-ink">Verifikasi Berkas</h2>
            <p class="mt-1 text-sm text-muted">Periksa pengajuan yang menunggu verifikasi.</p>
            <x-ui.button :href="route('verifikasi.daftar')" size="sm" class="mt-4">
                Buka Daftar Verifikasi
            </x-ui.button>
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-lg font-semibold text-ink">Rekonsiliasi Anomali</h2>
            <p class="mt-1 text-sm text-muted">Segera hadir.</p>
        </x-ui.card>
    </section>
@endsection
