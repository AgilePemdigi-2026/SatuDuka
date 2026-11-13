@extends('tata_letak.verifikator')

@section('judul', 'Detail Verifikasi Berkas')
@section('subjudul', 'Periksa data pemohon, kelengkapan dokumen, dan kesesuaian SIAK sebelum memutuskan.')
@section('tanpa_error_global', '1')
@section('aksi_header')
    <a href="{{ route('verifikasi.index') }}" class="btn btn-outline btn-sm">
        <span aria-hidden="true">←</span> Kembali
    </a>
@endsection

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
        <div class="card-head">
            <h2 class="card-title">Informasi Pemohon &amp; Almarhum</h2>
            <span class="badge badge-brand" style="padding: 8px 16px;">
                No. Pengajuan: {{ $laporan->no_pengajuan }}
            </span>
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

    <!-- Pemeriksaan Dokumen -->
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
                                    >{{ $berkas->nama_file }}</button>
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
        </div>
    </section>

    <!-- Pencocokan SIAK -->
    <section class="card">
        <div class="card-head">
            <h2 class="card-title">Pencocokan Data SIAK</h2>
        </div>

        <div class="card-body">
            @if ($hasilPencocokan['ditemukan_di_siak'])
                <div class="info-grid">
                    <div class="match-row">
                        <span>Kesesuaian NIK</span>
                        <span class="badge {{ $hasilPencocokan['nik_sesuai'] ? 'badge-ok' : 'badge-bad' }}">
                            {{ $hasilPencocokan['nik_sesuai'] ? 'Sesuai' : 'Tidak Sesuai' }}
                        </span>
                    </div>

                    <div class="match-row">
                        <span>Kesesuaian Nama</span>
                        <span class="badge {{ $hasilPencocokan['nama_sesuai'] ? 'badge-ok' : 'badge-bad' }}">
                            {{ $hasilPencocokan['nama_sesuai'] ? 'Sesuai' : 'Tidak Sesuai' }}
                        </span>
                    </div>
                </div>
            @else
                <div class="alert alert-warn">
                    Data tidak ditemukan pada SIAK dummy. Lakukan pemeriksaan manual.
                </div>
            @endif
        </div>
    </section>

    <!-- Keputusan Verifikasi -->
    <section class="card">
        <div class="card-head decision-header">
            <h2 class="card-title">Keputusan Verifikasi</h2>
        </div>

        <div class="card-body">
            <form
                id="form-keputusan"
                method="POST"
                action="{{ route('verifikasi.setujui', $laporan) }}"
                data-action-setujui="{{ route('verifikasi.setujui', $laporan) }}"
                data-action-tolak="{{ route('verifikasi.revisi', $laporan) }}"
                data-action-anomali="{{ route('verifikasi.anomali', $laporan) }}"
                class="decision-box"
            >
                @csrf

                <label for="pilihan-keputusan" class="decision-selector">
                    <span class="field-label">Pilihan Keputusan</span>
                    <select id="pilihan-keputusan" name="keputusan" class="field">
                        <option value="setujui" @selected(old('keputusan', 'setujui') === 'setujui')>Setujui Berkas</option>
                        <option value="tolak" @selected(old('keputusan') === 'tolak')>Tolak Berkas</option>
                        <option value="anomali" @selected(old('keputusan') === 'anomali')>Alihkan ke Rekonsiliasi</option>
                    </select>
                </label>

                <div id="kolom-setujui" data-decision-fields>
                    <h3>Setujui Berkas</h3>

                    <p class="hint">
                        Gunakan jika persyaratan dan data sudah sesuai sepenuhnya.
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

                <div id="kolom-tolak" data-decision-fields hidden>
                    <h3>Tolak Berkas</h3>

                    <p class="hint">
                        Jelaskan alasan penolakan agar pemohon mengetahui berkas yang perlu diperbaiki.
                    </p>

                    <label for="alasan_penolakan" class="field-label">
                        Alasan Penolakan
                    </label>

                    <textarea
                        id="alasan_penolakan"
                        name="catatan"
                        rows="3"
                        required
                        minlength="5"
                        maxlength="2000"
                        disabled
                        class="field"
                        placeholder="Tuliskan alasan penolakan..."
                    >{{ old('catatan') }}</textarea>
                </div>

                <div id="kolom-anomali" data-decision-fields hidden>
                    <h3>Tandai Kasus Anomali</h3>

                    <p class="hint">
                        Alihkan ke rekonsiliasi jika ditemukan ketidaksesuaian data.
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
                                disabled
                                class="field"
                            >
                                <option value="">Pilih Jenis Anomali</option>
                                <option value="Perbedaan Identitas" @selected(old('jenis_anomali') === 'Perbedaan Identitas')>Perbedaan Identitas</option>
                                <option value="NIK Ganda" @selected(old('jenis_anomali') === 'NIK Ganda')>NIK Ganda</option>
                                <option value="Dokumen Tidak Terbaca" @selected(old('jenis_anomali') === 'Dokumen Tidak Terbaca')>Dokumen Tidak Terbaca</option>
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
                                disabled
                                class="field"
                                placeholder="Jelaskan ketidaksesuaian..."
                            >{{ old('deskripsi') }}</textarea>
                        </div>
                    </div>
                </div>

                <button
                    id="tombol-keputusan"
                    type="submit"
                    class="btn btn-green btn-block"
                    {{ $jumlahTersedia !== $jumlahWajib ? 'disabled' : '' }}
                >
                    Setujui Berkas
                </button>
            </form>
        </div>
    </section>
