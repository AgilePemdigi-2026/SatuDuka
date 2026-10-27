@extends('tata_letak.utama')

@section('judul', $laporan === null ? 'Ajukan Laporan Baru' : ($laporan->adalahDraf() ? 'Ubah Draf Pengajuan' : 'Perbaiki Pengajuan'))

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-soft hover:text-brand">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    @php
        $judulLangkah = ['Data Pelapor', 'Data Almarhum', 'Unggah Berkas', 'Review'];
        $aksiForm = $laporan === null ? route('laporan.draf') : route('laporan.draf.ubah', $laporan);

        $nilaiIsian = [
            'nama_pelapor' => old('nama_pelapor', $laporan->nama_pelapor ?? $pengguna->nama_lengkap),
            'hubungan_pelapor' => old('hubungan_pelapor', $laporan->hubungan_pelapor ?? ''),
            'nama_almarhum' => old('nama_almarhum', $laporan->nama_almarhum ?? ''),
            'nik_almarhum' => old('nik_almarhum', $laporan->nik_almarhum ?? ''),
            'tanggal_kematian' => old('tanggal_kematian', $laporan?->tanggal_kematian?->format('Y-m-d') ?? ''),
            'jenis_kasus' => old('jenis_kasus', $laporan?->jenis_kasus ?? \App\Models\LaporanKematian::JENIS_KASUS_NORMAL),
            'tempat_kematian' => old('tempat_kematian', $laporan->tempat_kematian ?? ''),
            'sebab_kematian' => old('sebab_kematian', $laporan->sebab_kematian ?? ''),
        ];

        $kelasBaca = 'block h-[46px] w-full cursor-not-allowed rounded-xl border border-line bg-canvas px-3.5 text-sm text-muted outline-none';
    @endphp

    <div class="space-y-6">
        <x-ui.page-header
            :title="$laporan === null ? 'Ajukan Laporan Kematian' : ($laporan->adalahDraf() ? 'Lanjutkan Draf Pengajuan' : 'Perbaiki Pengajuan')"
            subtitle="Lengkapi data pelapor, data almarhum, dan berkas pendukung. Data tersimpan saat Anda menekan Lanjut." />

        <x-ui.wizard :steps="$judulLangkah" :current="$langkah" tautkan />

        @if ($langkah === 3)
            {{-- Langkah 3: setiap berkas memakai formulir sendiri agar hanya satu berkas terkirim. --}}
            <div class="space-y-4">
                <x-ui.card title="Unggah Berkas Pendukung">
                    <p class="text-sm text-muted">Format PDF, JPG, atau PNG. Maksimal 5 MB per berkas.</p>

                    @if ($laporan === null)
                        <x-ui.alert variant="warning" class="mt-4">
                            Simpan draf terlebih dahulu untuk mulai mengunggah berkas.
                        </x-ui.alert>
                    @endif

                    <div class="mt-4 space-y-6">
                        @foreach ($kelompokBerkas['kelompok'] as $kelompok)
                            <section>
                                <h3 class="text-sm font-semibold text-ink">
                                    {{ $kelompok['judul'] }}
                                    @if ($kelompok['wajib'])
                                        <span class="text-[#ef4444]">*</span>
                                    @endif
                                </h3>
                                <p class="mt-1 text-xs text-muted">{{ $kelompok['petunjuk'] }}</p>

                                <div class="mt-3 space-y-3">
                                    @foreach ($kelompok['jenis'] as $jenis)
                                        @php
                                            $berkas = $laporan?->berkasPendukung->firstWhere('jenis_dokumen', $jenis);
                                            $terunggah = $berkas !== null && $berkas->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL;
                                            $tandaBersyarat = [];

                                            if (in_array($jenis, $kelompokBerkas['keterlambatan'], true)) {
                                                $tandaBersyarat[] = 'Wajib karena keterlambatan';
                                            }

                                            if (in_array($jenis, $kelompokBerkas['kasus_khusus'], true)) {
                                                $tandaBersyarat[] = 'Wajib karena jenis kasus khusus';
                                            }
                                        @endphp

                                        <div class="rounded-xl border border-line p-4">
                                            <div class="flex items-start gap-3">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand">
                                                    <x-icon.arrow-down-tray class="h-5 w-5" />
                                                </span>

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <p class="text-sm font-semibold text-ink-soft">
                                                            {{ $jenis }}
                                                            @if ($kelompok['pilih_satu'])
                                                                <span class="font-normal text-muted">(pilih salah satu)</span>
                                                            @elseif (! $kelompok['wajib'])
                                                                <span class="font-normal text-muted">(opsional)</span>
                                                            @endif
                                                        </p>
                                                        <x-ui.badge :variant="$terunggah ? 'success' : 'neutral'">
                                                            {{ $terunggah ? 'Terunggah' : 'Belum diunggah' }}
                                                        </x-ui.badge>
                                                    </div>

                                                    @if ($tandaBersyarat !== [])
                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            @foreach ($tandaBersyarat as $tanda)
                                                                <x-ui.badge variant="warning">{{ $tanda }}</x-ui.badge>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if ($berkas !== null)
                                                        <p class="mt-1 text-xs text-muted">
                                                            {{ $berkas->nama_file }}
                                                            &middot;
                                                            <a href="{{ route('laporan.berkas', [$laporan, $berkas]) }}" target="_blank"
                                                                class="font-semibold text-brand hover:text-brand-hover">Pratinjau</a>
                                                        </p>
                                                    @endif

                                                    @if ($laporan === null)
                                                        <div class="mt-3 rounded-xl border border-dashed border-line-strong bg-canvas p-3">
                                                            <input type="file" disabled
                                                                class="block w-full text-sm text-faint file:mr-3 file:rounded-xl file:border-0 file:bg-line file:px-3 file:py-2 file:text-sm file:font-semibold file:text-muted">
                                                        </div>
                                                    @else
                                                        <form method="POST" action="{{ route('laporan.berkas.unggah', $laporan) }}"
                                                            enctype="multipart/form-data"
                                                            class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-dashed border-line-strong bg-canvas p-3">
                                                            @csrf
                                                            <input type="hidden" name="jenis_dokumen" value="{{ $jenis }}">
                                                            <input type="file" name="{{ $inputBerkas[$jenis] }}" accept=".pdf,.jpg,.jpeg,.png"
                                                                class="block w-full min-w-0 flex-1 text-sm text-muted file:mr-3 file:rounded-xl file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-hover">
                                                            <x-ui.button type="submit" size="sm">Unggah</x-ui.button>
                                                        </form>

                                                        @error($inputBerkas[$jenis])
                                                            <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                                                        @enderror
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    @error('berkas')
                        <p class="mt-3 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                    @enderror
                </x-ui.card>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-ui.button :href="'?langkah=2'" variant="secondary">Kembali</x-ui.button>
                    <x-ui.button :href="'?langkah=4'">Lanjut</x-ui.button>
                </div>
            </div>
        @else
            <form method="POST" action="{{ $aksiForm }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                @if ($langkah === 1)
                    {{-- Langkah 1: Data Pelapor --}}
                    <x-ui.card title="Data Pelapor">
                        <p class="text-sm text-muted">NIK, email, dan kontak mengikuti data akun Anda.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-ui.input name="nama_pelapor" id="nama_pelapor" label="Nama Lengkap Pelapor *"
                                    :value="$nilaiIsian['nama_pelapor']" maxlength="100" />
                            </div>

                            <div>
                                <label for="nik_pelapor" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                    NIK Pelapor
                                </label>
                                <input type="text" id="nik_pelapor" value="{{ $pengguna->nik }}" readonly
                                    class="{{ $kelasBaca }}" />
                            </div>

                            <div>
                                <label for="email_pelapor" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                    Alamat Email
                                </label>
                                <input type="email" id="email_pelapor" value="{{ $pengguna->email }}" readonly
                                    class="{{ $kelasBaca }}" />
                            </div>

                            <div>
                                <label for="kontak_pelapor" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                    Nomor HP/WhatsApp
                                </label>
                                <input type="text" id="kontak_pelapor" value="{{ $pengguna->no_telepon ?? '-' }}" readonly
                                    class="{{ $kelasBaca }}" />
                            </div>

                            <div>
                                <x-ui.select name="hubungan_pelapor" id="hubungan_pelapor"
                                    label="Hubungan dengan Almarhum *" :options="$daftarHubungan"
                                    :value="$nilaiIsian['hubungan_pelapor']" placeholder="Pilih hubungan" />
                            </div>
                        </div>
                    </x-ui.card>
                @elseif ($langkah === 2)
                    {{-- Langkah 2: Data Almarhum --}}
                    <x-ui.card title="Data Almarhum/Almarhumah">
                        <p class="text-sm text-muted">Data ini akan dicocokkan dengan berkas kependudukan.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <x-ui.input name="nama_almarhum" id="nama_almarhum" label="Nama Lengkap Almarhum *"
                                :value="$nilaiIsian['nama_almarhum']" maxlength="100" />

                            <x-ui.input name="nik_almarhum" id="nik_almarhum" label="NIK Almarhum *"
                                :value="$nilaiIsian['nik_almarhum']" maxlength="16" inputmode="numeric" />

                            <x-ui.input name="tanggal_kematian" id="tanggal_kematian" type="date"
                                label="Tanggal Kematian *" :value="$nilaiIsian['tanggal_kematian']"
                                :max="now()->toDateString()" />

                            <x-ui.select name="jenis_kasus" id="jenis_kasus" label="Jenis Kasus *"
                                :options="$daftarJenisKasus" :value="$nilaiIsian['jenis_kasus']" />

                            <x-ui.input name="tempat_kematian" id="tempat_kematian" label="Tempat Kematian *"
                                :value="$nilaiIsian['tempat_kematian']" maxlength="100"
                                placeholder="Rumah sakit, kediaman, atau lokasi lain" />

                            <div class="sm:col-span-2">
                                <label for="sebab_kematian" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                    Sebab Kematian *
                                </label>
                                <textarea name="sebab_kematian" id="sebab_kematian" rows="3" maxlength="1000"
                                    class="block w-full rounded-xl border bg-surface px-3.5 py-2.5 text-sm text-ink outline-none transition placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30
                                        {{ $errors->has('sebab_kematian') ? 'border-[#ef4444]' : 'border-line-strong' }}">{{ $nilaiIsian['sebab_kematian'] }}</textarea>
                                @error('sebab_kematian')
                                    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </x-ui.card>
                @else
                    {{-- Langkah 4: Review --}}
                    <x-ui.card title="Review Pengajuan">
                        <p class="text-sm text-muted">Periksa kembali seluruh data dan berkas sebelum mengirim.</p>

                        @foreach ($nilaiIsian as $nama => $nilai)
                            <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
                        @endforeach
                        <input type="hidden" name="langkah" value="4">

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-line bg-canvas p-4">
                                <h3 class="text-sm font-semibold text-ink">Data Pelapor</h3>
                                <dl class="mt-2 space-y-1 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Nama</dt>
                                        <dd class="text-right font-semibold text-ink">{{ $nilaiIsian['nama_pelapor'] ?: '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">NIK</dt>
                                        <dd class="text-right font-mono text-xs font-semibold text-ink">{{ $pengguna->nik }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Hubungan</dt>
                                        <dd class="text-right font-semibold text-ink">{{ $nilaiIsian['hubungan_pelapor'] ?: '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Kontak</dt>
                                        <dd class="text-right font-semibold text-ink">{{ $pengguna->no_telepon ?? '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Email</dt>
                                        <dd class="text-right font-semibold text-ink">{{ $pengguna->email }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="rounded-xl border border-line bg-canvas p-4">
                                <h3 class="text-sm font-semibold text-ink">Data Almarhum/Almarhumah</h3>
                                <dl class="mt-2 space-y-1 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Nama</dt>
                                        <dd class="text-right font-semibold text-ink">{{ $nilaiIsian['nama_almarhum'] ?: '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">NIK</dt>
                                        <dd class="text-right font-mono text-xs font-semibold text-ink">{{ $nilaiIsian['nik_almarhum'] ?: '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Tanggal kematian</dt>
                                        <dd class="text-right font-semibold text-ink">
                                            {{ $nilaiIsian['tanggal_kematian'] ? \Illuminate\Support\Carbon::parse($nilaiIsian['tanggal_kematian'])->translatedFormat('j F Y') : '-' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Jenis kasus</dt>
                                        <dd class="text-right font-semibold text-ink">{{ $nilaiIsian['jenis_kasus'] ?: '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Tempat kematian</dt>
                                        <dd class="text-right font-semibold text-ink">{{ $nilaiIsian['tempat_kematian'] ?: '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-muted">Sebab kematian</dt>
                                        <dd class="text-right font-semibold text-ink">{{ $nilaiIsian['sebab_kematian'] ?: '-' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-ink">Berkas Pendukung</h3>
                            <ul class="mt-2 divide-y divide-line rounded-xl border border-line">
                                @foreach ($daftarJenis as $jenis)
                                    @php
                                        $berkas = $laporan?->berkasPendukung->firstWhere('jenis_dokumen', $jenis);
                                        $terunggah = $berkas !== null && $berkas->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL;
                                    @endphp
                                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                                        <span class="font-semibold text-ink-soft">{{ $jenis }}</span>
                                        <span class="flex items-center gap-3">
                                            @if ($terunggah)
                                                <a href="{{ route('laporan.berkas', [$laporan, $berkas]) }}" target="_blank"
                                                    class="font-semibold text-brand hover:text-brand-hover">{{ $berkas->nama_file }}</a>
                                            @endif
                                            <x-ui.badge :variant="$terunggah ? 'success' : 'neutral'">
                                                {{ $terunggah ? 'Terunggah' : 'Belum diunggah' }}
                                            </x-ui.badge>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="mt-4 rounded-xl border border-line bg-canvas p-4">
                            <h3 class="text-sm font-semibold text-ink">Pemeriksaan Dokumen (AI)</h3>
                            <p class="mt-1 text-sm text-muted">
                                Hasil bantuan AI, hanya saran. Pemeriksaan ini tidak menghalangi pengiriman pengajuan.
                            </p>

                            @if ($laporan !== null)
                                <x-ui.button type="submit" form="form-analisis-ai" variant="secondary" class="mt-3">
                                    Periksa dokumen (AI)
                                </x-ui.button>
                            @else
                                <p class="mt-3 text-sm text-muted">Simpan draf terlebih dahulu untuk memeriksa dokumen.</p>
                            @endif

                            @if (session('analisis_ai'))
                                @include('laporan.panel_analisis_ai', ['analisis' => session('analisis_ai')])
                            @endif
                        </div>

                        <label class="mt-5 flex items-start gap-2 text-sm text-ink-soft">
                            <input type="checkbox" name="pernyataan" id="pernyataan" value="1" @checked(old('pernyataan'))
                                class="mt-0.5 rounded border-line-strong text-brand focus:ring-brand/30">
                            <span>Saya menyatakan bahwa data dan berkas yang saya lampirkan benar dan dapat dipertanggungjawabkan.</span>
                        </label>
                        @error('pernyataan')
                            <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror
                    </x-ui.card>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-3">
                    @if ($langkah > 1)
                        <x-ui.button :href="'?langkah='.($langkah - 1)" variant="secondary">Kembali</x-ui.button>
                    @else
                        <x-ui.button :href="route('dashboard')" variant="secondary">Kembali</x-ui.button>
                    @endif

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($langkah === 4)
                            <x-ui.button type="submit">
                                {{ ($laporan === null || $laporan->adalahDraf()) ? 'Simpan Draf' : 'Simpan Perubahan' }}
                            </x-ui.button>

                            @if ($laporan !== null)
                                <x-ui.button type="submit" formaction="{{ route('laporan.kirim', $laporan) }}" id="tombol-kirim"
                                    disabled>
                                    Kirim Pengajuan
                                </x-ui.button>
                            @else
                                <x-ui.button type="button" disabled title="Simpan draf terlebih dahulu">
                                    Kirim Pengajuan
                                </x-ui.button>
                            @endif
                        @else
                            <x-ui.button type="submit" name="langkah" value="{{ $langkah + 1 }}">
                                Lanjut
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            </form>

            @if ($laporan !== null)
                <form id="form-analisis-ai" method="POST" action="{{ route('laporan.periksa_ai', $laporan) }}" class="hidden">
                    @csrf
                </form>
            @endif
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var pernyataan = document.getElementById('pernyataan');
            var tombolKirim = document.getElementById('tombol-kirim');

            if (!pernyataan || !tombolKirim) {
                return;
            }

            var perbaruiTombol = function () {
                tombolKirim.disabled = !pernyataan.checked;
            };

            pernyataan.addEventListener('change', perbaruiTombol);
            perbaruiTombol();
        });
    </script>
@endsection
