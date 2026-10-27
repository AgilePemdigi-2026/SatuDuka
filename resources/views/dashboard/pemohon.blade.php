@extends('tata_letak.utama')

@section('judul', 'Dashboard Pemohon')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <x-ui.page-header title="Dashboard Pemohon"
        :subtitle="'Selamat datang, '.$pengguna->nama_lengkap.'. Anda masuk sebagai '.$pengguna->labelPeran().'. Layanan Pencatatan & Administrasi Kematian Kota Yogyakarta.'">
        <x-slot:aksi>
            <x-ui.button :href="route('laporan.baru')">Ajukan Laporan Baru</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-ink">Status Pengajuan</h2>
            <a href="{{ route('pengajuan.status') }}"
                class="text-sm font-semibold text-brand hover:text-brand-hover">Lihat semua</a>
        </div>

        @if ($ringkasanStatus->isEmpty())
            <p class="mt-3 text-sm text-muted">Belum ada pengajuan yang diajukan.</p>
        @else
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($ringkasanStatus as $status => $jumlah)
                    @php
                        $varianStat = match ($status) {
                            \App\Models\LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
                            \App\Models\LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE,
                            \App\Models\LaporanKematian::STATUS_DALAM_REKONSILIASI => 'info',
                            \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI,
                            \App\Models\LaporanKematian::STATUS_MENUNGGU_PERBAIKAN => 'danger',
                            \App\Models\LaporanKematian::STATUS_DIKEMBALIKAN => 'warning',
                            \App\Models\LaporanKematian::STATUS_SELESAI => 'success',
                            default => 'neutral',
                        };
                    @endphp
                    <x-ui.stat :label="$status" :value="$jumlah" :variant="$varianStat" />
                @endforeach
            </div>
        @endif
    </section>

    @if ($perluTindakan->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-lg font-semibold text-ink">Perlu Tindakan Anda</h2>
            <p class="mt-1 text-sm text-muted">Lengkapi draf atau perbaiki pengajuan yang dikembalikan.</p>

            @include('dashboard.tabel_laporan', ['daftar' => $perluTindakan])
        </section>
    @endif

    <section class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-ink">Pengajuan Saya</h2>
            <a href="{{ route('pengajuan.status') }}"
                class="text-sm font-semibold text-brand hover:text-brand-hover">Lihat semua</a>
        </div>

        @include('dashboard.tabel_laporan', ['daftar' => $pengajuanSaya])
    </section>
@endsection
