@extends('tata_letak.utama')

@section('judul', 'Kelola Profil')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-soft hover:text-brand">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="space-y-6">
        <x-ui.page-header
            title="Kelola Profil"
            subtitle="Perbarui data diri, foto profil, dan kata sandi akun Anda."
        />

        <x-ui.card>
            <div class="space-y-6">

                {{-- FOTO PROFIL --}}
                <section>
                    <h2 class="text-base font-semibold text-ink">Foto Profil</h2>
                    <p class="mt-1 text-xs text-muted">Perbarui foto profil akun Anda.</p>

                    <div class="mt-5 flex flex-wrap items-center gap-5">
                        <img
                            src="{{ route('profil.foto.tampil') }}"
                            alt="Foto Profil {{ $pengguna->nama_lengkap }}"
                            class="h-24 w-24 rounded-full border border-line bg-canvas object-cover"
                        />

                        <form
                            method="POST"
                            action="{{ route('profil.foto') }}"
                            enctype="multipart/form-data"
                            class="flex flex-wrap items-end gap-3"
                        >
                            @csrf

                            <div>
                                <label for="foto" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                    Ganti Foto
                                </label>

                                <input
                                    id="foto"
                                    name="foto"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    required
                                    class="block w-full text-sm text-muted file:mr-3 file:rounded-xl
                                        file:border-0 file:bg-brand file:px-3 file:py-2
                                        file:text-sm file:font-semibold file:text-white
                                        hover:file:bg-brand-hover"
                                />

                                <p class="mt-1 text-xs text-muted">
                                    Format JPG/PNG, ukuran maksimal 2 MB.
                                </p>

                                @error('foto')
                                    <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                                @enderror
                            </div>

                            <x-ui.button type="submit">Unggah Foto</x-ui.button>
                        </form>
                    </div>
                </section>

                <div class="border-t border-line"></div>

                {{-- DATA DIRI --}}
                <section>
                    <h2 class="text-base font-semibold text-ink">Data Diri</h2>

                    <form
                        id="form-profil"
                        method="POST"
                        action="{{ route('profil.simpan') }}"
                        class="mt-5 space-y-5"
                    >
                        @csrf
                        @method('PUT')

                        <div class="grid gap-5 lg:grid-cols-2">
                            <div class="space-y-5">
                                <x-ui.input
                                    name="nama_lengkap"
                                    label="Nama Lengkap"
                                    maxlength="100"
                                    :value="$pengguna->nama_lengkap"
                                    autocomplete="name"
                                    required
                                />

                                <x-ui.input
                                    name="email"
                                    type="email"
                                    label="Alamat Surel"
                                    maxlength="100"
                                    :value="$pengguna->email"
                                    autocomplete="email"
                                    required
                                />
                            </div>

                            <div class="space-y-5">
                                @if ($pengguna->peran === \App\Models\Pengguna::PERAN_PEMOHON)
                                    <div>
                                        <label for="nik" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                            Nomor Induk Kependudukan (NIK)
                                        </label>

                                        <input
                                            id="nik"
                                            type="text"
                                            value="{{ $pengguna->nik }}"
                                            readonly
                                            class="block h-[46px] w-full cursor-not-allowed rounded-xl border border-line bg-canvas px-3.5 text-sm text-muted outline-none"
                                        />

                                        <p class="mt-1 text-xs text-muted">NIK tidak dapat diubah.</p>
                                    </div>
                                @else
                                    <div>
                                        <label for="nip" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                            Nomor Induk Pegawai (NIP)
                                        </label>

                                        <input
                                            id="nip"
                                            type="text"
                                            value="{{ $pengguna->nip }}"
                                            readonly
                                            class="block h-[46px] w-full cursor-not-allowed rounded-xl border border-line bg-canvas px-3.5 text-sm text-muted outline-none"
                                        />

                                        <p class="mt-1 text-xs text-muted">NIP tidak dapat diubah.</p>
                                    </div>
                                @endif

                                <x-ui.input
                                    name="no_telepon"
                                    label="Nomor Telepon"
                                    maxlength="15"
                                    placeholder="Contoh: 081234567890"
                                    :value="$pengguna->no_telepon"
                                    autocomplete="tel"
                                />
                            </div>
                        </div>

                        <div>
                            <label for="alamat" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                Alamat
                            </label>

                            <textarea
                                id="alamat"
                                name="alamat"
                                rows="3"
                                maxlength="1000"
                                class="block w-full rounded-xl border bg-surface px-3.5 py-2.5 text-sm text-ink outline-none transition
                                    placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30
                                    {{ $errors->has('alamat') ? 'border-[#ef4444]' : 'border-line-strong' }}"
                            >{{ old('alamat', $pengguna->alamat) }}</textarea>

                            @error('alamat')
                                <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                            @enderror
                        </div>
                    </form>
                </section>

                <div class="border-t border-line"></div>

                {{-- PENGATURAN KEAMANAN --}}
                <section>
                    <h2 class="text-base font-semibold text-ink">Pengaturan Keamanan</h2>

                    <p class="mt-1 text-sm text-muted">
                        Ubah kata sandi akun Anda secara berkala.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('profil.kata_sandi') }}"
                        class="mt-5 space-y-6"
                    >
                        @csrf
                        @method('PUT')

                        {{-- TIGA KOLOM KATA SANDI --}}
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">

                            {{-- KATA SANDI LAMA --}}
                            <div class="min-w-0">
                                <x-ui.password
                                    name="kata_sandi_lama"
                                    label="Kata Sandi Lama"
                                    autocomplete="current-password"
                                    required
                                />
                            </div>

                            {{-- KATA SANDI BARU --}}
                            <div class="min-w-0">
                                <x-ui.password
                                    name="kata_sandi_baru"
                                    label="Kata Sandi Baru"
                                    autocomplete="new-password"
                                    required
                                />

                                <div class="mt-2 flex justify-end">
                                    <p class="max-w-full text-right text-xs leading-relaxed text-muted">
                                        Minimal 8 karakter, mengandung huruf dan angka.
                                    </p>
                                </div>
                            </div>

                            {{-- KONFIRMASI KATA SANDI --}}
                            <div class="min-w-0">
                                <x-ui.password
                                    name="konfirmasi_kata_sandi"
                                    label="Konfirmasi Kata Sandi"
                                    autocomplete="new-password"
                                    required
                                />
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <x-ui.button type="submit">
                                Simpan Kata Sandi
                            </x-ui.button>
                        </div>
                    </form>
                </section>

                <div class="border-t border-line"></div>

                {{-- TOMBOL AKSI --}}
                <div class="flex justify-end gap-3">
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl
                            border border-line-strong bg-surface px-5 text-sm
                            font-semibold text-ink-soft transition hover:bg-canvas"
                    >
                        Batal
                    </a>

                    <x-ui.button type="submit" form="form-profil">
                        Simpan Perubahan
                    </x-ui.button>
                </div>

            </div>
        </x-ui.card>
    </div>
@endsection