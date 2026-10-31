@extends('tata_letak.utama')

@section('judul', 'Detail Rekonsiliasi Anomali')

@section('kembali')
    <a href="{{ route('rekonsiliasi.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-ink-soft hover:text-brand">
        <x-icon.arrow-left class="h-4 w-4" />
        Kembali ke Antrean Rekonsiliasi
    </a>
@endsection

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => auth()->user()])
@endsection

@section('konten')
    @php
        $kelasLabel = 'mb-1.5 block text-sm font-semibold text-ink-soft';
        $kelasArea = 'block w-full rounded-xl border border-line-strong bg-surface px-3.5 py-3 text-sm text-ink outline-none transition placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30';
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <x-ui.page-header :title="$laporan->nama_almarhum ?: 'Detail Rekonsiliasi'"
            :subtitle="'Nomor Pengajuan: '.($laporan->no_pengajuan ?? '-')">
            <x-slot:aksi>
                @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                <x-ui.button :href="route('verifikasi.show', $laporan)" variant="secondary" size="sm">
                    Detail Berkas
                </x-ui.button>
            </x-slot:aksi>
        </x-ui.page-header>

        @if ($bannerManual)
            <x-ui.alert variant="warning">
                <p class="font-semibold">Layanan eksternal tidak tersedia</p>
                <p class="mt-1 font-normal">
                    Data SIAK atau hasil ekstraksi surat keterangan tidak dapat dimuat. Kolom terkait ditandai
                    "tidak tersedia"; rekonsiliasi tetap dapat dilanjutkan secara manual.
                </p>
            </x-ui.alert>
        @endif

        <x-ui.card title="Komparasi Data">
            <p class="text-sm text-muted">
                Bandingkan isian pemohon, data master SIAK, dan hasil ekstraksi surat keterangan. Data acuan yang
                sah ditentukan oleh verifikator berdasarkan bukti otentik.
            </p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-line text-xs uppercase tracking-wide text-muted">
                            <th class="py-2 pr-3">Kolom</th>
                            <th class="px-3 py-2">Data Pemohon</th>
                            <th class="px-3 py-2">Data SIAK</th>
                            <th class="px-3 py-2">Ekstraksi Surat Keterangan</th>
                            <th class="pl-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kolomKomparasi as $baris)
                            <tr class="border-b border-line last:border-0 align-top">
                                <td class="py-3 pr-3 font-semibold text-ink">{{ $baris['label'] }}</td>
                                <td class="px-3 py-3 text-ink">{{ $baris['nilai_pemohon'] ?: 'â€”' }}</td>
                                <td class="px-3 py-3">
                                    @if ($siakTersedia)
                                        <span class="text-ink">{{ $baris['nilai_siak'] ?: 'â€”' }}</span>
                                    @else
                                        <span class="text-muted italic">Tidak tersedia</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if ($ekstraksiTersedia)
                                        <span class="text-ink">{{ $baris['nilai_ekstraksi'] ?: 'â€”' }}</span>
                                    @else
                                        <span class="text-muted italic">Tidak tersedia</span>
                                    @endif
                                </td>
                                <td class="py-3 pl-3">
                                    @php
                                        $varian = match ($baris['status']) {
                                            'cocok' => 'success',
                                            'beda' => 'warning',
                                            default => 'neutral',
                                        };
                                        $labelStatus = match ($baris['status']) {
                                            'cocok' => 'Cocok',
                                            'beda' => 'Beda',
                                            default => 'Tidak terbaca',
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$varian">{{ $labelStatus }}</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-xs text-muted">
                NIK ditampilkan dalam bentuk termasker. NIK utuh hanya dipakai pada proses verifikasi internal dan
                tidak pernah ditulis ke log audit.
            </p>
        </x-ui.card>

        <x-ui.card title="Anomali Terbuka">
            @if ($anomali === [])
                <x-ui.notice icon="check">
                    <p class="font-semibold text-ink">Tidak ada anomali terbuka</p>
                    <p class="mt-1">Seluruh anomali pada pengajuan ini sudah ditutup.</p>
                </x-ui.notice>
            @else
                <ul class="space-y-3">
                    @foreach ($anomali as $item)
                        <li class="rounded-xl border border-line bg-surface p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-ink">{{ $item->jenis_anomali }}</span>
                                <x-ui.badge variant="purple">Terbuka</x-ui.badge>
                            </div>
                            <p class="mt-2 text-sm text-ink-soft">{{ $item->deskripsi }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card title="Aksi Rekonsiliasi">
            <p class="text-sm text-muted">
                Keputusan sepenuhnya milik verifikator. Setiap aksi berjalan dalam satu transaksi dan dicatat ke
                log audit.
            </p>

            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <form method="POST" action="{{ route('rekonsiliasi.selesaikan', $laporan) }}"
                    class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Selesaikan Rekonsiliasi</h3>
                        <p class="mt-1 text-xs text-muted">
                            Koreksi data sesuai bukti otentik, tutup anomali, dan kembalikan berkas ke antrean verifikasi.
                        </p>

                        <label for="nama_almarhum" class="{{ $kelasLabel }} mt-3">Nama Almarhum/Almarhumah</label>
                        <input id="nama_almarhum" name="nama_almarhum" type="text" maxlength="100" required
                            value="{{ old('nama_almarhum', $laporan->nama_almarhum) }}" class="{{ $kelasArea }}" />
                        @error('nama_almarhum')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror

                        <label for="tanggal_kematian" class="{{ $kelasLabel }} mt-3">Tanggal Kematian</label>
                        <input id="tanggal_kematian" name="tanggal_kematian" type="date" required
                            value="{{ old('tanggal_kematian', $laporan->tanggal_kematian?->toDateString()) }}"
                            class="{{ $kelasArea }}" />
                        @error('tanggal_kematian')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror

                        <label for="nik_almarhum" class="{{ $kelasLabel }} mt-3">NIK Baru (Opsional)</label>
                        <input id="nik_almarhum" name="nik_almarhum" type="text" inputmode="numeric" maxlength="16"
                            autocomplete="off" placeholder="Kosongkan bila NIK tidak berubah"
                            class="{{ $kelasArea }}" />
                        <p class="mt-1 text-xs text-muted">
                            NIK lama {{ \App\Support\MaskerNik::masker($laporan->nik_almarhum) ?? '-' }}. Isi hanya
                            bila NIK perlu dikoreksi.
                        </p>
                        @error('nik_almarhum')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror

                        <label for="catatan_rekonsiliasi" class="{{ $kelasLabel }} mt-3">Berita Acara/Catatan Rekonsiliasi</label>
                        <textarea id="catatan_rekonsiliasi" name="catatan_rekonsiliasi" rows="3" required minlength="5"
                            maxlength="2000" class="{{ $kelasArea }}"
                            placeholder="Jelaskan data acuan dan dasar penyesuaian...">{{ old('catatan_rekonsiliasi') }}</textarea>
                        @error('catatan_rekonsiliasi')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit" class="w-full">Simpan Penyesuaian</x-ui.button>
                </form>

                <form method="POST" action="{{ route('verifikasi.perbaikan', $laporan) }}"
                    class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Kembalikan ke Pemohon</h3>
                        <p class="mt-1 text-xs text-muted">
                            Status menjadi Pending - Menunggu Perbaikan Berkas Pelapor. Anomali tetap Terbuka.
                        </p>

                        <label for="catatan_perbaikan" class="{{ $kelasLabel }} mt-3">Catatan Perbaikan (Wajib)</label>
                        <textarea id="catatan_perbaikan" name="catatan" rows="3" required minlength="5" maxlength="2000"
                            class="{{ $kelasArea }}"
                            placeholder="Dokumen faskes tidak terbaca; mohon unggah ulang dokumen yang valid...">{{ old('catatan') }}</textarea>
                        @error('catatan')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror

                        @include('verifikasi.bagian_override', ['id' => 'alasan_perbaikan_rekonsiliasi'])
                    </div>

                    <x-ui.button type="submit" variant="secondary" class="w-full">Minta Unggah Ulang</x-ui.button>
                </form>

                <form method="POST" action="{{ route('rekonsiliasi.tolak_fatal', $laporan) }}"
                    class="flex flex-col justify-between gap-4 rounded-2xl border border-line bg-canvas p-5">
                    @csrf

                    <div>
                        <h3 class="text-sm font-bold text-ink">Tolak Permanen &amp; Eskalasi</h3>
                        <p class="mt-1 text-xs text-muted">
                            Status menjadi Ditolak - Anomali Data Fatal. Gunakan hanya bila identitas fiktif atau
                            sengketa tidak dapat diverifikasi.
                        </p>

                        <label for="alasan_eskalasi" class="{{ $kelasLabel }} mt-3">Alasan Eskalasi (Internal)</label>
                        <textarea id="alasan_eskalasi" name="alasan_eskalasi" rows="2" required minlength="5"
                            maxlength="2000" class="{{ $kelasArea }}"
                            placeholder="Alasan internal; tidak ditampilkan ke pemohon.">{{ old('alasan_eskalasi') }}</textarea>
                        <p class="mt-1 text-xs text-muted">Hanya disimpan pada log audit dan hasil verifikasi.</p>
                        @error('alasan_eskalasi')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror

                        <label for="catatan_resmi" class="{{ $kelasLabel }} mt-3">Catatan Resmi untuk Pemohon</label>
                        <textarea id="catatan_resmi" name="catatan_resmi" rows="2" required minlength="5"
                            maxlength="2000" class="{{ $kelasArea }}"
                            placeholder="Catatan resmi yang ditampilkan pada status pengajuan pemohon.">{{ old('catatan_resmi') }}</textarea>
                        @error('catatan_resmi')
                        <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit" variant="danger" class="w-full">Konfirmasi Penolakan Fatal</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>
@endsection



