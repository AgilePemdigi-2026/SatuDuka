@extends('tata_letak.utama')

@section('judul', 'Dashboard Pejabat')

@section('navigasi')
    <a href="{{ route('profil.tampilkan') }}" class="text-sm text-gray-600 hover:underline">Kelola Profil</a>
    <span class="text-sm text-gray-700">{{ $pengguna->nama_lengkap }}</span>
    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Keluar</button>
    </form>
@endsection

@section('konten')
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Dashboard Pejabat Penanda Tangan</h1>
        <p class="mt-1 text-sm text-gray-500">
            Selamat datang, {{ $pengguna->nama_lengkap }}. Antrean dokumen untuk ditandatangani elektronik.
        </p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Tanda Tangan Elektronik (TTE)</h2>
                <p class="mt-1 text-sm text-gray-500">Segera hadir.</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Profil Saya</h2>
                <p class="mt-1 text-sm text-gray-500">
                    <a href="{{ route('profil.tampilkan') }}" class="text-slate-700 underline">Kelola Profil</a>
                </p>
            </div>
        </div>
    </div>
@endsection
