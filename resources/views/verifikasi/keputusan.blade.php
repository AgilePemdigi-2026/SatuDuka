{{--
    Panel keputusan verifikasi: satu dropdown memilih tindakan yang dipakai.

    Seluruh panel tetap dirender agar halaman tetap berfungsi tanpa JavaScript;
    resources/js/app.js hanya menyembunyikan panel yang tidak dipilih. Setelah
    validasi gagal, panel yang tadi dipakai terbuka kembali karena nilai awal
    dropdown diambil dari old('tindakan').
--}}
@php
    $disarankan = $tindakanDisarankan ?? 'setujui';
    $tindakanTerpilih = old('tindakan', $disarankan);
@endphp

<div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] lg:items-start">
    <div class="space-y-3">
        <x-ui.select name="tindakan" label="Tindakan Verifikasi" :options="$labelTindakan" :value="$tindakanTerpilih"
            data-pilih-tindakan="verifikasi" />

        <x-ui.notice icon="shield-check">
            <p class="text-sm font-semibold text-ink">Rekomendasi mesin</p>
            <p class="mt-1 text-xs font-semibold text-ink">{{ $labelTindakan[$disarankan] ?? $disarankan }}</p>
            <p class="mt-1 text-xs">{{ $analisisAturan->rekomendasiUtama }}</p>
        </x-ui.notice>
    </div>

    <div class="space-y-4" data-wadah-tindakan="verifikasi">
        <form method="POST" action="{{ route('verifikasi.setujui', $laporan) }}"
            class="space-y-4 rounded-2xl border border-line bg-canvas p-5" data-panel-tindakan="setujui">
            @csrf
            <input type="hidden" name="tindakan" value="setujui">

            <div>
                <h3 class="text-sm font-bold text-ink">Setujui Berkas</h3>
                <p class="mt-1 text-xs text-muted">
                    Status menjadi Disetujui/Menunggu TTE dan dokumen resmi berstatus Menunggu dibuat.
                </p>
            </div>

            <div>
                <label for="catatan_setujui" class="{{ $kelasLabel }}">Catatan Persetujuan (Opsional)</label>
                <textarea id="catatan_setujui" name="catatan" rows="3" class="{{ $kelasArea }}"
                    placeholder="Tambahkan catatan jika ada..."></textarea>
                @include('verifikasi.bagian_override', ['id' => 'alasan_setujui'])
            </div>

            <x-ui.button type="submit" :variant="$disarankan === 'setujui' ? 'primary' : 'secondary'" class="w-full sm:w-auto">
                Setujui Berkas
            </x-ui.button>
        </form>

        <form method="POST" action="{{ route('verifikasi.perbaikan', $laporan) }}"
            class="space-y-4 rounded-2xl border border-line bg-canvas p-5" data-panel-tindakan="minta_perbaikan">
            @csrf
            <input type="hidden" name="tindakan" value="minta_perbaikan">

            <div>
                <h3 class="text-sm font-bold text-ink">Minta Perbaikan Berkas</h3>
                <p class="mt-1 text-xs text-muted">
                    Status menjadi Pending - Menunggu Perbaikan Berkas Pelapor.
                </p>
            </div>

            <div>
                <label for="catatan_perbaikan" class="{{ $kelasLabel }}">Catatan Perbaikan (Wajib)</label>
                <textarea id="catatan_perbaikan" name="catatan" rows="3" required minlength="5" maxlength="2000"
                    class="{{ $kelasArea }}" placeholder="Jelaskan berkas yang perlu diperbaiki..."></textarea>
                @include('verifikasi.bagian_override', ['id' => 'alasan_perbaikan'])
            </div>

            <x-ui.button type="submit" :variant="$disarankan === 'minta_perbaikan' ? 'primary' : 'secondary'"
                class="w-full sm:w-auto">
                Minta Perbaikan
            </x-ui.button>
        </form>

        <form method="POST" action="{{ route('verifikasi.revisi', $laporan) }}"
            class="space-y-4 rounded-2xl border border-line bg-canvas p-5" data-panel-tindakan="tolak_revisi">
            @csrf
            <input type="hidden" name="tindakan" value="tolak_revisi">

            <div>
                <h3 class="text-sm font-bold text-ink">Tolak / Butuh Revisi</h3>
                <p class="mt-1 text-xs text-muted">
                    Status menjadi Ditolak/Butuh Revisi beserta catatan untuk pemohon.
                </p>
            </div>

            <div>
                <label for="catatan_revisi" class="{{ $kelasLabel }}">Catatan Revisi (Wajib)</label>
                <textarea id="catatan_revisi" name="catatan" rows="3" required minlength="5" maxlength="2000"
                    class="{{ $kelasArea }}" placeholder="Jelaskan alasan penolakan..."></textarea>
                @include('verifikasi.bagian_override', ['id' => 'alasan_revisi'])
            </div>

            <x-ui.button type="submit" :variant="$disarankan === 'tolak_revisi' ? 'primary' : 'secondary'"
                class="w-full sm:w-auto">
                Kirim Catatan Revisi
            </x-ui.button>
        </form>

        <form method="POST" action="{{ route('verifikasi.anomali', $laporan) }}"
            class="space-y-4 rounded-2xl border border-line bg-canvas p-5" data-panel-tindakan="tandai_anomali">
            @csrf
            <input type="hidden" name="tindakan" value="tandai_anomali">

            <div>
                <h3 class="text-sm font-bold text-ink">Tandai Anomali</h3>
                <p class="mt-1 text-xs text-muted">Status menjadi Dalam Rekonsiliasi Anomali.</p>
            </div>

            <div class="space-y-3">
                <x-ui.select name="jenis_anomali" label="Jenis Anomali" placeholder="Pilih Jenis Anomali"
                    :options="$opsiAnomali" required />

                <div>
                    <label for="deskripsi" class="{{ $kelasLabel }}">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" required minlength="5" maxlength="2000"
                        class="{{ $kelasArea }}" placeholder="Jelaskan ketidaksesuaian..."></textarea>
                </div>

                @include('verifikasi.bagian_override', ['id' => 'alasan_anomali'])
            </div>

            <x-ui.button type="submit" :variant="$disarankan === 'tandai_anomali' ? 'primary' : 'secondary'"
                class="w-full sm:w-auto">
                Simpan ke Rekonsiliasi
            </x-ui.button>
        </form>

        <form method="POST" action="{{ route('verifikasi.tolak_fatal', $laporan) }}"
            class="space-y-4 rounded-2xl border border-line bg-canvas p-5" data-panel-tindakan="tolak_fatal">
            @csrf
            <input type="hidden" name="tindakan" value="tolak_fatal">

            <div>
                <h3 class="text-sm font-bold text-ink">Tolak Anomali Fatal</h3>
                <p class="mt-1 text-xs text-muted">
                    Status menjadi Ditolak - Anomali Data Fatal. Gunakan hanya untuk anomali fatal.
                </p>
            </div>

            <div>
                <label for="catatan_fatal" class="{{ $kelasLabel }}">Catatan Penolakan (Wajib)</label>
                <textarea id="catatan_fatal" name="catatan" rows="3" required minlength="5" maxlength="2000"
                    class="{{ $kelasArea }}" placeholder="Jelaskan alasan penolakan permanen..."></textarea>
                @include('verifikasi.bagian_override', ['id' => 'alasan_fatal'])
            </div>

            <x-ui.button type="submit" variant="danger" class="w-full sm:w-auto">Tolak Anomali Fatal</x-ui.button>
        </form>
    </div>
</div>
