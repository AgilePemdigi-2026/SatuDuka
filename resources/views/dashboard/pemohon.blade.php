@extends('tata_letak.utama')

@section('judul', 'Dashboard Pemohon')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
<<<<<<< HEAD
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Dashboard Pemohon</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Selamat datang, {{ $pengguna->nama_lengkap }}. Anda masuk sebagai {{ $pengguna->labelPeran() }}.
                    Layanan Pencatatan &amp; Administrasi Kematian Kota Yogyakarta.
                </p>
=======
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Dashboard Pemohon</h1>
        <p class="mt-1 text-sm text-gray-500">
            Selamat datang, {{ $pengguna->nama_lengkap }}. Layanan Pencatatan &amp; Administrasi Kematian Kota Yogyakarta.
        </p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-4">
                
<h2 class="font-semibold text-slate-800">Ajukan Laporan Kematian</h2>
<p class="mt-1 text-sm text-gray-500">
    Buat pengajuan laporan kematian secara online.
</p>

<a href="{{ route('laporan.create') }}"
   class="mt-3 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
    Ajukan Sekarang
</a>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Status Pengajuan</h2>
                <p class="mt-1 text-sm text-gray-500">Menyusul pada Fase 4.</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Profil Saya</h2>
                <p class="mt-1 text-sm text-gray-500">Menyusul pada Fase 5.</p>
>>>>>>> 288a7cc110b88f9b9c33e8fa296ed4f16965f243
            </div>
            <a href="{{ route('laporan.baru') }}"
               class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                Ajukan Laporan Baru
            </a>
        </div>

        @if ($perluTindakan->isNotEmpty())
            <section class="mt-8">
                <h2 class="text-lg font-semibold text-slate-800">Perlu Tindakan Anda</h2>
                <p class="mt-1 text-sm text-gray-500">Lengkapi draf atau perbaiki pengajuan yang dikembalikan.</p>

                @include('dashboard.tabel_laporan', ['daftar' => $perluTindakan])
            </section>
        @endif

        <section class="mt-8">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-slate-800">Pengajuan Saya</h2>
                <a href="{{ route('pengajuan.status') }}" class="text-sm text-slate-700 underline">Lihat semua</a>
            </div>

            @include('dashboard.tabel_laporan', ['daftar' => $pengajuanSaya])
        </section>

        <section class="mt-8">
            <div class="rounded-lg border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-semibold text-slate-800">Status Pengajuan</h2>
                    <a href="{{ route('pengajuan.status') }}" class="text-sm text-slate-700 underline">Lihat semua</a>
                </div>

                @if ($ringkasanStatus->isEmpty())
                    <p class="mt-1 text-sm text-gray-500">Belum ada pengajuan yang diajukan.</p>
                @else
                    <ul class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($ringkasanStatus as $status => $jumlah)
                            <li class="flex items-center justify-between gap-2 rounded-md border border-gray-100 px-3 py-2 text-sm">
                                <span class="text-gray-600">{{ $status }}</span>
                                @include('komponen.badge_status', ['status' => $status, 'teks' => $jumlah])
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
@endsection