</div>

<!-- Modal Pratinjau Berkas -->
<dialog id="dlg-pratinjau" class="dlg">
    <div class="dlg-box">
        <div class="dlg-head">
            <strong id="dlg-judul" class="dlg-judul"></strong>

            <div class="dlg-aksi">
                <a id="dlg-unduh" href="#" class="btn btn-brand btn-sm">Unduh</a>
                <button type="button" id="dlg-tutup" class="btn btn-outline btn-sm">Tutup</button>
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
        box-shadow: 0 24px 60px -12px rgba(15,23,42,.45);
    }

    .dlg::backdrop {
        background: rgba(15,23,42,.55);
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
    .decision-selector { display: block; width: min(250px, 100%); }
    .decision-selector .field-label { margin-bottom: 6px; }
    .decision-box > [data-decision-fields] > h3 { font-size: 15px; font-weight: 700; color: var(--ink); }
    .decision-box > [data-decision-fields] > .hint { font-size: 12.5px; color: var(--muted); margin: 4px 0 16px; }

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

        var pilihanKeputusan = document.getElementById('pilihan-keputusan');
        var formKeputusan = document.getElementById('form-keputusan');
        var tombolKeputusan = document.getElementById('tombol-keputusan');
        var konfigurasiKeputusan = {
            setujui: {
                action: formKeputusan.dataset.actionSetujui,
                bagian: 'kolom-setujui',
                label: 'Setujui Berkas',
                warna: 'btn-green',
                disabled: {{ $jumlahTersedia !== $jumlahWajib ? 'true' : 'false' }}
            },
            tolak: {
                action: formKeputusan.dataset.actionTolak,
                bagian: 'kolom-tolak',
                label: 'Tolak Berkas',
                warna: 'btn-red',
                disabled: false
            },
            anomali: {
                action: formKeputusan.dataset.actionAnomali,
                bagian: 'kolom-anomali',
                label: 'Alihkan ke Rekonsiliasi',
                warna: 'btn-brand',
                disabled: false
            }
        };

        function perbaruiKeputusan() {
            var keputusan = konfigurasiKeputusan[pilihanKeputusan.value];
            formKeputusan.action = keputusan.action;
            tombolKeputusan.textContent = keputusan.label;
            tombolKeputusan.className = 'btn ' + keputusan.warna + ' btn-block';
            tombolKeputusan.disabled = keputusan.disabled;

            document.querySelectorAll('[data-decision-fields]').forEach(function (bagian) {
                var aktif = bagian.id === keputusan.bagian;
                bagian.hidden = !aktif;
                bagian.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !aktif;
                });
            });
        }

        pilihanKeputusan.addEventListener('change', perbaruiKeputusan);
        perbaruiKeputusan();

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