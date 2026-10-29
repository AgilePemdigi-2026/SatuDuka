```blade
@extends('tata_letak.verifikator')

@section('judul', 'Detail Verifikasi Berkas')
@section('subjudul', 'Periksa data pemohon dan kelengkapan dokumen sebelum menentukan keputusan verifikasi.')
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
        <a href="{{ route('verifikasi.index') }}" class="btn btn-outline btn-sm">
            <span aria-hidden="true">←</span> Kembali ke Antrean
        </a>

        <span class="badge badge-brand" style="padding: 8px 16px;">
            No. Pengajuan: {{ $laporan->no_pengajuan }}
        </span>
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

    {{-- Informasi Pengajuan --}}
    <section class="card">
        <div class="card-head">
            <h2 class="card-title">Informasi Pemohon &amp; Almarhum</h2>
        </div>

        <div class="card-body">
            <dl class="info-grid" style="margin: 0;">
                <div class="info-item">
                    <dt>Nama Almarhum</dt>
                    <dd>{{ $laporan->nama_almarhum }}</dd>
                </div>

                <div class="info-item">
                    <dt>NIK Almarhum</dt>
                    <dd class="num">{{ $laporan->nik_almarhum }}</dd>
                </div>

                <div class="info-item">
                    <dt>Nama Pelapor</dt>
                    <dd>{{ $laporan->nama_pelapor }}</dd>
                </div>

                <div class="info-item">
                    <dt>Hubungan Pelapor</dt>
                    <dd>{{ $laporan->hubungan_pelapor }}</dd>
                </div>

                <div class="info-item">
                    <dt>Tanggal Kematian</dt>
                    <dd>{{ $laporan->tanggal_kematian?->format('d/m/Y') ?? '-' }}</dd>
                </div>

                <div class="info-item">
                    <dt>Tempat Kematian</dt>
                    <dd>{{ $laporan->tempat_kematian ?: '-' }}</dd>
                </div>
            </dl>
        </div>
    </section>

    {{-- Pemeriksaan Dokumen --}}
    <section class="card">
        <div class="card-head">
            <h2 class="card-title">Pemeriksaan Kelengkapan Dokumen</h2>

            <span class="badge {{ $jumlahTersedia === $jumlahWajib ? 'badge-ok' : 'badge-brand' }}">
                {{ $jumlahTersedia }} / {{ $jumlahWajib }} lengkap
            </span>
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
                            <span class="doc-ic {{ $berkas ? 'ok' : 'bad' }}">
                                {{ $berkas ? '✓' : '!' }}
                            </span>

                            <div style="min-width: 0;">
                                <p class="doc-name">{{ $jenis }}</p>

                                @if ($berkas)
                                    @php
                                        $ekstensi = strtolower(pathinfo($berkas->path_file, PATHINFO_EXTENSION));

                                        $tipe = $ekstensi === 'pdf'
                                            ? 'pdf'
                                            : (in_array($ekstensi, ['png', 'jpg', 'jpeg'], true)
                                                ? 'gambar'
                                                : 'lain');
                                    @endphp

                                    <button
                                        type="button"
                                        class="doc-file doc-file-btn js-pratinjau"
                                        title="Klik untuk pratinjau"
                                        data-nama="{{ $berkas->nama_file }}"
                                        data-tipe="{{ $tipe }}"
                                        data-pratinjau="{{ route('verifikasi.pratinjau', [$laporan, $berkas]) }}"
                                        data-unduh="{{ route('verifikasi.unduh', [$laporan, $berkas]) }}"
                                    >
                                        {{ $berkas->nama_file }}
                                    </button>
                                @else
                                    <p class="doc-file">Tidak ditemukan</p>
                                @endif
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

            @if (!empty($dokumenKurang))
                <div class="alert alert-warn" style="margin-top: 16px;">
                    <strong>Dokumen belum lengkap.</strong>
                    Pengajuan belum dapat disetujui sebelum seluruh dokumen wajib tersedia.
                </div>
            @endif
        </div>
    </section>

    {{-- Keputusan Verifikasi --}}
    <section class="card">
        <div class="card-head">
            <h2 class="card-title">Keputusan Verifikasi</h2>
        </div>

        <div class="card-body">
            <div class="decision-grid">

                {{-- Form Persetujuan --}}
                <form
                    method="POST"
                    action="{{ route('verifikasi.setujui', $laporan) }}"
                    class="decision-box"
                >
                    @csrf

                    <div>
                        <h3>Setujui Berkas</h3>

                        <p class="hint">
                            Gunakan jika seluruh dokumen wajib tersedia dan hasil pemeriksaan berkas dinyatakan sesuai.
                        </p>

                        <label for="catatan" class="field-label">
                            Catatan Persetujuan (Opsional)
                        </label>

                        <textarea
                            id="catatan"
                            name="catatan"
                            rows="3"
                            class="field"
                            placeholder="Tambahkan catatan jika ada..."
                        >{{ old('catatan') }}</textarea>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-green btn-block"
                        {{ $jumlahTersedia !== $jumlahWajib ? 'disabled' : '' }}
                    >
                        Setujui Berkas
                    </button>
                </form>

                {{-- Form Anomali --}}
                <form
                    method="POST"
                    action="{{ route('verifikasi.anomali', $laporan) }}"
                    class="decision-box"
                >
                    @csrf

                    <div>
                        <h3>Tandai Kasus Anomali</h3>

                        <p class="hint">
                            Alihkan pengajuan ke rekonsiliasi jika ditemukan ketidaksesuaian data atau masalah pada dokumen.
                        </p>

                        <div class="form-stack">
                            <div>
                                <label for="jenis_anomali" class="field-label">
                                    Jenis Anomali
                                </label>

                                <select
                                    id="jenis_anomali"
                                    name="jenis_anomali"
                                    required
                                    class="field"
                                >
                                    <option value="">Pilih Jenis Anomali</option>

                                    <option
                                        value="Perbedaan Identitas"
                                        @selected(old('jenis_anomali') === 'Perbedaan Identitas')
                                    >
                                        Perbedaan Identitas
                                    </option>

                                    <option
                                        value="NIK Ganda"
                                        @selected(old('jenis_anomali') === 'NIK Ganda')
                                    >
                                        NIK Ganda
                                    </option>

                                    <option
                                        value="Dokumen Tidak Terbaca"
                                        @selected(old('jenis_anomali') === 'Dokumen Tidak Terbaca')
                                    >
                                        Dokumen Tidak Terbaca
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="deskripsi" class="field-label">
                                    Deskripsi
                                </label>

                                <textarea
                                    id="deskripsi"
                                    name="deskripsi"
                                    rows="3"
                                    required
                                    class="field"
                                    placeholder="Jelaskan ketidaksesuaian..."
                                >{{ old('deskripsi') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand btn-block">
                        Dialihkan ke Rekonsiliasi
                    </button>
                </form>

            </div>
        </div>
    </section>
</div>

{{-- Modal Pratinjau Berkas --}}
<dialog id="dlg-pratinjau" class="dlg">
    <div class="dlg-box">
        <div class="dlg-head">
            <strong id="dlg-judul" class="dlg-judul"></strong>

            <div class="dlg-aksi">
                <a id="dlg-unduh" href="#" class="btn btn-brand btn-sm">
                    Unduh
                </a>

                <button
                    type="button"
                    id="dlg-tutup"
                    class="btn btn-outline btn-sm"
                >
                    Tutup
                </button>
            </div>
        </div>

        <div id="dlg-isi" class="dlg-isi"></div>
    </div>
</dialog>

<style>
    .doc-file-btn {
        background: none;
        border: 0;
        padding: 0;
        font-family: inherit;
        cursor: pointer;
        color: var(--brand);
        text-align: left;
        max-width: 100%;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .doc-file-btn:hover {
        color: var(--brand-dark);
    }

    .dlg {
        margin: auto;
        border: 0;
        border-radius: 18px;
        padding: 0;
        width: min(960px, 94vw);
        height: 88vh;
        box-shadow: 0 24px 60px -12px rgba(15, 23, 42, .45);
    }

    .dlg::backdrop {
        background: rgba(15, 23, 42, .55);
    }

    .dlg-box {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        background: #fff;
    }

    .dlg-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 20px;
        border-bottom: 1px solid var(--line);
    }

    .dlg-judul {
        font-size: 14px;
        color: var(--ink);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dlg-aksi {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .dlg-isi {
        flex: 1;
        min-height: 0;
        background: #F1F5F9;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
    }

    .dlg-isi iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }

    .dlg-isi img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .dlg-pesan {
        padding: 24px;
        font-size: 14px;
        color: var(--muted);
        text-align: center;
    }

    .decision-box button:disabled {
        cursor: not-allowed;
        opacity: .55;
    }
</style>

<script>
    (function () {
        var dlg = document.getElementById('dlg-pratinjau');
        var judul = document.getElementById('dlg-judul');
        var unduh = document.getElementById('dlg-unduh');
        var isi = document.getElementById('dlg-isi');

        function pesan(teks) {
            var p = document.createElement('p');
            p.className = 'dlg-pesan';
            p.textContent = teks;
            isi.appendChild(p);
        }

        document.querySelectorAll('.js-pratinjau').forEach(function (tombol) {
            tombol.addEventListener('click', function () {
                var tipe = tombol.dataset.tipe;

                judul.textContent = tombol.dataset.nama;
                unduh.href = tombol.dataset.unduh;
                isi.innerHTML = '';

                if (tipe === 'pdf') {
                    var f = document.createElement('iframe');
                    f.src = tombol.dataset.pratinjau;
                    f.title = 'Pratinjau ' + tombol.dataset.nama;
                    isi.appendChild(f);
                } else if (tipe === 'gambar') {
                    var g = document.createElement('img');
                    g.src = tombol.dataset.pratinjau;
                    g.alt = tombol.dataset.nama;
                    isi.appendChild(g);
                } else {
                    pesan('Jenis berkas ini tidak bisa dipratinjau. Gunakan tombol Unduh.');
                }

                dlg.showModal();
            });
        });

        document.getElementById('dlg-tutup').addEventListener('click', function () {
            dlg.close();
        });

        dlg.addEventListener('click', function (e) {
            if (e.target === dlg) {
                dlg.close();
            }
        });

        dlg.addEventListener('close', function () {
            isi.innerHTML = '';
        });
    })();
</script>
@endsection
```