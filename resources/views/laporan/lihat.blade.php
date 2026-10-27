@extends('tata_letak.utama')

@section('judul', 'Ringkasan Pengajuan')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Ringkasan Pengajuan</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Nomor pengajuan:
                    <span class="font-semibold text-slate-800">
                        {{ $laporan->no_pengajuan ?? 'Belum diterbitkan' }}
                    </span>
                </p>
            </div>
            @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
        </div>

        @if ($laporan->tanggal_pengajuan !== null)
            <p class="mt-2 text-sm text-gray-500">
                Dikirim pada {{ $laporan->tanggal_pengajuan->translatedFormat('j F Y, H:i') }} WIB.
            </p>
        @else
            <p class="mt-2 text-sm text-gray-500">
                Pengajuan masih berupa draf. Lengkapi data dan berkas, lalu kirim untuk memperoleh nomor pengajuan.
            </p>
        @endif

        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Data Pelapor</h2>
                <dl class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Nama</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $laporan->nama_pelapor ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">NIK</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $pengguna->nik }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Hubungan</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $laporan->hubungan_pelapor ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Kontak</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $pengguna->no_telepon ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Email</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $pengguna->email }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Data Almarhum/Almarhumah</h2>
                <dl class="mt-2 space-y-1 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Nama</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $laporan->nama_almarhum ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">NIK</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $laporan->nik_almarhum ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Tanggal kematian</dt>
                        <dd class="text-right font-medium text-slate-800">
                            {{ $laporan->tanggal_kematian?->translatedFormat('j F Y') ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Tempat kematian</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $laporan->tempat_kematian ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Sebab kematian</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $laporan->sebab_kematian ?: '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="mt-6">
            <h2 class="font-semibold text-slate-800">Berkas Pendukung</h2>
            <ul class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200">
                @foreach (\App\Models\BerkasPendukung::DAFTAR_JENIS as $jenis)
                    @php
                        $berkas = $laporan->berkasPendukung->firstWhere('jenis_dokumen', $jenis);
                        $terunggah = $berkas !== null && $berkas->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL;
                    @endphp
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                        <span class="font-medium text-slate-700">{{ $jenis }}</span>
                        <span class="flex items-center gap-3">
                            @if ($terunggah)
                                <a href="{{ route('laporan.berkas', [$laporan, $berkas]) }}" target="_blank"
                                   class="text-slate-700 underline">{{ $berkas->nama_file }}</a>
                            @endif
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $terunggah ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $terunggah ? 'Terunggah' : 'Belum diunggah' }}
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            @if ($laporan->adalahDraf())
                <a href="{{ route('laporan.ubah', $laporan) }}"
                   class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                    Lanjutkan Pengisian
                </a>
            @elseif ($laporan->bolehDiubah())
                <a href="{{ route('laporan.ubah', $laporan) }}"
                   class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                    Perbaiki dan Kirim Ulang
                </a>
            @endif
            <a href="{{ route('dashboard') }}"
               class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
@endsection
