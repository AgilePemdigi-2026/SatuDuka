@extends('tata_letak.utama')

@section('judul', 'Detail Verifikasi')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    @php
        $suratKeterangan = $laporan->berkasPendukung->firstWhere(
            'jenis_dokumen',
            \App\Models\BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        );
    @endphp

    <div class="space-y-4">
        <a href="{{ route('verifikasi.daftar') }}" class="inline-block text-sm text-slate-700 underline">
            &larr; Kembali ke Menunggu Verifikasi
        </a>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">{{ $laporan->nama_almarhum }}</h1>
                    <p class="mt-1 font-mono text-sm text-gray-500">{{ $laporan->no_pengajuan }}</p>
                </div>
                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800">
                    {{ $laporan->status_pengajuan }}
                </span>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h2 class="font-semibold text-slate-800">Data Almarhum/Almarhumah</h2>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Nama</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $laporan->nama_almarhum }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">NIK</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $laporan->nik_almarhum }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Tanggal kematian</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ $laporan->tanggal_kematian?->translatedFormat('j F Y') ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Tempat kematian</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $laporan->tempat_kematian ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Sebab kematian</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $laporan->sebab_kematian ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <h2 class="font-semibold text-slate-800">Data Pelapor</h2>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Nama</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $laporan->nama_pelapor }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Hubungan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $laporan->hubungan_pelapor }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Tanggal pengajuan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $laporan->labelTanggalPengajuan() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="mt-5">
                <h2 class="font-semibold text-slate-800">Berkas Pendukung</h2>
                <ul class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200">
                    @foreach ($laporan->berkasPendukung as $berkas)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                            <span class="font-medium text-slate-700">{{ $berkas->jenis_dokumen }}</span>
                            <span class="flex flex-wrap items-center gap-3">
                                <a href="{{ route('verifikasi.berkas', [$laporan, $berkas]) }}" target="_blank"
                                   class="text-slate-700 underline">{{ $berkas->nama_file }}</a>
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">
                                    {{ $berkas->status_unggahan }}
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-5 rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Pemeriksaan Dokumen (AI)</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Hasil bantuan AI, hanya saran. Tidak ada keputusan verifikasi yang diambil otomatis.
                </p>

                @if ($suratKeterangan !== null)
                    <form method="POST" action="{{ route('verifikasi.analisis_ai', $laporan) }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="id_berkas" value="{{ $suratKeterangan->id_berkas }}">
                        <button type="submit"
                                class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                            Baca dokumen (AI)
                        </button>
                    </form>
                @else
                    <p class="mt-3 text-sm text-amber-700">Surat Keterangan Kematian belum diunggah.</p>
                @endif

                @if ($analisis !== null)
                    @include('laporan.panel_analisis_ai', ['analisis' => $analisis])
                @endif
            </div>
        </div>
    </div>
@endsection
