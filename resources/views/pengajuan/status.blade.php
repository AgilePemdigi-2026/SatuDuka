@extends('tata_letak.verifikator')

@section('judul', 'Lacak Status Pengajuan')

@section('subjudul', 'Pantau perkembangan pengajuan laporan kematian dan unduh akta setelah diterbitkan.')

@section('aksi_header')
    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">Kembali ke Dashboard</a>
@endsection

@section('konten')
    <section class="card">
        <div class="card-head dashboard-card-head">
            <div>
                <h2 class="card-title">Daftar Pengajuan</h2>
                <p class="card-desc">Cari nomor pengajuan untuk melihat status terkininya.</p>
            </div>

            <form method="GET" action="{{ route('pengajuan.status') }}" class="search-form">
                <input
                    type="search"
                    name="nomor"
                    id="nomor"
                    class="field search-input"
                    aria-label="Nomor Pengajuan"
                    placeholder="Masukkan nomor pengajuan"
                    value="{{ $nomor }}"
                    @if ($galatNomor) aria-invalid="true" @endif
                >
                <button type="submit" class="btn btn-brand">Cari</button>
            </form>
        </div>

        @if ($galatNomor)
            <div class="alert alert-err">{{ $galatNomor }}</div>
        @endif

        @if ($galatCari)
            <div class="alert alert-err">{{ $galatCari }}</div>
        @endif

        @if ($daftar->isEmpty())
            <div class="empty">
                <p style="font-size: 14px; font-weight: 600; color: var(--ink);">Belum ada pengajuan</p>
                <p style="font-size: 12.5px; color: var(--faint); margin-top: 4px;">Pengajuan yang sudah dikirim akan muncul di sini.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Nama Almarhum</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftar as $laporan)
                            <tr>
                                <td>
                                    <a href="{{ route('pengajuan.detail', $laporan) }}" class="num" style="font-weight: 600; color: var(--brand);">
                                        {{ $laporan->no_pengajuan ?? '-' }}
                                    </a>
                                </td>
                                <td style="font-weight: 600; color: var(--ink);">{{ $laporan->nama_almarhum ?: '-' }}</td>
                                <td style="color: var(--muted);">
                                    {{ $laporan->tanggal_pengajuan?->translatedFormat('j F Y, H:i') ?? '-' }}
                                </td>
                                <td>
                                    @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($daftar->hasPages())
                <div class="pager">{{ $daftar->links() }}</div>
            @endif
        @endif
    </section>
@endsection
