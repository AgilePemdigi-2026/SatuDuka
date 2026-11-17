@extends('tata_letak.verifikator')

@section('judul', 'Dashboard Pejabat Penanda Tangan')

@section('subjudul')
    Selamat datang, {{ $pengguna->nama_lengkap }}.
    Anda masuk sebagai {{ $pengguna->labelPeran() }}.
    Antrean dokumen untuk ditandatangani elektronik.
@endsection

@section('konten')
    @php
        $totalDokumen = $statistik['menunggu'] + $statistik['selesai'] + $statistik['dikembalikan'];
    @endphp

    <section class="stats">
        <div class="stat stat-waiting">
            <p class="stat-label">Menunggu Tindakan</p>
            <p class="stat-value">{{ $statistik['menunggu'] }}</p>
        </div>

        <div class="stat stat-complete">
            <p class="stat-label">Selesai</p>
            <p class="stat-value">{{ $statistik['selesai'] }}</p>
        </div>

        <div class="stat stat-anomaly">
            <p class="stat-label">Dikembalikan</p>
            <p class="stat-value">{{ $statistik['dikembalikan'] }}</p>
        </div>

        <div class="stat" style="--stat-color: var(--brand);">
            <p class="stat-label">Total Dokumen</p>
            <p class="stat-value">{{ $totalDokumen }}</p>
        </div>
    </section>

    <section class="card" style="margin-top: 32px;">
        <div class="card-head dashboard-card-head">
            <div>
                <h2 class="card-title">Antrean Tanda Tangan</h2>
            </div>

            <a href="{{ route('tanda_tangan.index') }}" class="btn btn-outline">
                Buka Semua Dokumen
            </a>
        </div>

        @if ($dokumenMenunggu->isEmpty())
            <div class="empty">
                <div class="empty-ic" aria-hidden="true">✓</div>
                <h3 class="card-title">Tidak Ada Antrean</h3>
            </div>
        @else
            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nomor Pengajuan</th>
                            <th>Nama Almarhum/Almarhumah</th>
                            <th>Tanggal Pengajuan</th>
                            <th class="c">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($dokumenMenunggu as $index => $dokumen)
                            @php($laporan = $dokumen->laporanKematian)
                            <tr>
                                <td>{{ $index + 1 }}.</td>
                                <td class="num" style="font-weight: 600; color: var(--ink);">
                                    {{ $laporan?->no_pengajuan ?? '-' }}
                                </td>
                                <td style="font-weight: 600; color: var(--ink);">
                                    {{ $laporan?->nama_almarhum ?? '-' }}
                                </td>
                                <td class="num">
                                    {{ $laporan?->tanggal_pengajuan?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="c">
                                    @if ($laporan !== null)
                                        <a href="{{ route('tanda_tangan.show', $laporan) }}" class="btn btn-brand btn-sm">
                                            Periksa
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
