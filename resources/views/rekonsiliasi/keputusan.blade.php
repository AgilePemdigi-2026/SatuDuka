{{--
    Panel aksi rekonsiliasi: satu dropdown memilih tindakan yang dipakai.

    Seluruh panel tetap dirender agar halaman tetap berfungsi tanpa JavaScript;
    resources/js/app.js hanya menyembunyikan panel yang tidak dipilih.
--}}
@php
    $labelAksi = [
        'selesaikan' => 'Selesaikan Rekonsiliasi',
        'kembalikan' => 'Kembalikan ke Pemohon',
        'tolak_fatal' => 'Tolak Permanen & Eskalasi',
    ];
    $aksiTerpilih = old('tindakan', 'selesaikan');
@endphp

<div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] lg:items-start">
    <div class="space-y-3">
        <x-ui.select name="tindakan" label="Aksi Rekonsiliasi" :options="$labelAksi" :value="$aksiTerpilih"
            data-pilih-tindakan="rekonsiliasi" />

        <x-ui.notice icon="shield-check">
            <p class="text-sm font-semibold text-ink">Keputusan milik verifikator</p>
            <p class="mt-1 text-xs">Setiap aksi berjalan dalam satu transaksi dan dicatat ke log audit.</p>
        </x-ui.notice>
    </div>

    <div class="space-y-4" data-wadah-tindakan="rekonsiliasi">
        <form method="POST" action="{{ route('rekonsiliasi.selesaikan', $laporan) }}"
            class="space-y-4 rounded-2xl border border-line bg-canvas p-5" data-panel-tindakan="selesaikan">
            @csrf
            <input type="hidden" name="tindakan" value="selesaikan">

            <div>
                <h3 class="text-sm font-bold text-ink">Selesaikan Rekonsiliasi</h3>
                <p class="mt-1 text-xs text-muted">
                    Koreksi data sesuai bukti otentik, tutup anomali, dan kembalikan berkas ke antrean verifikasi.
                </p>
            </div>

            <div>
                <label for="nama_almarhum" class="{{ $kelasLabel }}">Nama Almarhum/Almarhumah</label>
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
                    autocomplete="off" placeholder="Kosongkan bila NIK tidak berubah" class="{{ $kelasArea }}" />
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

            <x-ui.button type="submit" class="w-full sm:w-auto">Simpan Penyesuaian</x-ui.button>
        </form>

        <form method="POST" action="{{ route('verifikasi.perbaikan', $laporan) }}"
            class="space-y-4 rounded-2xl border border-line bg-canvas p-5" data-panel-tindakan="kembalikan">
            @csrf
            <input type="hidden" name="tindakan" value="kembalikan">

            <div>
                <h3 class="text-sm font-bold text-ink">Kembalikan ke Pemohon</h3>
                <p class="mt-1 text-xs text-muted">
                    Status menjadi Pending - Menunggu Perbaikan Berkas Pelapor. Anomali tetap Terbuka.
                </p>
            </div>

            <div>
                <label for="catatan_perbaikan" class="{{ $kelasLabel }}">Catatan Perbaikan (Wajib)</label>
                <textarea id="catatan_perbaikan" name="catatan" rows="3" required minlength="5" maxlength="2000"
                    class="{{ $kelasArea }}"
                    placeholder="Dokumen faskes tidak terbaca; mohon unggah ulang dokumen yang valid...">{{ old('catatan') }}</textarea>
                @error('catatan')
                    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                @enderror

                @include('verifikasi.bagian_override', ['id' => 'alasan_perbaikan_rekonsiliasi'])
            </div>

            <x-ui.button type="submit" variant="secondary" class="w-full sm:w-auto">Minta Unggah Ulang</x-ui.button>
        </form>

        <form method="POST" action="{{ route('rekonsiliasi.tolak_fatal', $laporan) }}"
            class="space-y-4 rounded-2xl border border-line bg-canvas p-5" data-panel-tindakan="tolak_fatal">
            @csrf
            <input type="hidden" name="tindakan" value="tolak_fatal">

            <div>
                <h3 class="text-sm font-bold text-ink">Tolak Permanen &amp; Eskalasi</h3>
                <p class="mt-1 text-xs text-muted">
                    Status menjadi Ditolak - Anomali Data Fatal. Gunakan hanya bila identitas fiktif atau
                    sengketa tidak dapat diverifikasi.
                </p>
            </div>

            <div>
                <label for="alasan_eskalasi" class="{{ $kelasLabel }}">Alasan Eskalasi (Internal)</label>
                <textarea id="alasan_eskalasi" name="alasan_eskalasi" rows="2" required minlength="5" maxlength="2000"
                    class="{{ $kelasArea }}"
                    placeholder="Alasan internal; tidak ditampilkan ke pemohon.">{{ old('alasan_eskalasi') }}</textarea>
                <p class="mt-1 text-xs text-muted">Hanya disimpan pada log audit dan hasil verifikasi.</p>
                @error('alasan_eskalasi')
                    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                @enderror

                <label for="catatan_resmi" class="{{ $kelasLabel }} mt-3">Catatan Resmi untuk Pemohon</label>
                <textarea id="catatan_resmi" name="catatan_resmi" rows="2" required minlength="5" maxlength="2000"
                    class="{{ $kelasArea }}"
                    placeholder="Catatan resmi yang ditampilkan pada status pengajuan pemohon.">{{ old('catatan_resmi') }}</textarea>
                @error('catatan_resmi')
                    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.button type="submit" variant="danger" class="w-full sm:w-auto">Konfirmasi Penolakan Fatal</x-ui.button>
        </form>
    </div>
</div>
