@extends('tata_letak.utama')

@section('judul', 'Dashboard Pemohon')

@section('navigasi')
    @include('komponen.menu_peran')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <x-ui.page-header title="Dashboard Pemohon"
        subtitle="Selamat datang, {{ $pengguna->nama_lengkap }}. Anda masuk sebagai {{ $pengguna->labelPeran() }}. Layanan Pencatatan & Administrasi Kematian Kota Yogyakarta.">
        <x-slot:aksi>
            <x-ui.button :href="route('laporan.baru')">Ajukan Laporan Baru</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    {{-- Status Pengajuan --}}
    <section class="mt-8">
        <h2 class="text-lg font-semibold text-ink">Status Pengajuan</h2>
        <p class="mt-1 text-sm text-muted">Ringkasan perkembangan seluruh pengajuan Anda.</p>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat label="Total Pengajuan" :value="$statistikPemohon['total']" variant="brand"
                catatan="Semua pengajuan Anda" />
            <x-ui.stat label="Menunggu Verifikasi" :value="$statistikPemohon['menunggu']" variant="warning"
                catatan="Sedang diperiksa petugas" />
            <x-ui.stat label="Perlu Perbaikan" :value="$statistikPemohon['perluPerbaikan']" variant="danger"
                catatan="Perlu ditindaklanjuti" />
            <x-ui.stat label="Selesai" :value="$statistikPemohon['selesai']" variant="success"
                catatan="Pengajuan selesai diproses" />
        </div>
    </section>

    {{-- Perlu Tindakan Anda --}}
    @if ($perluTindakan->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-lg font-semibold text-ink">Perlu Tindakan Anda</h2>
            <p class="mt-1 text-sm text-muted">Lengkapi draf atau perbaiki pengajuan yang dikembalikan.</p>

            <div class="mt-4">
                @include('dashboard.tabel_laporan', [
                    'daftar' => $perluTindakan,
                ])
            </div>
        </section>
    @endif

    {{-- Pengajuan Saya --}}
    <section class="mt-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-ink">Pengajuan Saya</h2>
                <p class="mt-1 text-sm text-muted">Pengajuan yang sedang diproses atau sudah selesai.</p>
            </div>

            <form action="{{ route('pengajuan.status') }}" method="GET"
                class="flex w-full flex-wrap items-end gap-2 sm:w-auto">
                <div class="min-w-0 flex-1 sm:w-64">
                    <x-ui.input name="nomor" type="text" :value="request('nomor')"
                        placeholder="Cari nomor pengajuan..." />
                </div>

                <x-ui.button type="submit">Cari</x-ui.button>
            </form>
        </div>

        <div class="mt-4">
            @include('dashboard.tabel_laporan', [
                'daftar' => $pengajuanSaya,
            ])
        </div>
    </section>
@endsection
