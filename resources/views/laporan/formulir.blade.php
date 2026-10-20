@extends('tata_letak.utama')

@section('judul', $laporan === null ? 'Ajukan Laporan Baru' : ($laporan->adalahDraf() ? 'Ubah Draf Pengajuan' : 'Perbaiki Pengajuan'))

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Dashboard</a>
    <span class="text-sm text-gray-700">{{ $pengguna->nama_lengkap }}</span>
    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Keluar</button>
    </form>
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
            'tempat_kematian' => old('tempat_kematian', $laporan->tempat_kematian ?? ''),
            'sebab_kematian' => old('sebab_kematian', $laporan->sebab_kematian ?? ''),
        ];

        $kelasInput = 'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500';
        $kelasLabel = 'block text-sm font-medium text-gray-700';
        $kelasKartu = 'rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6';
        $kelasTombolUtama = 'rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900';
        $kelasTombolKedua = 'rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50';
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8">
        <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">
            {{ $laporan === null ? 'Ajukan Laporan Kematian' : ($laporan->adalahDraf() ? 'Lanjutkan Draf Pengajuan' : 'Perbaiki Pengajuan') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Lengkapi data pelapor, data almarhum, dan berkas pendukung. Setiap langkah tersimpan otomatis.
        </p>

        <ol class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ($judulLangkah as $indeks => $label)
                @php
                    $nomor = $indeks + 1;
                    $aktif = $nomor === $langkah;
                    $selesai = $nomor < $langkah;
                    $dapatDiklik = $nomor < $langkah;
                @endphp

                <li>
                    @if ($dapatDiklik)
                        <a href="?langkah={{ $nomor }}"
                           class="flex h-full items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-green-800 transition hover:border-green-400">
                    @else
                        <span @if ($aktif) aria-current="step" @endif
                              class="flex h-full items-center gap-2 rounded-lg border px-3 py-2 {{ $aktif ? 'border-slate-800 bg-slate-800 text-white shadow-sm' : 'border-gray-200 bg-gray-50 text-gray-400' }}">
                    @endif

                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                            {{ $aktif ? 'bg-white text-slate-900' : ($selesai ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                            @if ($selesai)
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l3 3 7-7" />
                                </svg>
                            @else
                                {{ $nomor }}
                            @endif
                        </span>

                        <span class="text-sm font-medium leading-tight">{{ $label }}</span>

                    @if ($dapatDiklik)
                        </a>
                    @else
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    @if ($langkah === 3)
        {{-- Langkah 3: setiap berkas memakai formulir sendiri agar hanya satu berkas terkirim. --}}
        <div class="mt-4 space-y-4 sm:mt-6">
            <section class="{{ $kelasKartu }}">
                <h2 class="text-lg font-semibold text-slate-800">Unggah Berkas Pendukung</h2>
                <p class="mt-1 text-sm text-gray-500">Format PDF, JPG, atau PNG. Maksimal 5 MB per berkas.</p>

                @if ($laporan === null)
                    <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        Simpan draf terlebih dahulu untuk mulai mengunggah berkas.
                    </p>
                @endif

                <div class="mt-4 space-y-3">
                    @foreach ($daftarJenis as $jenis)
                        @php
                            $berkas = $laporan?->berkasPendukung->firstWhere('jenis_dokumen', $jenis);
                            $terunggah = $berkas !== null && $berkas->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL;
                        @endphp

                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-800">{{ $jenis }} *</p>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $terunggah ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $terunggah ? 'Terunggah' : 'Belum diunggah' }}
                                </span>
                            </div>

                            @if ($berkas !== null)
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $berkas->nama_file }}
                                    &middot;
                                    <a href="{{ route('laporan.berkas', [$laporan, $berkas]) }}" target="_blank"
                                       class="text-slate-700 underline">Pratinjau</a>
                                </p>
                            @endif

                            @if ($laporan === null)
                                <input type="file" disabled
                                       class="mt-3 block w-full text-sm text-gray-400 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-400">
                            @else
                                <form method="POST" action="{{ route('laporan.berkas.unggah', $laporan) }}"
                                      enctype="multipart/form-data"
                                      class="mt-3 flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="jenis_dokumen" value="{{ $jenis }}">
                                    <input type="file" name="{{ $inputBerkas[$jenis] }}" accept=".pdf,.jpg,.jpeg,.png"
                                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700 sm:w-auto">
                                    <button type="submit"
                                            class="rounded-md bg-slate-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-800">
                                        Unggah
                                    </button>
                                </form>

                                @error($inputBerkas[$jenis])
                                    <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    @endforeach
                </div>

                @error('berkas')
                    <p class="mt-3 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <a href="?langkah=2" class="{{ $kelasTombolKedua }}">Kembali</a>
                <a href="?langkah=4" class="{{ $kelasTombolUtama }}">Lanjut</a>
            </div>
        </div>
    @else
        <form method="POST" action="{{ $aksiForm }}" enctype="multipart/form-data" class="mt-4 sm:mt-6">
            @csrf

            @if ($langkah === 1)
                {{-- Langkah 1: Data Pelapor --}}
                <section class="{{ $kelasKartu }}">
                    <h2 class="text-lg font-semibold text-slate-800">Data Pelapor</h2>
                    <p class="mt-1 text-sm text-gray-500">NIK, email, dan kontak mengikuti data akun Anda.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="nama_pelapor" class="{{ $kelasLabel }}">Nama Lengkap Pelapor *</label>
                            <input type="text" name="nama_pelapor" id="nama_pelapor"
                                   value="{{ $nilaiIsian['nama_pelapor'] }}" maxlength="100"
                                   class="{{ $kelasInput }} @error('nama_pelapor') border-red-400 @enderror">
                            @error('nama_pelapor')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="nik_pelapor" class="{{ $kelasLabel }}">NIK Pelapor</label>
                            <input type="text" id="nik_pelapor" value="{{ $pengguna->nik }}" readonly
                                   class="{{ $kelasInput }} border-gray-200 bg-gray-50 text-gray-600">
                        </div>

                        <div>
                            <label for="email_pelapor" class="{{ $kelasLabel }}">Alamat Email</label>
                            <input type="email" id="email_pelapor" value="{{ $pengguna->email }}" readonly
                                   class="{{ $kelasInput }} border-gray-200 bg-gray-50 text-gray-600">
                        </div>

                        <div>
                            <label for="kontak_pelapor" class="{{ $kelasLabel }}">Nomor HP/WhatsApp</label>
                            <input type="text" id="kontak_pelapor" value="{{ $pengguna->no_telepon ?? '-' }}" readonly
                                   class="{{ $kelasInput }} border-gray-200 bg-gray-50 text-gray-600">
                        </div>

                        <div>
                            <label for="hubungan_pelapor" class="{{ $kelasLabel }}">Hubungan dengan Almarhum *</label>
                            <select name="hubungan_pelapor" id="hubungan_pelapor"
                                    class="{{ $kelasInput }} @error('hubungan_pelapor') border-red-400 @enderror">
                                <option value="">Pilih hubungan</option>
                                @foreach ($daftarHubungan as $hubungan)
                                    <option value="{{ $hubungan }}" @selected($nilaiIsian['hubungan_pelapor'] === $hubungan)>
                                        {{ $hubungan }}
                                    </option>
                                @endforeach
                            </select>
                            @error('hubungan_pelapor')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>
            @elseif ($langkah === 2)
                {{-- Langkah 2: Data Almarhum --}}
                <section class="{{ $kelasKartu }}">
                    <h2 class="text-lg font-semibold text-slate-800">Data Almarhum/Almarhumah</h2>
                    <p class="mt-1 text-sm text-gray-500">Data ini akan dicocokkan dengan berkas kependudukan.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="nama_almarhum" class="{{ $kelasLabel }}">Nama Lengkap Almarhum *</label>
                            <input type="text" name="nama_almarhum" id="nama_almarhum"
                                   value="{{ $nilaiIsian['nama_almarhum'] }}" maxlength="100"
                                   class="{{ $kelasInput }} @error('nama_almarhum') border-red-400 @enderror">
                            @error('nama_almarhum')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="nik_almarhum" class="{{ $kelasLabel }}">NIK Almarhum *</label>
                            <input type="text" name="nik_almarhum" id="nik_almarhum" inputmode="numeric"
                                   value="{{ $nilaiIsian['nik_almarhum'] }}" maxlength="16"
                                   class="{{ $kelasInput }} @error('nik_almarhum') border-red-400 @enderror">
                            @error('nik_almarhum')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tanggal_kematian" class="{{ $kelasLabel }}">Tanggal Kematian *</label>
                            <input type="date" name="tanggal_kematian" id="tanggal_kematian"
                                   value="{{ $nilaiIsian['tanggal_kematian'] }}" max="{{ now()->toDateString() }}"
                                   class="{{ $kelasInput }} @error('tanggal_kematian') border-red-400 @enderror">
                            @error('tanggal_kematian')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tempat_kematian" class="{{ $kelasLabel }}">Tempat Kematian *</label>
                            <input type="text" name="tempat_kematian" id="tempat_kematian"
                                   value="{{ $nilaiIsian['tempat_kematian'] }}" maxlength="100"
                                   placeholder="Rumah sakit, kediaman, atau lokasi lain"
                                   class="{{ $kelasInput }} @error('tempat_kematian') border-red-400 @enderror">
                            @error('tempat_kematian')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="sebab_kematian" class="{{ $kelasLabel }}">Sebab Kematian *</label>
                            <textarea name="sebab_kematian" id="sebab_kematian" rows="3" maxlength="1000"
                                      class="{{ $kelasInput }} @error('sebab_kematian') border-red-400 @enderror">{{ $nilaiIsian['sebab_kematian'] }}</textarea>
                            @error('sebab_kematian')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>
            @else
                {{-- Langkah 4: Review --}}
                <section class="{{ $kelasKartu }}">
                    <h2 class="text-lg font-semibold text-slate-800">Review Pengajuan</h2>
                    <p class="mt-1 text-sm text-gray-500">Periksa kembali seluruh data dan berkas sebelum mengirim.</p>

                    @foreach ($nilaiIsian as $nama => $nilai)
                        <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
                    @endforeach
                    <input type="hidden" name="langkah" value="4">

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 p-4">
                            <h3 class="font-semibold text-slate-800">Data Pelapor</h3>
                            <dl class="mt-2 space-y-1 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Nama</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $nilaiIsian['nama_pelapor'] ?: '-' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">NIK</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $pengguna->nik }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Hubungan</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $nilaiIsian['hubungan_pelapor'] ?: '-' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Kontak</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $pengguna->no_telepon ?? '-' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Email</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $pengguna->email }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4">
                            <h3 class="font-semibold text-slate-800">Data Almarhum/Almarhumah</h3>
                            <dl class="mt-2 space-y-1 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Nama</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $nilaiIsian['nama_almarhum'] ?: '-' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">NIK</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $nilaiIsian['nik_almarhum'] ?: '-' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Tanggal kematian</dt>
                                    <dd class="text-right font-medium text-slate-800">
                                        {{ $nilaiIsian['tanggal_kematian'] ? \Illuminate\Support\Carbon::parse($nilaiIsian['tanggal_kematian'])->translatedFormat('j F Y') : '-' }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Tempat kematian</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $nilaiIsian['tempat_kematian'] ?: '-' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-500">Sebab kematian</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $nilaiIsian['sebab_kematian'] ?: '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h3 class="font-semibold text-slate-800">Berkas Pendukung</h3>
                        <ul class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200">
                            @foreach ($daftarJenis as $jenis)
                                @php
                                    $berkas = $laporan?->berkasPendukung->firstWhere('jenis_dokumen', $jenis);
                                    $terunggah = $berkas !== null && $berkas->status_unggahan === \App\Models\BerkasPendukung::STATUS_BERHASIL;
                                @endphp
                                <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                                    <span class="font-medium text-slate-700">{{ $jenis }}</span>
                                    <span class="flex items-center gap-3">
                                        @if ($terunggah)
                                            <a href="{{ route('laporan.berkas', [$laporan, $berkas]) }}" target="_blank"
                                               class="text-slate-700 underline">{{ $berkas->nama_file }}</a>
                                        @endif
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $terunggah ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $terunggah ? 'Terunggah' : 'Belum diunggah' }}
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <label class="mt-5 flex items-start gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="pernyataan" id="pernyataan" value="1" @checked(old('pernyataan'))
                               class="mt-0.5 rounded border-gray-300 text-slate-800 focus:ring-slate-500">
                        <span>Saya menyatakan bahwa data dan berkas yang saya lampirkan benar dan dapat dipertanggungjawabkan.</span>
                    </label>
                    @error('pernyataan')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </section>
            @endif

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                @if ($langkah > 1)
                    <a href="?langkah={{ $langkah - 1 }}" class="{{ $kelasTombolKedua }}">Kembali</a>
                @else
                    <a href="{{ route('dashboard') }}" class="{{ $kelasTombolKedua }}">Kembali</a>
                @endif

                <div class="flex flex-wrap items-center gap-2">
                    @if ($langkah === 4)
                        <button type="submit" class="{{ $kelasTombolUtama }}">
                            {{ ($laporan === null || $laporan->adalahDraf()) ? 'Simpan Draf' : 'Simpan Perubahan' }}
                        </button>

                        @if ($laporan !== null)
                            <button type="submit" formaction="{{ route('laporan.kirim', $laporan) }}" id="tombol-kirim" disabled
                                    class="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                                Kirim Pengajuan
                            </button>
                        @else
                            <button type="button" disabled title="Simpan draf terlebih dahulu"
                                    class="cursor-not-allowed rounded-md bg-gray-300 px-4 py-2 text-sm font-medium text-gray-600">
                                Kirim Pengajuan
                            </button>
                        @endif
                    @else
                        <button type="submit" name="langkah" value="{{ $langkah + 1 }}" class="{{ $kelasTombolUtama }}">
                            Lanjut
                        </button>
                    @endif
                </div>
            </div>
        </form>
    @endif

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
