@extends('tata_letak.utama')

@section('judul', 'Detail Verifikasi')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-soft hover:text-brand">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    @php
        $suratKeterangan = $laporan->berkasPendukung->firstWhere(
            'jenis_dokumen',
            \App\Models\BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN,
        );
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4 rounded-2xl border border-line bg-surface px-4 py-3">
            <a href="{{ route('verifikasi.daftar') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-brand">
                <x-icon.arrow-left class="h-4 w-4" />
                Kembali ke Menunggu Verifikasi
            </a>
            <span class="text-xs text-faint">Detail Verifikasi</span>
        </div>

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-3xl font-bold tracking-tight text-ink">{{ $laporan->nama_almarhum }}</h1>
                <p class="mt-1 text-sm text-muted">
                    Nomor Dokumen:
                    <span class="font-mono text-ink">{{ $laporan->no_pengajuan }}</span>
                </p>
            </div>

            @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <div class="rounded-2xl bg-[#d9dcde] p-6">
                    <div class="mx-auto max-w-2xl rounded-xl bg-surface p-8 shadow-[0_1px_2px_rgb(15_23_42_/_0.04)]">
                        <p class="text-center font-serif text-lg font-bold uppercase tracking-[0.2em] text-gold">
                            Data Pengajuan
                        </p>
                        <div class="mt-3 h-px bg-line"></div>

                        <section class="mt-6">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">
                                Data Almarhum/Almarhumah
                            </h2>
                            <dl class="mt-3 divide-y divide-line">
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Nama</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->nama_almarhum }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">NIK</dt>
                                    <dd class="text-right font-mono text-sm font-semibold text-ink">
                                        {{ \App\Support\MaskerNik::masker($laporan->nik_almarhum) ?? '-' }}
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Tanggal kematian</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">
                                        {{ $laporan->tanggal_kematian?->translatedFormat('j F Y') ?? '-' }}
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Tempat kematian</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->tempat_kematian ?? '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Sebab kematian</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->sebab_kematian ?? '-' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="mt-6">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Data Pelapor</h2>
                            <dl class="mt-3 divide-y divide-line">
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Nama</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->nama_pelapor }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Hubungan</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->hubungan_pelapor }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Tanggal pengajuan</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->labelTanggalPengajuan() }}</dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </div>

                <x-ui.card title="Berkas Pendukung">
                    @if ($laporan->berkasPendukung->isEmpty())
                        <p class="text-sm text-muted">Belum ada berkas pendukung.</p>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($laporan->berkasPendukung as $berkas)
                                <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                    <span class="text-sm font-medium text-ink-soft">{{ $berkas->jenis_dokumen }}</span>
                                    <span class="flex items-center gap-3">
                                        <a href="{{ route('verifikasi.berkas', [$laporan, $berkas]) }}" target="_blank"
                                            class="text-xs font-semibold text-brand hover:text-brand-hover">
                                            {{ $berkas->nama_file }}
                                        </a>
                                        <x-ui.badge
                                            :variant="$berkas->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL ? 'success' : 'warning'">
                                            {{ $berkas->status_unggahan }}
                                        </x-ui.badge>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>

                <x-ui.card title="Pemeriksaan Dokumen (AI)">
                    <p class="text-sm text-muted">
                        Hasil bantuan AI, hanya saran. Tidak ada keputusan verifikasi yang diambil otomatis.
                    </p>

                    @if ($analisis !== null)
                        @include('laporan.panel_analisis_ai', ['analisis' => $analisis])
                    @else
                        <p class="mt-3 text-sm text-muted">Belum ada hasil pemeriksaan dokumen.</p>
                    @endif
                </x-ui.card>

                <x-ui.card title="Ringkasan Ketidaksesuaian (AI)">
                    <p class="text-sm text-muted">
                        Ringkasan dan saran tindakan untuk verifikator. Hasil bantuan AI, hanya saran;
                        verifikator dapat mengabaikannya.
                    </p>

                    @if ($ringkasan !== null)
                        @if ($ringkasanBerhasil)
                            <div class="mt-4 rounded-2xl border border-[#f59e0b] bg-[#fef3c7] p-4">
                                <p class="text-sm font-semibold text-[#b45309]">Hasil bantuan AI, hanya saran</p>
                                <p class="mt-2 text-sm text-ink">{{ $ringkasan['ringkasan'] ?? '-' }}</p>

                                @if (! empty($ringkasan['saran']))
                                    <p class="mt-3 text-sm font-semibold text-[#b45309]">Saran tindakan</p>
                                    <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-ink-soft">
                                        @foreach ($ringkasan['saran'] as $saran)
                                            <li>{{ $saran }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @else
                            <x-ui.alert variant="danger" class="mt-4">
                                Analisis AI tidak tersedia, lanjutkan secara manual
                            </x-ui.alert>
                        @endif
                    @else
                        <p class="mt-3 text-sm text-muted">Belum ada ringkasan AI.</p>
                    @endif
                </x-ui.card>
            </div>

            <aside class="space-y-4">
                <x-ui.card title="Informasi Dokumen" padding="p-5">
                    <dl class="divide-y divide-line">
                        <div class="flex items-start justify-between gap-4 py-3 first:pt-0">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nomor Pengajuan</dt>
                            <dd class="text-right font-mono text-sm font-semibold text-ink">{{ $laporan->no_pengajuan }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nama Almarhum/Almarhumah</dt>
                            <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->nama_almarhum }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">NIK</dt>
                            <dd class="text-right font-mono text-sm font-semibold text-ink">
                                {{ \App\Support\MaskerNik::masker($laporan->nik_almarhum) ?? '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3 last:pb-0">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Status</dt>
                            <dd class="text-right">
                                @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                            </dd>
                        </div>
                    </dl>
                </x-ui.card>

                <x-ui.notice icon="shield-check">
                    <p class="font-semibold text-ink">Panel Verifikasi</p>
                    <p class="mt-1">
                        Halaman ini hanya untuk pemeriksaan berkas. Hasil AI adalah saran; keputusan
                        verifikasi tidak diambil otomatis.
                    </p>
                </x-ui.notice>

                <x-ui.card title="Tindakan Verifikasi" padding="p-5">
                    @if ($suratKeterangan !== null)
                        <form method="POST" action="{{ route('verifikasi.analisis_ai', $laporan) }}">
                            @csrf
                            <input type="hidden" name="id_berkas" value="{{ $suratKeterangan->id_berkas }}">
                            <x-ui.button type="submit" class="w-full">Baca dokumen (AI)</x-ui.button>
                        </form>

                        <form method="POST" action="{{ route('verifikasi.ringkasan_ai', $laporan) }}" class="mt-2">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" class="w-full">
                                Buat ringkasan AI
                            </x-ui.button>
                        </form>
                    @else
                        <x-ui.alert variant="warning">
                            Surat Keterangan Kematian belum diunggah.
                        </x-ui.alert>
                    @endif
                </x-ui.card>

                <div class="flex flex-col gap-2">
                    <x-ui.button type="button" disabled class="w-full">
                        <x-icon.arrow-down-tray class="h-4 w-4" />
                        Unduh Dokumen (PDF)
                    </x-ui.button>

                    <x-ui.button type="button" variant="secondary" onclick="window.print()" class="w-full">
                        <x-icon.printer class="h-4 w-4" />
                        Cetak Dokumen
                    </x-ui.button>
                </div>

                <p class="text-xs text-faint">*Aktif setelah status "Selesai"</p>

                <p class="text-xs leading-relaxed text-muted">
                    Dokumen ini diterbitkan secara elektronik dan sah menurut Undang-Undang Informasi dan
                    Transaksi Elektronik.
                </p>
            </aside>
        </div>
    </div>
@endsection
