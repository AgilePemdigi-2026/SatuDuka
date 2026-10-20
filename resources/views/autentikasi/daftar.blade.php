@extends('tata_letak.utama')

@section('judul', 'Registrasi')

@section('konten')
    <div class="mx-auto max-w-md">
        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-center text-xl font-bold text-slate-800">Registrasi ke Akun Pemohon</h1>
            <p class="mt-1 text-center text-sm text-gray-500">Layanan Pencatatan &amp; Administrasi Kematian</p>

            <form method="POST" action="{{ route('registrasi.simpan') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="nik" class="block text-sm font-medium text-gray-700">Nomor Induk Kependudukan (NIK)</label>
                    <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16"
                        value="{{ old('nik') }}" placeholder="Masukkan NIK" required autofocus
                        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                            {{ $errors->has('nik') ? 'border-red-500' : 'border-gray-300' }}
                            focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                    @error('nik')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Alamat Surel</label>
                    <input id="email" name="email" type="email"
                        value="{{ old('email') }}" placeholder="nama@contoh.id" required
                        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                            {{ $errors->has('email') ? 'border-red-500' : 'border-gray-300' }}
                            focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="kata_sandi" class="block text-sm font-medium text-gray-700">Kata Sandi</label>
                    <div class="relative mt-1">
                        <input id="kata_sandi" name="kata_sandi" type="password"
                            placeholder="Masukkan kata sandi" autocomplete="new-password" required
                            class="block w-full rounded-md border px-3 py-2 pr-10 text-sm shadow-sm outline-none
                                {{ $errors->has('kata_sandi') ? 'border-red-500' : 'border-gray-300' }}
                                focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                        <button type="button" data-tampilkan-sandi="kata_sandi"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
                            aria-label="tampilkan kata sandi">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                    @error('kata_sandi')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="konfirmasi_kata_sandi" class="block text-sm font-medium text-gray-700">
                        Konfirmasi Kata Sandi
                    </label>
                    <div class="relative mt-1">
                        <input id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" type="password"
                            placeholder="Masukkan ulang kata sandi" autocomplete="new-password" required
                            class="block w-full rounded-md border px-3 py-2 pr-10 text-sm shadow-sm outline-none
                                {{ $errors->has('konfirmasi_kata_sandi') ? 'border-red-500' : 'border-gray-300' }}
                                focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                        <button type="button" data-tampilkan-sandi="konfirmasi_kata_sandi"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
                            aria-label="tampilkan kata sandi">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>
                    @error('konfirmasi_kata_sandi')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full rounded-md bg-slate-800 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Daftar
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-gray-600">
                Sudah punya akun?
                <a href="{{ route('masuk') }}" class="font-medium text-sky-700 hover:underline">Masuk di sini</a>
            </p>
        </div>
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
