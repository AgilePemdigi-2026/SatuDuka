@extends('tata_letak.utama')

@section('judul', 'Detail Dokumen TTE')

@section('kembali')
    <a href="{{ route('tanda_tangan.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-brand">
        <x-icon.arrow-left class="h-4 w-4" />
        Kembali ke Daftar Dokumen
    </a>
@endsection

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    @php
        $kelasLabel = 'mb-1.5 block text-sm font-semibold text-ink-soft';
        $kelasArea = 'block w-full rounded-xl border border-line-strong bg-surface px-3.5 py-3 text-sm text-ink outline-none transition placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30';
        $menunggu = $dokumen->status_ttd === \App\Models\DokumenResmi::STATUS_MENUNGGU;
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <x-ui.page-header :title="$laporan->nama_almarhum ?: 'Detail Dokumen'"
            :subtitle="'Nomor pengajuan: '.($laporan->no_pengajuan ?? '-')">
            <x-slot:aksi>
                <x-ui.badge :variant="match ($dokumen->status_ttd) {
                    \App\Models\DokumenResmi::STATUS_MENUNGGU => 'warning',
                    \App\Models\DokumenResmi::STATUS_SELESAI => 'success',
                    default => 'danger',
                }">{{ $dokumen->status_ttd }}</x-ui.badge>
            </x-slot:aksi>
        </x-ui.page-header>

        @if ($terkunci)
            <x-ui.alert variant="danger">
                <p class="font-semibold">Akses TTE dikunci</p>
                <p class="mt-1 font-normal">
                    Akses TTE dikunci selama 15 menit. Silakan coba lagi nanti.
                    @if ($sisaKunci > 0)
                        (sekitar {{ (int) ceil($sisaKunci / 60) }} menit lagi)
                    @endif
                </p>
            </x-ui.alert>
        @endif

        @if ($dokumen->status_ttd === \App\Models\DokumenResmi::STATUS_DIKEMBALIKAN)
            <x-ui.alert variant="warning">
                <p class="font-semibold">Dokumen dikembalikan</p>
                <p class="mt-1 font-normal">{{ $dokumen->alasan_pengembalian ?: 'Alasan pengembalian belum dicatat.' }}</p>
            </x-ui.alert>
        @endif

        <x-ui.card title="Pratinjau Surat">
            <x-dokumen.pratinjau-akta :laporan="$laporan" :dokumen="$dokumen" />
        </x-ui.card>

        @if ($dokumen->status_ttd === \App\Models\DokumenResmi::STATUS_SELESAI)
            <x-ui.card title="Berkas Dokumen Resmi">
                @if ($berkasAda)
                    <p class="text-sm text-muted">
                        Berkas PDF Kutipan Akta Kematian sudah tersedia.
                    </p>
                    <div class="mt-3">
                        <x-ui.button :href="route('tanda_tangan.unduh', $laporan)" size="sm">
                            <x-icon.arrow-down-tray class="h-4 w-4" />
                            Unduh Dokumen (PDF)
                        </x-ui.button>
                    </div>
                @else
                    <x-ui.alert variant="info">
                        <p class="font-semibold">Dokumen resmi sedang disiapkan</p>
                        <p class="mt-1 font-normal">
                            {{ $berkasHilang
                                ? 'Berkas tidak ditemukan pada penyimpanan. Gunakan perintah pemulihan atau terbitkan ulang dari layanan.'
                                : 'Berkas PDF belum dibuat. Gunakan tombol di bawah untuk menerbitkan ulang.' }}
                        </p>
                    </x-ui.alert>

                    @if (! $berkasHilang)
                        <form method="POST" action="{{ route('tanda_tangan.terbitkan', $laporan) }}" class="mt-3">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="secondary">
                                Buat ulang dokumen
                            </x-ui.button>
                        </form>
                    @endif
                @endif
            </x-ui.card>
        @endif

        @if ($menunggu)
            <x-ui.card title="Keputusan Pejabat">
                <p class="text-sm text-muted">
                    Pastikan seluruh data sesuai sebelum membubuhkan tanda tangan elektronik. PIN/OTP tidak
                    disimpan oleh SATUDUKA.
                </p>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                        <div>
                            <h3 class="text-sm font-bold text-ink">Tanda Tangani</h3>
                            <p class="mt-1 text-xs text-muted">
                                Status dokumen menjadi Selesai dan pengajuan diterbitkan.
                            </p>

                            @if ($terkunci)
                                <p class="mt-3 rounded-xl border border-[#f59e0b] bg-[#fef3c7] px-3 py-2 text-xs font-semibold text-[#b45309]">
                                    Tombol tanda tangan dinonaktifkan selama akses TTE terkunci.
                                </p>
                            @else
                                <x-ui.button type="button" class="mt-3 w-full"
                                    onclick="window.bukaModalTte ? window.bukaModalTte() : document.getElementById('dlg-tte').showModal()">
                                    Tanda Tangani
                                </x-ui.button>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('tanda_tangan.kembalikan', $laporan) }}"
                        class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                        @csrf

                        <div>
                            <h3 class="text-sm font-bold text-ink">Kembalikan</h3>
                            <p class="mt-1 text-xs text-muted">
                                Dokumen dikembalikan untuk perbaikan; alasannya dicatat pada audit.
                            </p>

                            <label for="alasan" class="{{ $kelasLabel }} mt-3">Alasan Pengembalian (Wajib)</label>
                            <textarea id="alasan" name="alasan" rows="3" required minlength="5" maxlength="1000"
                                class="{{ $kelasArea }}"
                                placeholder="Contoh: tanggal meninggal perlu diperbaiki">{{ old('alasan') }}</textarea>
                            @error('alasan')
                                <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-ui.button type="submit" variant="secondary" class="w-full">Kembalikan Dokumen</x-ui.button>
                    </form>
                </div>
            </x-ui.card>
        @endif
    </div>

    @if ($menunggu && ! $terkunci)
        <dialog id="dlg-tte"
            class="m-auto w-[min(520px,94vw)] rounded-2xl border-0 p-0 shadow-[0_24px_60px_-12px_rgb(15_23_42_/_0.45)] backdrop:bg-ink/50">
            <form method="POST" action="{{ route('tanda_tangan.tandatangani', $laporan) }}"
                class="flex flex-col gap-4 bg-surface p-6" data-form-tte>
                @csrf

                <div>
                    <h2 class="text-lg font-semibold text-ink">Konfirmasi Tanda Tangan Elektronik</h2>
                    <p class="mt-1 text-sm text-muted">
                        Gambar tanda tangan Anda, lalu masukkan PIN/OTP TTE. Kredensial tidak disimpan oleh SATUDUKA.
                    </p>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="kanvas-tanda-tangan" class="{{ $kelasLabel }} mb-0">Tanda Tangan</label>
                        <button type="button" id="tombol-hapus-ttd"
                            class="rounded-lg border border-line-strong px-2.5 py-1 text-xs font-semibold text-ink-soft hover:bg-canvas">
                            Hapus/Ulangi
                        </button>
                    </div>

                    <div class="relative">
                        <canvas id="kanvas-tanda-tangan" width="600" height="200"
                            class="block h-[200px] w-full cursor-crosshair touch-none rounded-xl border border-line-strong bg-white"
                            aria-label="Area gambar tanda tangan"></canvas>
                        <span id="placeholder-ttd"
                            class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-[#94a3b8]">
                            Tanda tangan di sini
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-muted">
                        Tanda tangan visual ini bukan bukti keaslian; hanya penanda pada dokumen simulasi.
                    </p>

                    <input type="hidden" name="tanda_tangan" id="input-tanda-tangan" value="" />

                    @error('tanda_tangan')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pin" class="{{ $kelasLabel }}">PIN/OTP (6 digit)</label>
                    <input id="pin" name="pin" type="password" inputmode="numeric" maxlength="6"
                        autocomplete="off" required pattern="\d{6}" class="font-mono {{ $kelasArea }}"
                        placeholder="••••••" />
                    @error('pin')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <x-ui.button type="button" variant="secondary"
                        onclick="document.getElementById('dlg-tte').close()">Batal</x-ui.button>
                    <x-ui.button type="submit" id="tombol-tandatangani" :disabled="true">Konfirmasi &amp; Tanda Tangani</x-ui.button>
                </div>
            </form>
        </dialog>

        <script>
            (function () {
                var dlg = document.getElementById('dlg-tte');
                var kanvas = document.getElementById('kanvas-tanda-tangan');
                var input = document.getElementById('input-tanda-tangan');
                var tombolHapus = document.getElementById('tombol-hapus-ttd');
                var tombolKirim = document.getElementById('tombol-tandatangani');
                var placeholder = document.getElementById('placeholder-ttd');
                var form = dlg ? dlg.querySelector('[data-form-tte]') : null;
                var ctx = kanvas ? kanvas.getContext('2d') : null;
                var siap = false;
                var menggambar = false;
                var goresan = 0;
                var MIN_GORESAN = 30;

                if (!dlg || !kanvas || !ctx || !input || !form) {
                    return;
                }

                function siapkanKanvas() {
                    var rasio = window.devicePixelRatio || 1;
                    var lebarTampil = kanvas.clientWidth || kanvas.parentElement.clientWidth || 600;
                    var tinggiTampil = 200;

                    kanvas.width = Math.round(lebarTampil * rasio);
                    kanvas.height = Math.round(tinggiTampil * rasio);

                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.scale(rasio, rasio);
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.strokeStyle = '#000000';
                    ctx.fillStyle = '#000000';

                    bersihkan();
                    siap = true;
                }

                function bersihkan() {
                    ctx.save();
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.clearRect(0, 0, kanvas.width, kanvas.height);
                    ctx.restore();

                    goresan = 0;
                    input.value = '';
                    perbaruiTombol();
                }

                function perbaruiTombol() {
                    var cukup = goresan >= MIN_GORESAN;
                    tombolKirim.disabled = !cukup;

                    if (placeholder) {
                        placeholder.style.display = goresan > 0 ? 'none' : 'flex';
                    }
                }

                function posisi(ev) {
                    var kotak = kanvas.getBoundingClientRect();

                    return {
                        x: (ev.clientX - kotak.left) * (kanvas.clientWidth / kotak.width),
                        y: (ev.clientY - kotak.top) * (kanvas.clientHeight / kotak.height),
                    };
                }

                function mulai(ev) {
                    if (!siap) {
                        return;
                    }

                    ev.preventDefault();
                    kanvas.setPointerCapture(ev.pointerId);
                    menggambar = true;

                    var p = posisi(ev);
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(p.x + 0.1, p.y + 0.1);
                    ctx.stroke();
                    goresan += 3;
                    perbaruiTombol();
                }

                function lanjut(ev) {
                    if (!menggambar) {
                        return;
                    }

                    ev.preventDefault();
                    var p = posisi(ev);
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                    goresan += 2;
                    perbaruiTombol();
                }

                function selesai(ev) {
                    if (!menggambar) {
                        return;
                    }

                    menggambar = false;

                    try {
                        kanvas.releasePointerCapture(ev.pointerId);
                    } catch (e) {
                        /* pointer sudah dilepas */
                    }

                    if (goresan >= MIN_GORESAN) {
                        input.value = kanvas.toDataURL('image/png');
                    }
                }

                kanvas.style.touchAction = 'none';
                kanvas.addEventListener('pointerdown', mulai);
                kanvas.addEventListener('pointermove', lanjut);
                kanvas.addEventListener('pointerup', selesai);
                kanvas.addEventListener('pointercancel', selesai);
                kanvas.addEventListener('pointerleave', selesai);

                tombolHapus.addEventListener('click', bersihkan);

                dlg.addEventListener('close', function () {
                    bersihkan();
                });

                form.addEventListener('submit', function (ev) {
                    if (goresan < MIN_GORESAN) {
                        ev.preventDefault();
                        perbaruiTombol();
                    }
                });

                function buka() {
                    if (!dlg.open) {
                        dlg.showModal();
                    }

                    // Ukuran kanvas diatur saat modal terbuka agar tidak 0 px.
                    siapkanKanvas();
                }

                window.bukaModalTte = buka;

                @if ($bukaModal)
                    buka();
                @endif
            })();
        </script>
    @endif
@endsection
