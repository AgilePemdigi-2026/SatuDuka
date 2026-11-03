@extends('tata_letak.verifikator')

@section('judul', 'Dashboard Verifikator')
@section('subjudul', 'Ringkasan seluruh proses verifikasi dan rekonsiliasi')

@section('konten')
<div class="stack">

    <!-- Kartu Statistik -->
    <section class="stats">
        <div class="stat">
            <p class="stat-label">Total Permohonan</p>
            <p class="stat-value">{{ $ringkasan['total'] }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Menunggu Verifikasi</p>
            <p class="stat-value">{{ $ringkasan['menunggu'] }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Anomali Perlu Rekonsiliasi</p>
            <p class="stat-value">{{ $ringkasan['rekonsiliasi'] }}</p>
        </div>
        <div class="stat">
            <p class="stat-label">Selesai</p>
            <p class="stat-value">{{ $ringkasan['selesai'] }}</p>
        </div>
    </section>

    <!-- Tabel Permohonan -->
    <section class="card">
        <div class="card-head dashboard-card-head">
            <div>
                <h2 class="card-title">Daftar Permohonan</h2>
                <p class="card-desc">Pilih berkas untuk melakukan pemeriksaan detail.</p>
            </div>

            <form method="GET" action="{{ route('verifikasi.index') }}" class="search-form">
                <select id="status" name="status" class="field" aria-label="Filter status"
                        onchange="this.form.submit()"
                        style="appearance: none; background-image: none; width: 190px; min-width: 190px; border-radius: 999px; padding: 10px 16px; text-align: center; text-align-last: center;">
                    @foreach ([
                        'semua' => 'Semua Status',
                        'disetujui' => 'Disetujui',
                        'menunggu' => 'Menunggu Verifikasi',
                        'rekonsiliasi' => 'Perlu Rekonsiliasi',
                        'selesai' => 'Selesai',
                    ] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($statusFilter === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                <input id="cari" name="cari" type="search" value="{{ request('cari') }}"
                       placeholder="Cari NIK atau Nama..." class="field search-input">
                @if (request('cari') || $statusFilter !== 'semua')
                    <a href="{{ route('verifikasi.index') }}" class="btn btn-outline btn-pill btn-sm" style="padding: 10px 20px;">Reset</a>
                @endif
            </form>
        </div>

        @if ($pengajuan->isEmpty())
            <div class="empty">
                <div class="empty-ic">✓</div>
                <p style="font-size: 14px; font-weight: 600; color: var(--ink);">Tidak ada permohonan yang sesuai</p>
                <p style="font-size: 12.5px; color: var(--faint); margin-top: 4px;">Coba ubah status atau kata kunci pencarian.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No.</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Tanggal Masuk</th>
                            <th>Status</th>
                            <th class="c">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pengajuan as $index => $item)
                            @php
                                [$labelStatus, $warnaStatus] = match ($item->status_pengajuan) {
                                    \App\Models\LaporanKematian::STATUS_MENUNGGU_VERIFIKASI => ['Menunggu Verifikasi', 'badge-brand'],
                                    \App\Models\LaporanKematian::STATUS_DISETUJUI_MENUNGGU_TTE => ['Disetujui', 'badge-brand'],
                                    \App\Models\LaporanKematian::STATUS_DALAM_REKONSILIASI => ['Perlu Rekonsiliasi', 'badge-warning'],
                                    \App\Models\LaporanKematian::STATUS_SELESAI => ['Selesai', 'badge-ok'],
                                    \App\Models\LaporanKematian::STATUS_DIKEMBALIKAN => ['Dikembalikan', 'badge-bad'],
                                    \App\Models\LaporanKematian::STATUS_DITOLAK_BUTUH_REVISI => ['Ditolak/Butuh Revisi', 'badge-warning'],
                                    \App\Models\LaporanKematian::STATUS_MENUNGGU_PERBAIKAN => ['Menunggu Perbaikan', 'badge-warning'],
                                    default => ['Status lainnya', 'badge-brand'],
                                };
                            @endphp
                            <tr>
                                <td style="color: var(--faint);">{{ $pengajuan->firstItem() + $index }}.</td>
                                <td class="num" style="font-weight: 500; color: var(--ink);">{{ $item->nik_almarhum }}</td>
                                <td style="font-weight: 600; color: var(--ink);">{{ $item->nama_almarhum }}</td>
                                <td style="color: var(--muted);">
                                    {{ $item->tanggal_pengajuan ? \Carbon\Carbon::parse($item->tanggal_pengajuan)->format('d/m/Y') : '-' }}
                                </td>
                                <td><span class="badge {{ $warnaStatus }}"><span class="badge-dot"></span>{{ $labelStatus }}</span></td>
                                <td class="c">
                                    @if ($item->status_pengajuan === \App\Models\LaporanKematian::STATUS_MENUNGGU_VERIFIKASI)
                                        <a href="{{ route('verifikasi.show', $item) }}" class="btn btn-brand btn-sm">Verifikasi</a>
                                    @else
                                        <span style="color: var(--faint);">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($pengajuan->hasPages())
                <div class="pager">{{ $pengajuan->links() }}</div>
            @endif
        @endif
    </section>
</div>
@endsection