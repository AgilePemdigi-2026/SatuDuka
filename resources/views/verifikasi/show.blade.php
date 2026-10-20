@extends('tata_letak.verifikator')

@section('judul', 'Detail Verifikasi Berkas')
@section('subjudul', 'Periksa data pemohon, kelengkapan dokumen, dan kesesuaian SIAK sebelum memutuskan.')
@section('tanpa_error_global', '1')

@section('konten')
@php
    $jumlahWajib = count($dokumenWajib);
    $jumlahTersedia = collect($dokumenWajib)->filter(fn ($jenis) =>
        $laporan->berkasPendukung->contains(fn ($item) =>
            $item->jenis_dokumen === $jenis
            && $item->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL
        )
    )->count();
@endphp

<div class="stack page-narrow">

    <div class="toolbar">
        <a href="{{ route('verifikasi.index') }}" class="btn btn-outline btn-sm"><span aria-hidden="true">←</span> Kembali ke Antrean</a>
        <span class="badge badge-brand" style="padding: 8px 16px;">No. Pengajuan: {{ $laporan->no_pengajuan }}</span>
    </div>

    @if ($errors->any())
        <div role="alert" class="alert alert-err" style="margin: 0;">
            <strong>Periksa kembali pengajuan ini.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Informasi Pengajuan -->
    <section class="card">
        <div class="card-head"><h2 class="card-title">Informasi Pemohon &amp; Almarhum</h2></div>
        <div class="card-body">
            <dl class="info-grid" style="margin: 0;">
                <div class="info-item"><dt>Nama Almarhum</dt><dd>{{ $laporan->nama_almarhum }}</dd></div>
                <div class="info-item"><dt>NIK Almarhum</dt><dd class="num">{{ $laporan->nik_almarhum }}</dd></div>
                <div class="info-item"><dt>Nama Pelapor</dt><dd>{{ $laporan->nama_pelapor }}</dd></div>
                <div class="info-item"><dt>Hubungan Pelapor</dt><dd>{{ $laporan->hubungan_pelapor }}</dd></div>
                <div class="info-item"><dt>Tanggal Kematian</dt><dd>{{ $laporan->tanggal_kematian?->format('d/m/Y') ?? '-' }}</dd></div>
                <div class="info-item"><dt>Tempat Kematian</dt><dd>{{ $laporan->tempat_kematian ?: '-' }}</dd></div>
            </dl>
        </div>
    </section>

    <!-- Pemeriksaan Dokumen -->
    <section class="card">
        <div class="card-head">
            <h2 class="card-title">Pemeriksaan Kelengkapan Dokumen</h2>
            <span class="badge {{ $jumlahTersedia === $jumlahWajib ? 'badge-ok' : 'badge-brand' }}">{{ $jumlahTersedia }} / {{ $jumlahWajib }} lengkap</span>
        </div>
        <div class="card-body">
            <div class="doc-list">
                @foreach ($dokumenWajib as $jenis)
                    @php
                        $berkas = $laporan->berkasPendukung->first(fn ($item) =>
                            $item->jenis_dokumen === $jenis
                            && $item->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL
                        );
                    @endphp
                    <div class="doc-item">
                        <div class="doc-left">
                            <span class="doc-ic {{ $berkas ? 'ok' : 'bad' }}">{{ $berkas ? '✓' : '!' }}</span>
                            <div style="min-width: 0;">
                                <p class="doc-name">{{ $jenis }}</p>
                                <p class="doc-file">{{ $berkas->nama_file ?? 'Tidak ditemukan' }}</p>
                            </div>
                        </div>
                        @if ($berkas)
                            <span class="badge badge-ok">Tersedia</span>
                        @else
                            <span class="badge badge-bad">Belum Lengkap</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pencocokan SIAK -->
    <section class="card">
        <div class="card-head"><h2 class="card-title">Pencocokan Data SIAK</h2></div>
        <div class="card-body">
            @if ($hasilPencocokan['ditemukan_di_siak'])
                <div class="info-grid">
                    <div class="match-row">
                        <span>Kesesuaian NIK</span>
                        <span class="badge {{ $hasilPencocokan['nik_sesuai'] ? 'badge-ok' : 'badge-bad' }}">{{ $hasilPencocokan['nik_sesuai'] ? 'Sesuai' : 'Tidak Sesuai' }}</span>
                    </div>
                    <div class="match-row">
                        <span>Kesesuaian Nama</span>
                        <span class="badge {{ $hasilPencocokan['nama_sesuai'] ? 'badge-ok' : 'badge-bad' }}">{{ $hasilPencocokan['nama_sesuai'] ? 'Sesuai' : 'Tidak Sesuai' }}</span>
                    </div>
                </div>
            @else
                <div class="alert alert-warn">Data tidak ditemukan pada SIAK dummy. Lakukan pemeriksaan manual.</div>
            @endif
        </div>
    </section>

    <!-- Keputusan Verifikasi -->
    <section class="card">
        <div class="card-head"><h2 class="card-title">Keputusan Verifikasi</h2></div>
        <div class="card-body">
            <div class="decision-grid">

                <form method="POST" action="{{ route('verifikasi.setujui', $laporan) }}" class="decision-box">
                    @csrf
                    <div>
                        <h3>Setujui Berkas</h3>
                        <p class="hint">Gunakan jika persyaratan dan data sudah sesuai sepenuhnya.</p>
                        <label for="catatan" class="field-label">Catatan Persetujuan (Opsional)</label>
                        <textarea id="catatan" name="catatan" rows="3" class="field" placeholder="Tambahkan catatan jika ada..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-green btn-block">Setujui Berkas</button>
                </form>

                <form method="POST" action="{{ route('verifikasi.anomali', $laporan) }}" class="decision-box">
                    @csrf
                    <div>
                        <h3>Tandai Kasus Anomali</h3>
                        <p class="hint">Alihkan ke rekonsiliasi jika ditemukan ketidaksesuaian data.</p>
                        <div class="form-stack">
                            <div>
                                <label for="jenis_anomali" class="field-label">Jenis Anomali</label>
                                <select id="jenis_anomali" name="jenis_anomali" required class="field">
                                    <option value="">Pilih Jenis Anomali</option>
                                    <option value="Perbedaan Identitas">Perbedaan Identitas</option>
                                    <option value="NIK Ganda">NIK Ganda</option>
                                    <option value="Dokumen Tidak Terbaca">Dokumen Tidak Terbaca</option>
                                </select>
                            </div>
                            <div>
                                <label for="deskripsi" class="field-label">Deskripsi</label>
                                <textarea id="deskripsi" name="deskripsi" rows="3" required class="field" placeholder="Jelaskan ketidaksesuaian..."></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-brand btn-block">Dialihkan ke Rekonsiliasi</button>
                </form>

            </div>
        </div>
    </section>
</div>
@endsection