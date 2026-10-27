@extends('tata_letak.utama')

@section('judul', 'Kelola Profil')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-soft hover:text-brand">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="space-y-6">
        <x-ui.page-header title="Kelola Profil"
            subtitle="Perbarui data diri, foto profil, dan kata sandi akun Anda." />

        <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
            <x-ui.card title="Informasi Akun">
                <div class="flex items-center gap-4">
                    <img src="{{ route('profil.foto.tampil') }}" alt="Foto Profil {{ $pengguna->nama_lengkap }}"
                        class="h-16 w-16 rounded-full border border-line object-cover" />

                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink">{{ $pengguna->nama_lengkap }}</p>
                        <p class="text-xs text-muted">{{ $pengguna->labelPeran() }}</p>
                    </div>
                </div>

                <dl class="mt-5 divide-y divide-line">
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <dt class="text-xs text-muted">Email</dt>
                        <dd class="text-right text-sm font-semibold text-ink">{{ $pengguna->email }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <dt class="text-xs text-muted">
                            {{ $pengguna->peran === \App\Models\Pengguna::PERAN_PEMOHON ? 'NIK' : 'NIP' }}
                        </dt>
                        <dd class="text-right font-mono text-xs font-semibold text-ink">
                            {{ $pengguna->peran === \App\Models\Pengguna::PERAN_PEMOHON ? $pengguna->nik : $pengguna->nip }}
                        </dd>
                    </div>
                </dl>

                <h2 class="mt-6 text-lg font-semibold text-ink">Foto Profil</h2>

                <form method="POST" action="{{ route('profil.foto') }}" enctype="multipart/form-data"
                    class="mt-3 space-y-3">
                    @csrf

                    <div>
                        <label for="foto" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                            Unggah atau Ubah Foto
                        </label>
                        <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required
                            class="block w-full text-sm text-muted file:mr-3 file:rounded-xl file:border-0
                                file:bg-brand file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white
                                hover:file:bg-brand-hover" />
                        <p class="mt-1 text-xs text-muted">Format JPG/PNG, ukuran maksimal 2 MB.</p>
                        @error('foto')
                            <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit" class="w-full">Unggah Foto</x-ui.button>
                </form>
            </x-ui.card>

            <x-ui.card title="Data Diri">
                <form method="POST" action="{{ route('profil.simpan') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-ui.input name="nama_lengkap" label="Nama Lengkap" maxlength="100"
                            :value="$pengguna->nama_lengkap" autocomplete="name" required />

                        <x-ui.input name="email" type="email" label="Alamat Surel" maxlength="100"
                            :value="$pengguna->email" autocomplete="email" required />
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        @if ($pengguna->peran === \App\Models\Pengguna::PERAN_PEMOHON)
                            <div>
                                <label for="nik" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                    Nomor Induk Kependudukan (NIK)
                                </label>
                                <input id="nik" type="text" value="{{ $pengguna->nik }}" readonly
                                    class="block h-[46px] w-full cursor-not-allowed rounded-xl border border-line bg-canvas px-3.5 text-sm text-muted outline-none" />
                                <p class="mt-1 text-xs text-muted">NIK tidak dapat diubah.</p>
                            </div>
                        @else
                            <div>
                                <label for="nip" class="mb-1.5 block text-sm font-semibold text-ink-soft">
                                    Nomor Induk Pegawai (NIP)
                                </label>
                                <input id="nip" type="text" value="{{ $pengguna->nip }}" readonly
                                    class="block h-[46px] w-full cursor-not-allowed rounded-xl border border-line bg-canvas px-3.5 text-sm text-muted outline-none" />
                                <p class="mt-1 text-xs text-muted">NIP tidak dapat diubah.</p>
                            </div>
                        @endif

                        <x-ui.input name="no_telepon" label="Nomor Telepon" maxlength="15"
                            placeholder="Contoh: 081234567890" :value="$pengguna->no_telepon"
                            autocomplete="tel" />
                    </div>

                    <div>
                        <label for="alamat" class="mb-1.5 block text-sm font-semibold text-ink-soft">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3" maxlength="1000"
                            class="block w-full rounded-xl border bg-surface px-3.5 py-2.5 text-sm text-ink outline-none transition
                                placeholder:text-faint focus:border-brand focus:ring-2 focus:ring-brand/30
                                {{ $errors->has('alamat') ? 'border-[#ef4444]' : 'border-line-strong' }}">{{ old('alamat', $pengguna->alamat) }}</textarea>
                        @error('alamat')
                            <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit">Simpan Perubahan</x-ui.button>
                </form>
            </x-ui.card>
        </div>

        <x-ui.card title="Pengaturan Keamanan">
            <p class="mb-4 text-sm text-muted">Ubah kata sandi akun Anda secara berkala.</p>

            <form method="POST" action="{{ route('profil.kata_sandi') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 lg:grid-cols-3">
                    <x-ui.password name="kata_sandi_lama" label="Kata Sandi Lama" autocomplete="current-password"
                        required :repopulate="false" />

                    <x-ui.password name="kata_sandi_baru" label="Kata Sandi Baru"
                        hint="Minimal 8 karakter, mengandung huruf dan angka." autocomplete="new-password" required
                        :repopulate="false" />

                    <x-ui.password name="konfirmasi_kata_sandi" label="Konfirmasi Kata Sandi"
                        autocomplete="new-password" required :repopulate="false" />
                </div>

                <x-ui.button type="submit">Simpan Perubahan</x-ui.button>
            </form>
        </x-ui.card>
    </div>

    @once
        <script>
            document.querySelectorAll('[data-tampilkan-sandi]').forEach((tombol) => {
                tombol.addEventListener('click', () => {
                    const kolom = document.getElementById(tombol.dataset.tampilkanSandi);
                    if (kolom) {
                        kolom.type = kolom.type === 'password' ? 'text' : 'password';
                    }
                });
            });
        </script>
    @endonce
@endsection
