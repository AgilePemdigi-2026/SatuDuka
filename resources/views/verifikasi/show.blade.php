@extends('tata_letak.utama')

@section('judul', 'Detail Verifikasi Berkas')

@section('kembali')
    <a href="{{ route('verifikasi.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-brand">
        <x-icon.arrow-left class="h-4 w-4" />
        Kembali ke Antrean
    </a>
@endsection

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    @php
        $kelasLabel = 'mb-1.5 block text-sm font-semibold text-ink-soft';
        $kelasArea = 'block w-full rounded-xl border border-line-strong bg-surface px-3.5 py-3 text-sm text-ink outline-none transition placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30';
        $opsiAnomali = [
            \App\Models\AnomaliDokumen::JENIS_PERBEDAAN_IDENTITAS => 'Perbedaan Identitas',
            \App\Models\AnomaliDokumen::JENIS_NIK_GANDA => 'NIK Ganda',
            \App\Models\AnomaliDokumen::JENIS_TANGGAL_KEMATIAN => 'Perbedaan Tanggal Kematian',
            \App\Models\AnomaliDokumen::JENIS_TEMPAT_KEMATIAN => 'Perbedaan Tempat Kematian',
            \App\Models\AnomaliDokumen::JENIS_DOKUMEN_TIDAK_TERBACA => 'Dokumen Tidak Terbaca',
        ];
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <x-ui.page-header :title="$laporan->nama_almarhum ?: 'Detail Verifikasi Berkas'"
            :subtitle="'Nomor Pengajuan: '.($laporan->no_pengajuan ?? '-')">
            <x-slot:aksi>
                @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
            </x-slot:aksi>
        </x-ui.page-header>

        <x-ui.card title="Informasi Pemohon &amp; Almarhum">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-line bg-canvas p-4">
                    <dt class="text-xs text-muted">Nama Almarhum</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $laporan->nama_almarhum }}</dd>
                </div>
                <div class="rounded-xl border border-line bg-canvas p-4">
                    <dt class="text-xs text-muted">NIK Almarhum</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold text-ink">{{ $laporan->nik_almarhum }}</dd>
                </div>
                <div class="rounded-xl border border-line bg-canvas p-4">
                    <dt class="text-xs text-muted">Nama Pelapor</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $laporan->nama_pelapor }}</dd>
                </div>
                <div class="rounded-xl border border-line bg-canvas p-4">
                    <dt class="text-xs text-muted">Hubungan Pelapor</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $laporan->hubungan_pelapor }}</dd>
                </div>
                <div class="rounded-xl border border-line bg-canvas p-4">
                    <dt class="text-xs text-muted">Tanggal Kematian</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">
                        {{ $laporan->tanggal_kematian?->format('d/m/Y') ?? '-' }}
                    </dd>
                </div>
                <div class="rounded-xl border border-line bg-canvas p-4">
                    <dt class="text-xs text-muted">Tempat Kematian</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $laporan->tempat_kematian ?: '-' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-ink">Pemeriksaan Kelengkapan Dokumen</h2>
                    <p class="mt-1 text-sm text-muted">
                        Bukti kematian dapat berupa salah satu dari {{ implode(', ', $jenisBuktiKematian) }}.
                    </p>
                </div>
                <x-ui.badge :variant="$jumlahTersedia === $jumlahWajib ? 'success' : 'warning'">
                    {{ $jumlahTersedia }} / {{ $jumlahWajib }} lengkap
                </x-ui.badge>
            </div>

            <div class="mt-4 space-y-3">
                @foreach ($dokumenTampil as $jenis)
                    @php
                        $berkas = $laporan->berkasPendukung->first(fn ($item) =>
                            $item->jenis_dokumen === $jenis
                            && $item->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL
                        );
                        $wajib = in_array($jenis, $jenisWajibTunggal, true);
                        $alternatif = in_array($jenis, $jenisBuktiKematian, true);
                        $statusKurang = $wajib || ($alternatif && ! $adaBuktiKematian);
                    @endphp

                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-surface p-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold
                                    {{ $berkas ? 'bg-[#dcfce7] text-[#15803d]' : ($statusKurang ? 'bg-[#fee2e2] text-[#b91c1c]' : 'bg-[#f1f5f9] text-[#475569]') }}">
                                {{ $berkas ? '✓' : ($statusKurang ? '!' : '–') }}
                            </span>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink">{{ $jenis }}</p>

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
                                        class="js-pratinjau max-w-full truncate text-left text-xs font-semibold text-brand underline underline-offset-2 hover:text-brand-hover"
                                        title="Klik untuk pratinjau"
                                        data-nama="{{ $berkas->nama_file }}"
                                        data-tipe="{{ $tipe }}"
                                        data-pratinjau="{{ route('verifikasi.pratinjau', [$laporan, $berkas]) }}"
                                        data-unduh="{{ route('verifikasi.unduh', [$laporan, $berkas]) }}"
                                    >{{ $berkas->nama_file }}</button>
                                @else
                                    <p class="text-xs text-muted">Belum diunggah</p>
                                @endif
                            </div>
                        </div>

                        @if ($berkas)
                            <x-ui.badge variant="success">Tersedia</x-ui.badge>
                        @elseif ($statusKurang)
                            <x-ui.badge variant="danger">Belum Lengkap</x-ui.badge>
                        @elseif ($alternatif)
                            <x-ui.badge variant="info">Alternatif</x-ui.badge>
                        @else
                            <x-ui.badge variant="info">Opsional</x-ui.badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Pencocokan Data SIAK">
            @if ($hasilPencocokan['ditemukan_di_siak'])
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-canvas p-4 text-sm text-muted">
                        <span>Kesesuaian NIK</span>
                        <x-ui.badge :variant="$hasilPencocokan['nik_sesuai'] ? 'success' : 'danger'">
                            {{ $hasilPencocokan['nik_sesuai'] ? 'Sesuai' : 'Tidak Sesuai' }}
                        </x-ui.badge>
                    </div>

                    <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-canvas p-4 text-sm text-muted">
                        <span>Kesesuaian Nama</span>
                        <x-ui.badge :variant="$hasilPencocokan['nama_sesuai'] ? 'success' : 'danger'">
                            {{ $hasilPencocokan['nama_sesuai'] ? 'Sesuai' : 'Tidak Sesuai' }}
                        </x-ui.badge>
                    </div>
                </div>
            @else
                <x-ui.alert variant="warning">
                    Data tidak ditemukan pada SIAK dummy. Lakukan pemeriksaan manual.
                </x-ui.alert>
            @endif
        </x-ui.card>

        <x-ui.card title="Pemeriksaan Dokumen (AI)">
            <p class="text-sm text-muted">
                Hasil bantuan AI, hanya saran. AI tidak menyetujui, menolak, atau mengubah status pengajuan.
            </p>

            @if ($suratKeterangan !== null)
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('verifikasi.analisis_ai', $laporan) }}">
                        @csrf
                        <input type="hidden" name="asal" value="baru">
                        <input type="hidden" name="id_berkas" value="{{ $suratKeterangan->id_berkas }}">
                        <x-ui.button type="submit" size="sm">Baca dokumen (AI)</x-ui.button>
                    </form>

                    <form method="POST" action="{{ route('verifikasi.ringkasan_ai', $laporan) }}">
                        @csrf
                        <input type="hidden" name="asal" value="baru">
                        <x-ui.button type="submit" variant="secondary" size="sm">Buat ringkasan AI</x-ui.button>
                    </form>
                </div>
            @else
                <x-ui.alert variant="warning" class="mt-4">
                    Surat Keterangan Kematian belum diunggah.
                </x-ui.alert>
            @endif

            @if ($analisis !== null)
                <div class="mt-4">
                    @include('laporan.panel_analisis_ai', ['analisis' => $analisis])
                </div>
            @else
                <p class="mt-4 text-sm text-muted">Belum ada hasil pemeriksaan dokumen.</p>
            @endif

            @if ($ringkasan !== null)
                <x-ui.alert :variant="$ringkasanBerhasil ? 'success' : 'warning'" class="mt-4">
                    <strong>Ringkasan Ketidaksesuaian (AI)</strong>
                    <p class="mt-1">{{ $ringkasan['ringkasan'] ?? '-' }}</p>

                    @if (! empty($ringkasan['saran']))
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($ringkasan['saran'] as $saran)
                                <li>{{ $saran }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.alert>
            @endif
        </x-ui.card>

        <x-ui.card title="Keputusan Verifikasi">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <form method="POST" action="{{ route('verifikasi.setujui', $laporan) }}"
                    class="flex flex-col justify-between gap-5 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Setujui Berkas</h3>
                        <p class="mt-1 text-xs text-muted">
                            Gunakan jika persyaratan dan data sudah sesuai sepenuhnya.
                        </p>

                        <label for="catatan" class="{{ $kelasLabel }} mt-4">Catatan Persetujuan (Opsional)</label>
                        <textarea id="catatan" name="catatan" rows="3" class="{{ $kelasArea }}"
                            placeholder="Tambahkan catatan jika ada..."></textarea>
                    </div>

                    <x-ui.button type="submit" class="w-full">Setujui Berkas</x-ui.button>
                </form>

                <form method="POST" action="{{ route('verifikasi.revisi', $laporan) }}"
                    class="flex flex-col justify-between gap-5 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Minta Revisi</h3>
                        <p class="mt-1 text-xs text-muted">
                            Gunakan jika ada dokumen yang perlu diperbaiki oleh pemohon.
                        </p>

                        <label for="catatan_revisi" class="{{ $kelasLabel }} mt-4">Catatan Revisi (Wajib)</label>
                        <textarea id="catatan_revisi" name="catatan" rows="3" required minlength="5" maxlength="2000"
                            class="{{ $kelasArea }}" placeholder="Jelaskan berkas yang perlu diperbaiki..."></textarea>
                    </div>

                    <x-ui.button type="submit" variant="secondary" class="w-full">Minta Revisi</x-ui.button>
                </form>

                <form method="POST" action="{{ route('verifikasi.anomali', $laporan) }}"
                    class="flex flex-col justify-between gap-5 rounded-2xl border border-line bg-canvas p-5 md:col-span-2 lg:col-span-1">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Tandai Kasus Anomali</h3>
                        <p class="mt-1 text-xs text-muted">
                            Alihkan ke rekonsiliasi jika ditemukan ketidaksesuaian data.
                        </p>

                        <div class="mt-4 space-y-3">
                            <x-ui.select name="jenis_anomali" label="Jenis Anomali" placeholder="Pilih Jenis Anomali"
                                :options="$opsiAnomali" required />

                            <div>
                                <label for="deskripsi" class="{{ $kelasLabel }}">Deskripsi</label>
                                <textarea id="deskripsi" name="deskripsi" rows="3" required class="{{ $kelasArea }}"
                                    placeholder="Jelaskan ketidaksesuaian..."></textarea>
                            </div>
                        </div>
                    </div>

                    <x-ui.button type="submit" class="w-full">Dialihkan ke Rekonsiliasi</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>

    <dialog id="dlg-pratinjau"
        class="m-auto h-[88vh] w-[min(960px,94vw)] rounded-2xl border-0 p-0 shadow-[0_24px_60px_-12px_rgb(15_23_42_/_0.45)] backdrop:bg-ink/50">
        <div class="flex h-full w-full flex-col bg-surface">
            <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3">
                <strong id="dlg-judul" class="truncate text-sm text-ink"></strong>

                <div class="flex shrink-0 items-center gap-2">
                    <x-ui.button id="dlg-unduh" href="#" size="sm">Unduh</x-ui.button>
                    <x-ui.button id="dlg-tutup" type="button" variant="secondary" size="sm">Tutup</x-ui.button>
                </div>
            </div>

            <div id="dlg-isi" class="flex min-h-0 flex-1 items-center justify-center overflow-auto bg-canvas"></div>
        </div>
    </dialog>

    <script>
        (function () {
            var dlg = document.getElementById('dlg-pratinjau');
            var judul = document.getElementById('dlg-judul');
            var unduh = document.getElementById('dlg-unduh');
            var isi = document.getElementById('dlg-isi');

            function pesan(teks) {
                var p = document.createElement('p');
                p.className = 'p-6 text-center text-sm text-muted';
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
                        f.className = 'h-full w-full border-0';
                        isi.appendChild(f);
                    } else if (tipe === 'gambar') {
                        var g = document.createElement('img');
                        g.src = tombol.dataset.pratinjau;
                        g.alt = tombol.dataset.nama;
                        g.className = 'max-h-full max-w-full object-contain';
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
