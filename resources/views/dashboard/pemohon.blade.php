
@extends('tata_letak.verifikator')

@section('judul', 'Dashboard Pemohon')

@section('subjudul')
    Selamat datang, {{ $pengguna->nama_lengkap }}.
    Anda masuk sebagai {{ $pengguna->labelPeran() }}.
    Layanan Pencatatan &amp; Administrasi Kematian Kota Yogyakarta.
@endsection

@section('aksi_header')
    <a href="{{ route('laporan.baru') }}"
        class="btn btn-brand"
        style="flex-shrink: 0; white-space: nowrap; min-height: 44px; padding: 10px 20px; font-size: 13px; border-radius: 12px; box-shadow: 0 4px 12px rgba(30, 58, 95, .12);">
        <span aria-hidden="true" style="font-size: 19px; line-height: 1; font-weight: 500;">+</span>
        <span>Ajukan Laporan Baru</span>
    </a>
@endsection

@section('konten')

    <style>
        /*
        |--------------------------------------------------------------------------
        | Warna KPI Dashboard Pemohon
        |--------------------------------------------------------------------------
        | Posisi, ukuran, dan struktur KPI tetap mengikuti layout asli.
        | Warna disesuaikan dengan badge status pengajuan.
        */

        /* Total Pengajuan - Navy */
        .stat--total {
            background: #ffffff;
            border-color: #dbe4ef;
        }

        .stat--total::before {
            background: #1e3a5f;
        }

        .stat--total .stat-label,
        .stat--total .stat-value {
            color: #1e3a5f;
        }

        /* Menunggu Verifikasi - Biru */
        .stat--menunggu {
            background: #eff8ff;
            border-color: #bae6fd;
        }

        .stat--menunggu::before {
            background: #0284c7;
        }

        .stat--menunggu .stat-label,
        .stat--menunggu .stat-value {
            color: #0369a1;
        }

        /* Perlu Perbaikan - Oranye */
        .stat--perbaikan {
            background: #fffbeb;
            border-color: #fcd34d;
        }

        .stat--perbaikan::before {
            background: #f59e0b;
        }

        .stat--perbaikan .stat-label,
        .stat--perbaikan .stat-value {
            color: #b45309;
        }

        /* Selesai - Hijau */
        .stat--selesai {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .stat--selesai::before {
            background: #16a34a;
        }

        .stat--selesai .stat-label,
        .stat--selesai .stat-value {
            color: #15803d;
        }
    </style>

    {{-- Status Pengajuan --}}
    <section class="card" style="margin-top: 32px;">
        <div class="card-head">
            <div>
                <h2 class="card-title">Status Pengajuan</h2>
                <p class="card-desc">
                    Ringkasan perkembangan seluruh pengajuan Anda.
                </p>
            </div>
        </div>

        <div class="card-body">
            <section class="stats">

                {{-- KPI 1: Total Pengajuan --}}
                <div class="stat stat--total">
                    <p class="stat-label">
                        Total Pengajuan
                    </p>

                    <p class="stat-value">
                        {{ $statistikPemohon['total'] }}
                    </p>

                    <p class="stat-note">
                        Semua pengajuan Anda
                    </p>
                </div>

                {{-- KPI 2: Menunggu Verifikasi --}}
                <div class="stat stat--menunggu">
                    <p class="stat-label">
                        Menunggu Verifikasi
                    </p>

                    <p class="stat-value">
                        {{ $statistikPemohon['menunggu'] }}
                    </p>

                    <p class="stat-note">
                        Sedang diperiksa petugas
                    </p>
                </div>

                {{-- KPI 3: Perlu Perbaikan --}}
                <div class="stat stat--perbaikan">
                    <p class="stat-label">
                        Perlu Perbaikan
                    </p>

                    <p class="stat-value">
                        {{ $statistikPemohon['perluPerbaikan'] }}
                    </p>

                    <p class="stat-note">
                        Perlu ditindaklanjuti
                    </p>
                </div>

                {{-- KPI 4: Selesai --}}
                <div class="stat stat--selesai">
                    <p class="stat-label">
                        Selesai
                    </p>

                    <p class="stat-value">
                        {{ $statistikPemohon['selesai'] }}
                    </p>

                    <p class="stat-note">
                        Pengajuan selesai diproses
                    </p>
                </div>

            </section>
        </div>
    </section>

    {{-- Perlu Tindakan Anda --}}
    @if ($perluTindakan->isNotEmpty())
        <section class="card" style="margin-top: 32px;">
            <div class="card-head">
                <div>
                    <h2 class="card-title">
                        Perlu Tindakan Anda
                    </h2>

                    <p class="card-desc">
                        Lengkapi draf atau perbaiki pengajuan yang dikembalikan.
                    </p>
                </div>
            </div>

            <div class="card-body">
                @include('dashboard.tabel_laporan', [
                    'daftar' => $perluTindakan
                ])
            </div>
        </section>
    @endif

    {{-- Pengajuan Saya --}}
    <section class="card" style="margin-top: 32px;">
        <div class="card-head dashboard-card-head">
            <div>
                <h2 class="card-title">
                    Pengajuan Saya
                </h2>

                <p class="card-desc">
                    Pengajuan yang sedang diproses atau sudah selesai.
                </p>
            </div>

            {{-- Form Pencarian --}}
            <form
                action="{{ route('pengajuan.status') }}"
                method="GET"
                class="search-form"
            >
                <input
                    type="text"
                    name="nomor"
                    class="field search-input"
                    placeholder="Cari nomor pengajuan..."
                    aria-label="Cari berdasarkan nomor pengajuan"
                    value="{{ request('nomor') }}"
                >

                <button
                    type="submit"
                    class="btn btn-brand"
                >
                    Cari
                </button>
            </form>
        </div>

        <div class="card-body">
            @include('dashboard.tabel_laporan', [
                'daftar' => $pengajuanSaya
            ])
        </div>
    </section>

@endsection