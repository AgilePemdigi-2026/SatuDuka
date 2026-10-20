@extends('tata_letak.utama')

@section('judul', 'Kata Sandi Baru')

@section('konten')
    <div class="mx-auto max-w-md">
        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-center text-xl font-bold text-slate-800">Kata Sandi Baru</h1>
            <p class="mt-1 text-center text-sm text-gray-500">
                Kata sandi minimal 8 karakter dan mengandung huruf serta angka.
            </p>

            <form method="POST" action="{{ route('atur_ulang_kata_sandi.simpan') }}" class="mt-6 space-y-4">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}" />
                <input type="hidden" name="email" value="{{ $email }}" />

                <div>
                    <label for="kata_sandi" class="block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
                    <input id="kata_sandi" name="kata_sandi" type="password"
                        placeholder="Masukkan kata sandi baru" autocomplete="new-password" required autofocus
                        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                            {{ $errors->has('kata_sandi') ? 'border-red-500' : 'border-gray-300' }}
                            focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                    @error('kata_sandi')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="konfirmasi_kata_sandi" class="block text-sm font-medium text-gray-700">
                        Konfirmasi Kata Sandi
                    </label>
                    <input id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" type="password"
                        placeholder="Masukkan ulang kata sandi baru" autocomplete="new-password" required
                        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                            {{ $errors->has('konfirmasi_kata_sandi') ? 'border-red-500' : 'border-gray-300' }}
                            focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                    @error('konfirmasi_kata_sandi')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full rounded-md bg-slate-800 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Simpan
                </button>
            </form>
        </div>
    </div>
@endsection
