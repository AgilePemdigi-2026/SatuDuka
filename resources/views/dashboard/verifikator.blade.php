
@extends('tata_letak.verifikator')

@section('judul', 'Dashboard Verifikator')

@section('subjudul')
    Selamat datang, {{ $pengguna->nama_lengkap }}.
    Anda masuk sebagai {{ $pengguna->labelPeran() }}.
    Antrean verifikasi berkas pengajuan.
@endsection

@section('konten')

    {{-- Statistik Dashboard --}}
    <section class="stats">
        <div class="stat">
            <p class="stat-label">Menunggu Verifikasi</p>
            <p class="stat-value">{{ $statistik['menunggu'] }}</p>
            <p class="stat-note">Pengajuan menunggu pemeriksaan</p>
        </div>

        <div class="stat">
            <p class="stat-label">Dalam Rekonsiliasi</p>
            <p class="stat-value">{{ $statistik['rekonsiliasi'] }}</p>
            <p class="stat-note">Pengajuan dalam proses rekonsiliasi</p>
        </div>

        <div class="stat">
            <p class="stat-label">Selesai</p>
            <p class="stat-value">{{ $statistik['selesai'] }}</p>
            <p class="stat-note">Pengajuan telah selesai diproses</p>
        </div>

        <div class="stat">
            <p class="stat-label">Total Antrean</p>
            <p class="stat-value">
                {{ $statistik['menunggu'] + $statistik['rekonsiliasi'] }}
            </p>
            <p class="stat-note">Pengajuan yang membutuhkan tindak lanjut</p>
        </div>
    </section>

    {{-- Daftar Permohonan --}}
    <section class="card" style="margin-top: 32px;">
        <div class="card-head dashboard-card-head">
            <div>
                <h2 class="card-title">Daftar Permohonan</h2>
                <p class="card-desc">
                    Pilih berkas untuk melakukan pemeriksaan detail.
                </p>
            </div>

            <form action="{{ route('verifikasi.index') }}" method="GET"
                class="search-form">
                <input
                    type="text"
                    name="cari"
                    class="field search-input"
                    placeholder="Cari nomor, NIK, atau nama..."
                    value="{{ request('cari') }}"
                >

                <button type="submit" class="btn btn-brand">
                    Cari
                </button>
            </form>
        </div>

        @if ($antrean->isEmpty())
            <div class="empty">
                <div class="empty-ic">✓</div>
                <h3 class="card-title">Tidak Ada Antrean</h3>
                <p class="card-desc" style="margin-top: 8px;">
                    Belum ada pengajuan yang membutuhkan pemeriksaan.
                </p>
            </div>
        @else
            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Berkas</th>
                            <th class="c">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($antrean as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}.</td>

                                <td class="num">
                                    {{ $item->nik_almarhum }}
                                </td>

                                <td style="font-weight: 600; color: var(--ink);">
                                    {{ $item->nama_almarhum }}
                                </td>

                                <td class="num">
                                    {{ $item->tanggal_pengajuan?->format('d/m/Y') ?? '-' }}
                                </td>

                                <td>
                                    {{ $item->berkasPendukung->count() }} Berkas
                                </td>

                                <td class="c">
                                    <a
                                        href="{{ route('verifikasi.show', $item) }}"
                                        class="btn btn-brand btn-sm"
                                    >
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Tombol menuju daftar verifikasi --}}
    <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
        <a href="{{ route('verifikasi.index') }}" class="btn btn-outline">
            Lihat Semua Pengajuan
        </a>
    </div>

@endsection
