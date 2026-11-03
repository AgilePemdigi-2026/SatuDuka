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
        $labelTindakan = [
            'setujui' => 'Setujui Berkas',
            'minta_perbaikan' => 'Minta Perbaikan Berkas',
            'tolak_revisi' => 'Tolak / Butuh Revisi',
            'tandai_anomali' => 'Tandai Anomali',
            'tolak_fatal' => 'Tolak Anomali Fatal',
        ];
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <x-ui.page-header :title="$laporan->nama_almarhum ?: 'Detail Verifikasi Berkas'"
            :subtitle="'Nomor Pengajuan: '.($laporan->no_pengajuan ?? '-')">
            <x-slot:aksi>
                @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                @if ($laporan->status_pengajuan === \App\Models\LaporanKematian::STATUS_DALAM_REKONSILIASI)
                    <x-ui.button :href="route('rekonsiliasi.show', $laporan)" size="sm">
                        Telusuri Rekonsiliasi
                    </x-ui.button>
                @endif
            </x-slot:aksi>
        </x-ui.page-header>

        @if ($bannerManual)
            <x-ui.alert variant="warning">
                <p class="font-semibold">Layanan eksternal tidak tersedia</p>
                <p class="mt-1 font-normal">
                    Data SIAK atau analisis AI tidak dapat diakses. Lanjutkan secara manual; keputusan tetap dapat diambil.
                </p>
            </x-ui.alert>
        @endif

        @if ($laporan->status_pengajuan === \App\Models\LaporanKematian::STATUS_DIKEMBALIKAN
            && filled($laporan->dokumenResmi?->alasan_pengembalian))
            <x-ui.alert variant="warning">
                <p class="font-semibold">Dokumen dikembalikan oleh Pejabat</p>
                <p class="mt-1 font-normal">{{ $laporan->dokumenResmi->alasan_pengembalian }}</p>
                <p class="mt-1 font-normal">
                    Periksa kembali berkas dan data; setelah sesuai, setujui ulang agar dapat ditandatangani.
                </p>
            </x-ui.alert>
        @endif

        <x-ui.card title="Informasi Pemohon &amp; Almarhum">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-line bg-canvas p-4">
                    <dt class="text-xs text-muted">Nama Almarhum</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $laporan->nama_almarhum }}</dd>
                </div>
                <div class="rounded-xl border border-line bg-canvas p-4">
                    <dt class="text-xs text-muted">NIK Almarhum</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold text-ink">
                        {{ \App\Support\MaskerNik::masker($laporan->nik_almarhum) ?? '-' }}
                    </dd>
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

        <x-ui.card title="Pemeriksaan Aturan">
            <p class="text-sm text-muted">
                Hasil mesin rekonsiliasi, hanya saran. Mesin tidak menyetujui, menolak, atau mengubah status pengajuan.
            </p>

            <div class="mt-4 rounded-xl border border-line bg-canvas p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Rekomendasi Utama Mesin</p>
                <p class="mt-1 text-sm font-semibold text-ink">
                    {{ $labelTindakan[$tindakanDisarankan] ?? $tindakanDisarankan }}
                </p>
                <p class="mt-1 text-xs text-muted">{{ $analisisAturan->rekomendasiUtama }}</p>
            </div>

            @if ($analisisAturan->anomali === [])
                <x-ui.notice icon="check" class="mt-4">
                    <p class="font-semibold text-ink">Tidak ada anomali terdeteksi</p>
                    <p class="mt-1">Seluruh aturan yang dapat diperiksa mesin terpenuhi.</p>
                </x-ui.notice>
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($analisisAturan->anomali as $anomali)
                        @php
                            $varianKeparahan = match ($anomali->keparahan) {
                                'fatal' => 'danger',
                                'tinggi' => 'warning',
                                'sedang' => 'info',
                                'rendah' => 'neutral',
                                default => 'neutral',
                            };
                            $labelAnomali = config(
                                'verifikasi.taksonomi_anomali.'.$anomali->kode.'.label',
                                $anomali->kode
                            );
                        @endphp

                        <li class="rounded-xl border border-line bg-surface p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <x-ui.badge :variant="$varianKeparahan">{{ ucfirst($anomali->keparahan) }}</x-ui.badge>
                                    <span class="text-xs font-semibold text-ink-soft">{{ $labelAnomali }}</span>
                                </div>
                            </div>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-ink">{{ $anomali->pesan }}</p>
                            <p class="mt-1 text-xs text-muted">Rekomendasi: {{ $anomali->rekomendasi }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Pemeriksaan Dokumen (AI)">
            <p class="text-sm text-muted">
                Hasil bantuan AI, hanya saran. AI tidak menyetujui, menolak, atau mengubah status pengajuan.
            </p>

            @php
                $suratKeterangan = $laporan->berkasPendukung->first(fn ($berkas) =>
                    $berkas->jenis_dokumen === \App\Models\BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN
                    && $berkas->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL
                );
            @endphp

            @if ($suratKeterangan !== null)
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('verifikasi.analisis_ai', $laporan) }}">
                        @csrf
                        <input type="hidden" name="id_berkas" value="{{ $suratKeterangan->id_berkas }}">
                        <x-ui.button type="submit" size="sm">Baca dokumen (AI)</x-ui.button>
                    </form>

                    <form method="POST" action="{{ route('verifikasi.ringkasan_ai', $laporan) }}">
                        @csrf
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
            <p class="text-sm text-muted">
                Keputusan sepenuhnya milik verifikator. Tindakan yang berbeda dari rekomendasi utama mesin wajib disertai alasan override.
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <form method="POST" action="{{ route('verifikasi.setujui', $laporan) }}"
                    class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Setujui Berkas</h3>
                        <p class="mt-1 text-xs text-muted">
                            Status menjadi Disetujui/Menunggu TTE dan dokumen resmi berstatus Menunggu dibuat.
                        </p>

                        <label for="catatan_setujui" class="{{ $kelasLabel }} mt-3">Catatan Persetujuan (Opsional)</label>
                        <textarea id="catatan_setujui" name="catatan" rows="2" class="{{ $kelasArea }}"
                            placeholder="Tambahkan catatan jika ada..."></textarea>
                        @include('verifikasi.bagian_override', ['id' => 'alasan_setujui'])
                    </div>

                    <x-ui.button type="submit" class="w-full">Setujui Berkas</x-ui.button>
                </form>

                <form method="POST" action="{{ route('verifikasi.perbaikan', $laporan) }}"
                    class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Minta Perbaikan Berkas</h3>
                        <p class="mt-1 text-xs text-muted">
                            Status menjadi Pending - Menunggu Perbaikan Berkas Pelapor.
                        </p>

                        <label for="catatan_perbaikan" class="{{ $kelasLabel }} mt-3">Catatan Perbaikan (Wajib)</label>
                        <textarea id="catatan_perbaikan" name="catatan" rows="2" required minlength="5" maxlength="2000"
                            class="{{ $kelasArea }}" placeholder="Jelaskan berkas yang perlu diperbaiki..."></textarea>
                        @include('verifikasi.bagian_override', ['id' => 'alasan_perbaikan'])
                    </div>

                    <x-ui.button type="submit" variant="secondary" class="w-full">Minta Perbaikan</x-ui.button>
                </form>

                <form method="POST" action="{{ route('verifikasi.revisi', $laporan) }}"
                    class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Tolak / Butuh Revisi</h3>
                        <p class="mt-1 text-xs text-muted">
                            Status menjadi Ditolak/Butuh Revisi beserta catatan untuk pemohon.
                        </p>

                        <label for="catatan_revisi" class="{{ $kelasLabel }} mt-3">Catatan Revisi (Wajib)</label>
                        <textarea id="catatan_revisi" name="catatan" rows="2" required minlength="5" maxlength="2000"
                            class="{{ $kelasArea }}" placeholder="Jelaskan alasan penolakan..."></textarea>
                        @include('verifikasi.bagian_override', ['id' => 'alasan_revisi'])
                    </div>

                    <x-ui.button type="submit" variant="secondary" class="w-full">Kirim Catatan Revisi</x-ui.button>
                </form>

                <form method="POST" action="{{ route('verifikasi.anomali', $laporan) }}"
                    class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Tandai Anomali</h3>
                        <p class="mt-1 text-xs text-muted">Status menjadi Dalam Rekonsiliasi Anomali.</p>

                        <div class="mt-3 space-y-2">
                            <x-ui.select name="jenis_anomali" label="Jenis Anomali" placeholder="Pilih Jenis Anomali"
                                :options="$opsiAnomali" required />

                            <div>
                                <label for="deskripsi" class="{{ $kelasLabel }}">Deskripsi</label>
                                <textarea id="deskripsi" name="deskripsi" rows="2" required minlength="5" maxlength="2000"
                                    class="{{ $kelasArea }}" placeholder="Jelaskan ketidaksesuaian..."></textarea>
                            </div>

                            @include('verifikasi.bagian_override', ['id' => 'alasan_anomali'])
                        </div>
                    </div>

                    <x-ui.button type="submit" class="w-full">Simpan ke Rekonsiliasi</x-ui.button>
                </form>

                <form method="POST" action="{{ route('verifikasi.tolak_fatal', $laporan) }}"
                    class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5 md:col-span-2 lg:col-span-1">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Tolak Anomali Fatal</h3>
                        <p class="mt-1 text-xs text-muted">
                            Status menjadi Ditolak - Anomali Data Fatal. Gunakan hanya untuk anomali fatal.
                        </p>

                        <label for="catatan_fatal" class="{{ $kelasLabel }} mt-3">Catatan Penolakan (Wajib)</label>
                        <textarea id="catatan_fatal" name="catatan" rows="2" required minlength="5" maxlength="2000"
                            class="{{ $kelasArea }}" placeholder="Jelaskan alasan penolakan permanen..."></textarea>
                        @include('verifikasi.bagian_override', ['id' => 'alasan_fatal'])
                    </div>

                    <x-ui.button type="submit" variant="secondary" class="w-full">Tolak Anomali Fatal</x-ui.button>
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