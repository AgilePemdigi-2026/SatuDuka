@extends('tata_letak.utama')

@section('judul', 'Ringkasan Pengajuan')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-soft hover:text-brand">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4 rounded-2xl border border-line bg-surface px-4 py-3">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-brand">
                <x-icon.arrow-left class="h-4 w-4" />
                Kembali ke Dashboard
            </a>
            <span class="text-xs text-faint">Ringkasan Pengajuan</span>
        </div>

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-3xl font-bold tracking-tight text-ink">
                    {{ $laporan->nama_almarhum ?: 'Ringkasan Pengajuan' }}
                </h1>
                <p class="mt-1 text-sm text-muted">
                    Nomor Dokumen:
                    <span class="font-mono text-ink">{{ $laporan->no_pengajuan ?? 'Belum diterbitkan' }}</span>
                </p>
            </div>

            @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <div class="rounded-2xl bg-[#d9dcde] p-6">
                    <div class="mx-auto max-w-2xl rounded-xl bg-surface p-8 shadow-[0_1px_2px_rgb(15_23_42_/_0.04)]">
                        <p class="text-center font-serif text-lg font-bold uppercase tracking-[0.2em] text-gold">
                            Ringkasan Pengajuan
                        </p>
                        <div class="mt-3 h-px bg-line"></div>

                        <section class="mt-6">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">Data Pelapor</h2>
                            <dl class="mt-3 divide-y divide-line">
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Nama</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->nama_pelapor ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">NIK</dt>
                                    <dd class="text-right font-mono text-sm font-semibold text-ink">
                                        {{ \App\Support\MaskerNik::masker($pengguna->nik) ?? '-' }}
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Hubungan</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->hubungan_pelapor ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Kontak</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $pengguna->no_telepon ?? '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Email</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $pengguna->email }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="mt-6">
                            <h2 class="text-sm font-bold uppercase tracking-wide text-ink-soft">
                                Data Almarhum/Almarhumah
                            </h2>
                            <dl class="mt-3 divide-y divide-line">
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Nama</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->nama_almarhum ?: '-' }}</dd>
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
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->tempat_kematian ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4 py-2.5">
                                    <dt class="text-xs text-muted">Sebab kematian</dt>
                                    <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->sebab_kematian ?: '-' }}</dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </div>

                <x-ui.card title="Berkas Pendukung">
                    <ul class="divide-y divide-line">
                        @foreach ($daftarJenis as $jenis)
                            @php
                                $berkas = $laporan->berkasPendukung->firstWhere('jenis_dokumen', $jenis);
                                $terunggah = $berkas !== null && $berkas->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL;
                            @endphp
                            <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <span class="text-sm font-medium text-ink-soft">
                                    {{ $jenis }}
                                    @if (! in_array($jenis, $daftarJenisWajib, true))
                                        <span class="font-normal text-muted">(opsional)</span>
                                    @endif
                                </span>
                                <span class="flex items-center gap-3">
                                    @if ($terunggah)
                                        <a href="{{ route('laporan.berkas', [$laporan, $berkas]) }}" target="_blank"
                                            class="text-xs font-semibold text-brand hover:text-brand-hover">
                                            {{ $berkas->nama_file }}
                                        </a>
                                    @endif
                                    <x-ui.badge :variant="$terunggah ? 'success' : 'neutral'">
                                        {{ $terunggah ? 'Terunggah' : 'Belum diunggah' }}
                                    </x-ui.badge>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            </div>

            <aside class="space-y-4">
                <x-ui.card title="Informasi Dokumen" padding="p-5">
                    <dl class="divide-y divide-line">
                        <div class="flex items-start justify-between gap-4 py-3 first:pt-0">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nomor Pengajuan</dt>
                            <dd class="text-right font-mono text-sm font-semibold text-ink">
                                {{ $laporan->no_pengajuan ?? '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Nama Almarhum/Almarhumah</dt>
                            <dd class="text-right text-sm font-semibold text-ink">{{ $laporan->nama_almarhum ?: '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Tanggal Pengajuan</dt>
                            <dd class="text-right text-sm font-semibold text-ink">
                                {{ $laporan->tanggal_pengajuan?->translatedFormat('j F Y, H:i') ?? '-' }}
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
                    @if ($laporan->tanggal_pengajuan !== null)
                        <p class="mt-1">
                            Dikirim pada {{ $laporan->tanggal_pengajuan->translatedFormat('j F Y, H:i') }} WIB.
                            Pengajuan akan diperiksa oleh verifikator.
                        </p>
                    @else
                        <p class="mt-1">
                            Pengajuan masih berupa draf. Lengkapi data dan berkas, lalu kirim untuk memperoleh
                            nomor pengajuan.
                        </p>
                    @endif
                </x-ui.notice>

                <div class="flex flex-col gap-2">
                    @if ($laporan->adalahDraf())
                        <x-ui.button :href="route('laporan.ubah', $laporan)" class="w-full">
                            Lanjutkan Pengisian
                        </x-ui.button>
                    @elseif ($laporan->bolehDiubah())
                        <x-ui.button :href="route('laporan.ubah', $laporan)" class="w-full">
                            Perbaiki dan Kirim Ulang
                        </x-ui.button>
                    @endif

                    @if ($laporan->selesai())
                        <x-ui.button :href="route('pengajuan.unduh', $laporan)" class="w-full">
                            <x-icon.arrow-down-tray class="h-4 w-4" />
                            Unduh Dokumen (PDF)
                        </x-ui.button>
                    @else
                        <x-ui.button type="button" disabled class="w-full">
                            <x-icon.arrow-down-tray class="h-4 w-4" />
                            Unduh Dokumen (PDF)
                        </x-ui.button>
                    @endif

                    <x-ui.button type="button" variant="secondary" onclick="window.print()" class="w-full">
                        <x-icon.printer class="h-4 w-4" />
                        Cetak Dokumen
                    </x-ui.button>

                    <x-ui.button :href="route('dashboard')" variant="secondary" class="w-full">
                        Kembali ke Dashboard
                    </x-ui.button>
                </div>

                <p class="text-xs text-faint">*Unduhan aktif setelah status "Selesai"</p>

                <p class="text-xs leading-relaxed text-muted">
                    Dokumen ini diterbitkan secara elektronik dan sah menurut Undang-Undang Informasi dan
                    Transaksi Elektronik.
                </p>
            </aside>
        </div>
    </div>
@endsection
