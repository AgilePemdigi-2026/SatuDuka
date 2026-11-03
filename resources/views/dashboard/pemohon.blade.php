@extends('tata_letak.verifikator')

@section('judul', 'Dashboard Pemohon')

@section('subjudul')
    Selamat datang, {{ $pengguna->nama_lengkap }}.
    Anda masuk sebagai {{ $pengguna->labelPeran() }}.
    Layanan Pencatatan &amp; Administrasi Kematian Kota Yogyakarta.
@endsection

@section('aksi_header')
    <a href="{{ route('laporan.baru') }}" class="btn btn-brand btn-sm">Ajukan Laporan Baru</a>
@endsection

@section('konten')
    <section class="card" style="margin-top: 32px;">
        <div class="card-head">
            <div>
                <h2 class="card-title">Status Pengajuan</h2>
                <p class="card-desc">Ringkasan perkembangan seluruh pengajuan Anda.</p>
            </div>
        </div>

        <div class="card-body">
            <section class="stats">
                <div class="stat">
                    <p class="stat-label">Total Pengajuan</p>
                    <p class="stat-value">{{ $statistikPemohon['total'] }}</p>
                    <p class="stat-note">Semua pengajuan Anda</p>
                </div>
                <div class="stat">
                    <p class="stat-label">Menunggu Verifikasi</p>
                    <p class="stat-value">{{ $statistikPemohon['menunggu'] }}</p>
                    <p class="stat-note">Sedang diperiksa petugas</p>
                </div>
                <div class="stat">
                    <p class="stat-label">Perlu Perbaikan</p>
                    <p class="stat-value">{{ $statistikPemohon['perluPerbaikan'] }}</p>
                    <p class="stat-note">Perlu ditindaklanjuti</p>
                </div>
                <div class="stat">
                    <p class="stat-label">Selesai</p>
                    <p class="stat-value">{{ $statistikPemohon['selesai'] }}</p>
                    <p class="stat-note">Pengajuan selesai diproses</p>
                </div>
            </section>
        </div>
    </section>

    @if ($perluTindakan->isNotEmpty())
        <section class="card" style="margin-top: 32px;">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Perlu Tindakan Anda</h2>
                    <p class="card-desc">Lengkapi draf atau perbaiki pengajuan yang dikembalikan.</p>
                </div>
            </div>

            <div class="card-body">
                @include('dashboard.tabel_laporan', ['daftar' => $perluTindakan])
            </div>
        </section>
    @endif

    <section class="card" style="margin-top: 32px;">
        <div class="card-head dashboard-card-head">
            <div>
                <h2 class="card-title">Pengajuan Saya</h2>
                <p class="card-desc">Pengajuan yang sedang diproses atau sudah selesai.</p>
            </div>

            <form action="{{ route('pengajuan.status') }}" method="GET" class="search-form">
                <input
                    type="text"
                    name="nomor"
                    class="field search-input"
                    placeholder="Cari nomor pengajuan..."
                    aria-label="Cari berdasarkan nomor pengajuan"
                    value="{{ request('nomor') }}"
                >
                <button type="submit" class="btn btn-brand">Cari</button>
            </form>
        </div>

        <div class="card-body">
            @include('dashboard.tabel_laporan', ['daftar' => $pengajuanSaya])
        </div>
    </section>
@endsection
