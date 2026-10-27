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

            @php
                $warnaKategori = match ($skor['kategori']) {
                    'tinggi' => 'bg-rose-100 text-rose-800',
                    'sedang' => 'bg-amber-100 text-amber-800',
                    default => 'bg-green-100 text-green-800',
                };
            @endphp
            <div class="mt-5 rounded-lg border border-gray-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-800">Skor Kecurigaan</h2>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Skor aturan deterministik 0-100 untuk memprioritaskan pemeriksaan.
                            Skor tidak mengubah status pengajuan dan bukan keputusan verifikasi.
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-slate-800">
                            {{ $skor['skor'] }}<span class="text-sm font-normal text-gray-500">/100</span>
                        </p>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $warnaKategori }}">
                            {{ $skor['kategori'] }}
                        </span>
                    </div>
                </div>

                @if ($skor['alasan'] === [])
                    <p class="mt-3 text-sm text-green-700">
                        Tidak ada alasan kecurigaan dari aturan yang tersedia.
                    </p>
                @else
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-700">
                        @foreach ($skor['alasan'] as $alasan)
                            <li>
                                {{ $alasan['keterangan'] }}
                                <span class="text-gray-500">(+{{ $alasan['bobot'] }})</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
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

            <div class="mt-5 rounded-lg border border-gray-200 p-4">
                <h2 class="font-semibold text-slate-800">Ringkasan Ketidaksesuaian (AI)</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Ringkasan dan saran tindakan untuk verifikator. Hasil bantuan AI, hanya saran;
                    verifikator dapat mengabaikannya.
                </p>

                @if ($suratKeterangan !== null)
                    <form method="POST" action="{{ route('verifikasi.ringkasan_ai', $laporan) }}" class="mt-3">
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                            Buat ringkasan AI
                        </button>
                    </form>
                @else
                    <p class="mt-3 text-sm text-amber-700">Surat Keterangan Kematian belum diunggah.</p>
                @endif

                @if ($ringkasan !== null)
                    @if ($ringkasanBerhasil)
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-900">Hasil bantuan AI, hanya saran</p>
                            <p class="mt-2 text-sm text-slate-800">{{ $ringkasan['ringkasan'] ?? '—' }}</p>

                            @if (! empty($ringkasan['saran']))
                                <p class="mt-3 text-sm font-semibold text-amber-900">Saran tindakan</p>
                                <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-slate-800">
                                    @foreach ($ringkasan['saran'] as $saran)
                                        <li>{{ $saran }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @else
                        <p class="mt-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            Analisis AI tidak tersedia, lanjutkan secara manual
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
