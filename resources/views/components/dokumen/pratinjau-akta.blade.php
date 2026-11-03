@props([
    'laporan',
    'dokumen' => null,
])

@php
    $dokumen = $dokumen ?? $laporan->dokumenResmi;
    $dataSiak = \App\Support\DataSIAK::cariNik($laporan->nik_almarhum) ?? [];

    $nomorDokumen = $dokumen?->no_dokumen ?: ($laporan->no_pengajuan ?: '-');
    $tanggalLahir = $dataSiak['tanggal_lahir'] ?? null;
    $umur = null;

    if ($tanggalLahir !== null && $laporan->tanggal_kematian !== null) {
        $umur = (int) \Illuminate\Support\Carbon::parse($tanggalLahir)->diffInYears($laporan->tanggal_kematian);
    }

    $alamat = $dataSiak['alamat'] ?? ($laporan->pemohon?->alamat ?: '-');
    $status = $laporan->status_pengajuan;
    $selesai = $status === \App\Models\LaporanKematian::STATUS_SELESAI;
    $dikembalikan = $status === \App\Models\LaporanKematian::STATUS_DIKEMBALIKAN;
    $alasan = $dokumen?->alasan_pengembalian ?: ($laporan->catatan_revisi ?: 'Alasan pengembalian belum dicatat.');
    $pejabat = $dokumen?->pejabat?->nama_lengkap;
    $waktuTtd = $dokumen?->tanggal_terbit;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div class="relative overflow-hidden rounded-2xl bg-line p-3 sm:p-6">
        <div
            class="relative mx-auto max-w-2xl overflow-hidden rounded-xl border border-line bg-surface p-5 shadow-[0_1px_2px_rgb(15_23_42_/_0.04)] sm:p-10">
            {{-- Watermark simulasi, bukan dokumen resmi. --}}
            <span
                class="pointer-events-none absolute inset-0 flex select-none items-center justify-center -rotate-45 text-5xl font-extrabold uppercase tracking-[0.35em] text-brand/5 sm:text-7xl"
                aria-hidden="true">SIMULASI</span>

            <p
                class="relative mb-6 rounded-xl border border-[#f59e0b] bg-[#fef3c7] px-3 py-2 text-center text-[11px] font-bold uppercase tracking-wide text-[#b45309]">
                DOKUMEN SIMULASI - DATA FIKTIF - BUKAN DOKUMEN RESMI
            </p>

            <header class="relative border-b border-line pb-5 text-center">
                <p class="text-2xl leading-none text-gold" aria-hidden="true">★</p>
                <p class="mt-2 text-xs font-bold uppercase tracking-[0.18em] text-ink-soft">
                    {{ config('satuduka.instansi') }}
                </p>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">
                    {{ config('satuduka.dinas') }}
                </p>
                <p class="text-xs text-faint">{{ config('satuduka.daerah') }}</p>

                <h2 class="mt-4 font-serif text-lg font-bold uppercase tracking-[0.18em] text-ink sm:text-xl">
                    Akta Kematian
                </h2>
                <p class="mt-1 font-mono text-xs font-semibold text-ink-soft">{{ $nomorDokumen }}</p>
            </header>

            <div class="relative mt-5 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs text-muted">Nama Almarhum/Almarhumah</p>
                    <p class="mt-0.5 font-semibold text-ink">{{ $laporan->nama_almarhum ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted">NIK</p>
                    <p class="mt-0.5 font-mono font-semibold text-ink">{{ $laporan->nik_almarhum ?: '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted">Tanggal Lahir</p>
                    <p class="mt-0.5 font-semibold text-ink">
                        {{ $tanggalLahir ? \Illuminate\Support\Carbon::parse($tanggalLahir)->translatedFormat('j F Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-muted">Umur</p>
                    <p class="mt-0.5 font-semibold text-ink">{{ $umur !== null ? $umur.' tahun' : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted">Tanggal Kematian</p>
                    <p class="mt-0.5 font-semibold text-ink">
                        {{ $laporan->tanggal_kematian?->translatedFormat('j F Y') ?? '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-muted">Tempat Kematian</p>
                    <p class="mt-0.5 font-semibold text-ink">{{ $laporan->tempat_kematian ?: '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs text-muted">Alamat</p>
                    <p class="mt-0.5 font-semibold text-ink">{{ $alamat }}</p>
                </div>
            </div>

            <div class="relative mt-8 flex flex-col items-end gap-1 border-t border-line pt-5 text-sm">
                <p class="text-xs text-muted">{{ config('satuduka.daerah') }}, {{ $waktuTtd?->translatedFormat('j F Y') ?? '-' }}</p>
                <p class="text-xs text-muted">Pejabat Penanda Tangan</p>
                <p class="mt-4 font-semibold text-ink">{{ $pejabat ?: '[ BELUM DITANDATANGANI ]' }}</p>
                <p class="text-xs text-faint">
                    {{ $waktuTtd ? $waktuTtd->translatedFormat('j F Y, H:i').' WIB' : 'Waktu penandatanganan belum tersedia' }}
                </p>
            </div>
        </div>
    </div>

    @if ($selesai)
        <x-ui.notice icon="shield-check">
            <p class="font-semibold text-ink">Tanda Tangan Elektronik (Simulasi)</p>
            <p class="mt-1">
                Ditandatangani secara elektronik (simulasi). Bukan tanda tangan elektronik tersertifikasi.
            </p>
        </x-ui.notice>

        @isset($aksi)
            <div class="flex flex-col gap-2 sm:flex-row">
                {{ $aksi }}
            </div>
        @endisset
    @elseif ($dikembalikan)
        <x-ui.alert variant="warning">
            <p class="font-semibold">Alasan Dikembalikan</p>
            <p class="mt-1 font-normal">{{ $alasan }}</p>
        </x-ui.alert>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <x-ui.badge variant="neutral">[ BELUM DITANDATANGANI ]</x-ui.badge>

            @isset($aksi)
                <div class="flex flex-col gap-2 sm:flex-row">
                    {{ $aksi }}
                </div>
            @endisset
        </div>
    @else
        <div class="flex flex-wrap items-center gap-3">
            @include('komponen.badge_status', ['status' => $status])
            <span class="text-sm text-muted">Dokumen belum dapat diunduh pada status ini.</span>
        </div>
    @endif
</div>
