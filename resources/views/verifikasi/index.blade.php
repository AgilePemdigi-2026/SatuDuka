@extends('tata_letak.verifikator')

@section('judul', 'Dashboard Verifikator')
@section('subjudul', 'Ringkasan seluruh proses verifikasi dan rekonsiliasi')

@section('konten')
<div class="stack">

    <!-- Kartu Statistik -->
    <section class="stats">
        <div class="stat">
            <p class="stat-label">Total Antrean</p>
            <p class="stat-value">{{ $pengajuan->total() }}</p>
            <p class="stat-note">Pengajuan menunggu pemeriksaan</p>
        </div>
        <div class="stat">
            <p class="stat-label">Menunggu Verifikasi</p>
            <p class="stat-value">{{ $pengajuan->total() }}</p>
            <p class="stat-note">Sesuai antrean aktif</p>
        </div>
        <div class="stat">
            <p class="stat-label">Hasil Pencarian</p>
            <p class="stat-value">{{ $pengajuan->count() }}</p>
            <p class="stat-note">Ditampilkan pada halaman ini</p>
        </div>
        <div class="stat">
            <p class="stat-label">Halaman</p>
            <p class="stat-value">{{ $pengajuan->currentPage() }}</p>
            <p class="stat-note">Dari total {{ $pengajuan->lastPage() }} halaman</p>
        </div>
    </section>

    <!-- Tabel Permohonan -->
    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Daftar Permohonan</h2>
                <p class="card-desc">Pilih berkas untuk melakukan pemeriksaan detail.</p>
            </div>

            <form method="GET" action="{{ route('verifikasi.index') }}" class="search-form">
                <input id="cari" name="cari" type="search" value="{{ request('cari') }}"
                       placeholder="Cari NIK atau Nama..." class="field search-input">
                <button type="submit" class="btn btn-brand btn-pill btn-sm" style="padding: 10px 24px;">Cari</button>
                @if (request('cari'))
                    <a href="{{ route('verifikasi.index') }}" class="btn btn-outline btn-pill btn-sm" style="padding: 10px 20px;">Reset</a>
                @endif
            </form>
        </div>

        @if ($pengajuan->isEmpty())
            <div class="empty">
                <div class="empty-ic">✓</div>
                <p style="font-size: 14px; font-weight: 600; color: var(--ink);">Tidak ada berkas yang menunggu verifikasi</p>
                <p style="font-size: 12.5px; color: var(--faint); margin-top: 4px;">Semua pengajuan sudah diperiksa.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No.</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Berkas</th>
                            <th>Status</th>
                            <th class="c">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pengajuan as $index => $item)
                            <tr>
                                <td style="color: var(--faint);">{{ $pengajuan->firstItem() + $index }}.</td>
                                <td class="num" style="font-weight: 500; color: var(--ink);">{{ $item->nik_almarhum }}</td>
                                <td style="font-weight: 600; color: var(--ink);">{{ $item->nama_almarhum }}</td>
                                <td style="color: var(--muted);">
                                    {{ $item->tanggal_pengajuan ? \Carbon\Carbon::parse($item->tanggal_pengajuan)->format('d/m/Y') : '-' }}
                                </td>
                                <td>{{ $item->berkas_pendukung_count }} Berkas</td>
                                <td><span class="badge badge-brand"><span class="badge-dot"></span>Menunggu</span></td>
                                <td class="c">
                                    <a href="{{ route('verifikasi.show', $item) }}" class="btn btn-brand btn-sm">Verifikasi</a>
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