
@extends('tata_letak.utama')

@section('judul', 'Pusat Notifikasi')

@section('konten')
<div class="mx-auto max-w-4xl space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink">
                Notifikasi Pengajuan
            </h1>
            <p class="mt-1 text-sm text-muted">
                Pantau perubahan status, permintaan perbaikan,
                dan penerbitan dokumen resmi.
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center
                        rounded-full bg-slate-200 font-bold text-slate-700">
                {{ substr($pengguna->nama ?? 'P', 0, 1) }}
            </div>

            <div>
                <p class="text-sm font-semibold text-ink">
                    {{ $pengguna->nama }}
                </p>
                <p class="text-xs text-muted">Pemohon</p>
            </div>
        </div>
    </div>

    <!-- Pencarian dan Tombol Aksi -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

        <!-- Form Pencarian -->
        <form
            method="GET"
            action="{{ route('notifikasi.index') }}"
            class="flex min-w-0 flex-1 gap-3"
        >
            <div class="relative min-w-0 flex-1">
                <input
                    type="text"
                    name="cari"
                    value="{{ $cari }}"
                    placeholder="Cari nomor pengajuan atau isi notifikasi"
                    class="w-full rounded-xl border border-line
                           bg-surface px-4 py-2.5 text-sm
                           focus:border-brand focus:outline-none"
                >
            </div>

            <button
                type="submit"
                class="shrink-0 rounded-xl bg-ink px-4 py-2.5
                       text-sm font-semibold text-white
                       hover:bg-slate-800"
            >
                Cari
            </button>
        </form>

        <!-- Form Tandai Semua Dibaca -->
        <form
            method="POST"
            action="{{ route('notifikasi.tandai_semua') }}"
            class="shrink-0"
        >
            @csrf

            <x-ui.button
                type="submit"
                variant="secondary"
                class="w-full sm:w-auto"
            >
                Tandai Semua Dibaca
            </x-ui.button>
        </form>

    </div>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-2 border-b border-line pb-2">

        <a
            href="{{ route('notifikasi.index', ['tab' => 'semua']) }}"
            class="rounded-full px-4 py-1.5 text-xs font-semibold
                   {{ $tab === 'semua'
                       ? 'bg-ink text-white'
                       : 'border border-line text-ink hover:bg-slate-50' }}"
        >
            Semua
        </a>

        <a
            href="{{ route('notifikasi.index', ['tab' => 'belum_dibaca']) }}"
            class="rounded-full px-4 py-1.5 text-xs font-semibold
                   {{ $tab === 'belum_dibaca'
                       ? 'bg-ink text-white'
                       : 'border border-line text-ink hover:bg-slate-50' }}"
        >
            Belum Dibaca ({{ $jumlahBelumDibaca }})
        </a>

        <a
            href="{{ route('notifikasi.index', ['tab' => 'perlu_tindakan']) }}"
            class="rounded-full px-4 py-1.5 text-xs font-semibold
                   {{ $tab === 'perlu_tindakan'
                       ? 'bg-ink text-white'
                       : 'border border-line text-ink hover:bg-slate-50' }}"
        >
            Perlu Tindakan
        </a>

        <a
            href="{{ route('notifikasi.index', ['tab' => 'selesai']) }}"
            class="rounded-full px-4 py-1.5 text-xs font-semibold
                   {{ $tab === 'selesai'
                       ? 'bg-ink text-white'
                       : 'border border-line text-ink hover:bg-slate-50' }}"
        >
            Selesai
        </a>

    </div>

    <!-- Daftar Notifikasi -->
    <div class="space-y-3">

        @forelse ($notifikasis as $item)

            <div
                class="relative flex flex-col gap-4 rounded-2xl
                       border border-line bg-surface p-4 shadow-sm
                       sm:flex-row sm:items-center
                       sm:justify-between
                       {{ ! $item->sudah_dibaca
                           ? 'border-l-4 border-l-brand'
                           : '' }}"
            >

                <!-- Konten Notifikasi -->
                <div class="flex min-w-0 items-start gap-4">

                    <!-- Ikon Berdasarkan Jenis -->
                    <div
                        class="flex h-10 w-10 shrink-0 items-center
                               justify-center rounded-full
                               bg-slate-100 text-ink"
                    >

                        @if ($item->jenis === 'validasi_awal')

                            <x-icon.check class="h-5 w-5" />

                        @elseif ($item->jenis === 'perlu_diperbaiki')

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10.29 3.86 1.82 18a2 2
                                       0 0 0 1.71 3h16.94a2 2
                                       0 0 0 1.71-3L13.71
                                       3.86a2 2 0 0 0-3.42 0Z"
                                />
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                            </svg>

                        @elseif ($item->jenis === 'dokumen_terbit')

                            <x-icon.document-text class="h-5 w-5" />

                        @else

                            <x-icon.clock class="h-5 w-5" />

                        @endif

                    </div>

                    <!-- Informasi Notifikasi -->
                    <div class="min-w-0">

                        <div class="flex flex-wrap items-center gap-2">

                            <h3 class="text-sm font-bold text-ink">
                                {{ $item->judul }}
                            </h3>

                            @if (! $item->sudah_dibaca)
                                <span
                                    class="rounded bg-slate-200
                                           px-1.5 py-0.5 text-[10px]
                                           font-semibold text-slate-700"
                                >
                                    BELUM DIBACA
                                </span>
                            @endif

                        </div>

                        <p class="mt-0.5 text-xs text-muted">
                            {{ $item->pesan }}
                        </p>

                        <p class="mt-1 text-[11px] text-faint">
                            {{ $item->created_at->translatedFormat('j F Y, H.i') }}
                            WIB

                            @if ($item->laporan)
                                &bull; {{ $item->laporan->no_pengajuan }}
                            @endif
                        </p>

                    </div>

                </div>

                <!-- Tombol Aksi Kontekstual -->
                <div class="shrink-0">

                    @if ($item->jenis === 'perlu_diperbaiki')

                        <a
                            href="{{ route('laporan.ubah', $item->laporan) }}"
                            class="inline-flex items-center
                                   justify-center rounded-xl
                                   bg-ink px-4 py-2 text-xs
                                   font-semibold text-white
                                   hover:bg-slate-800"
                        >
                            Perbaiki Pengajuan
                        </a>

                    @elseif ($item->jenis === 'dokumen_terbit')

                        <a
                            href="{{ route('pengajuan.unduh', $item->laporan) }}"
                            class="inline-flex items-center
                                   justify-center rounded-xl
                                   bg-ink px-4 py-2 text-xs
                                   font-semibold text-white
                                   hover:bg-slate-800"
                        >
                            Unduh Dokumen
                        </a>

                    @else

                        <a
                            href="{{ route('pengajuan.detail', $item->laporan) }}"
                            class="inline-flex items-center
                                   justify-center rounded-xl
                                   border border-line px-4 py-2
                                   text-xs font-semibold text-ink
                                   hover:bg-slate-50"
                        >
                            Lihat Status
                        </a>

                    @endif

                </div>

            </div>

        @empty

            <!-- Kondisi Tidak Ada Notifikasi -->
            <div
                class="rounded-2xl border border-dashed
                       border-line p-8 text-center text-sm text-muted"
            >
                Tidak ada notifikasi.
            </div>

        @endforelse

    </div>

</div>
@endsection
