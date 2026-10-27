@extends('tata_letak.utama')

@section('judul', 'Kelola Profil')

@section('navigasi')
    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:underline">Dashboard</a>
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-800">Kelola Profil</h1>
        <p class="mt-1 text-sm text-gray-500">
            Perbarui data diri, foto profil, dan kata sandi akun Anda.
        </p>

        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            <section class="lg:col-span-1">
                <h2 class="text-lg font-semibold text-slate-800">Foto Profil</h2>

                <img src="{{ route('profil.foto.tampil') }}" alt="Foto Profil {{ $pengguna->nama_lengkap }}"
                    class="mt-4 h-24 w-24 rounded-full border border-gray-200 object-cover" />

                <form method="POST" action="{{ route('profil.foto') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf

                    <div>
                        <label for="foto" class="block text-sm font-medium text-gray-700">Unggah atau Ubah Foto</label>
                        <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required
                            class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0
                                file:bg-slate-800 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white" />
                        <p class="mt-1 text-xs text-gray-500">Format JPG/PNG, ukuran maksimal 2 MB.</p>
                        @error('foto')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                        Unggah Foto
                    </button>
                </form>
            </section>

            <section class="lg:col-span-2">
                <h2 class="text-lg font-semibold text-slate-800">Data Diri</h2>

                <form method="POST" action="{{ route('profil.simpan') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input id="nama_lengkap" name="nama_lengkap" type="text" maxlength="100"
                            value="{{ old('nama_lengkap', $pengguna->nama_lengkap) }}" required
                            class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                                {{ $errors->has('nama_lengkap') ? 'border-red-500' : 'border-gray-300' }}
                                focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                        @error('nama_lengkap')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($pengguna->peran === \App\Models\Pengguna::PERAN_PEMOHON)
                        <div>
                            <label for="nik" class="block text-sm font-medium text-gray-700">Nomor Induk Kependudukan (NIK)</label>
                            <input id="nik" type="text" value="{{ $pengguna->nik }}" readonly
                                class="mt-1 block w-full cursor-not-allowed rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-600 shadow-sm outline-none" />
                            <p class="mt-1 text-xs text-gray-500">NIK tidak dapat diubah.</p>
                        </div>
                    @else
                        <div>
                            <label for="nip" class="block text-sm font-medium text-gray-700">Nomor Induk Pegawai (NIP)</label>
                            <input id="nip" type="text" value="{{ $pengguna->nip }}" readonly
                                class="mt-1 block w-full cursor-not-allowed rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-600 shadow-sm outline-none" />
                            <p class="mt-1 text-xs text-gray-500">NIP tidak dapat diubah.</p>
                        </div>
                    @endif

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Alamat Surel</label>
                        <input id="email" name="email" type="email" maxlength="100"
                            value="{{ old('email', $pengguna->email) }}" required
                            class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                                {{ $errors->has('email') ? 'border-red-500' : 'border-gray-300' }}
                                focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="no_telepon" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                        <input id="no_telepon" name="no_telepon" type="text" maxlength="15"
                            value="{{ old('no_telepon', $pengguna->no_telepon) }}" placeholder="Contoh: 081234567890"
                            class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                                {{ $errors->has('no_telepon') ? 'border-red-500' : 'border-gray-300' }}
                                focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                        @error('no_telepon')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="alamat" class="block text-sm font-medium text-gray-700">Alamat</label>
                        <textarea id="alamat" name="alamat" rows="3" maxlength="1000"
                            class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                                {{ $errors->has('alamat') ? 'border-red-500' : 'border-gray-300' }}
                                focus:border-sky-500 focus:ring-1 focus:ring-sky-500">{{ old('alamat', $pengguna->alamat) }}</textarea>
                        @error('alamat')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                        Simpan Perubahan
                    </button>
                </form>
            </section>
        </div>

        <section class="mt-10 border-t border-gray-200 pt-8">
            <h2 class="text-lg font-semibold text-slate-800">Pengaturan Keamanan</h2>
            <p class="mt-1 text-sm text-gray-500">Ubah kata sandi akun Anda secara berkala.</p>

            <form method="POST" action="{{ route('profil.kata_sandi') }}" class="mt-4 max-w-md space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="kata_sandi_lama" class="block text-sm font-medium text-gray-700">Kata Sandi Lama</label>
                    <input id="kata_sandi_lama" name="kata_sandi_lama" type="password" autocomplete="current-password" required
                        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                            {{ $errors->has('kata_sandi_lama') ? 'border-red-500' : 'border-gray-300' }}
                            focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                    @error('kata_sandi_lama')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="kata_sandi_baru" class="block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
                    <input id="kata_sandi_baru" name="kata_sandi_baru" type="password" autocomplete="new-password" required
                        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                            {{ $errors->has('kata_sandi_baru') ? 'border-red-500' : 'border-gray-300' }}
                            focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                    <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter, mengandung huruf dan angka.</p>
                    @error('kata_sandi_baru')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="konfirmasi_kata_sandi" class="block text-sm font-medium text-gray-700">Konfirmasi Kata Sandi</label>
                    <input id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" type="password" autocomplete="new-password" required
                        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                            {{ $errors->has('konfirmasi_kata_sandi') ? 'border-red-500' : 'border-gray-300' }}
                            focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                    @error('konfirmasi_kata_sandi')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                    Simpan Perubahan
                </button>
            </form>
        </section>
    </div>
@endsection
