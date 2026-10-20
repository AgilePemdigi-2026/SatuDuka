@extends('tata_letak.utama')

@section('judul', 'Dashboard Pemohon')

@section('navigasi')
    <span class="text-sm text-gray-700">{{ $pengguna->nama_lengkap }}</span>
    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Keluar</button>
    </form>
@endsection

@section('konten')
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Dashboard Pemohon</h1>
        <p class="mt-1 text-sm text-gray-500">
            Selamat datang, {{ $pengguna->nama_lengkap }}. Layanan Pencatatan &amp; Administrasi Kematian Kota Yogyakarta.
        </p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Ajukan Laporan Kematian</h2>
                <p class="mt-1 text-sm text-gray-500">Menyusul pada Fase 3.</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Status Pengajuan</h2>
                <p class="mt-1 text-sm text-gray-500">Menyusul pada Fase 4.</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Profil Saya</h2>
                <p class="mt-1 text-sm text-gray-500">Menyusul pada Fase 5.</p>
            </div>
        </div>
    </div>
@endsection
