@extends('tata_letak.utama')

@section('judul', 'Lupa Kata Sandi')

@section('konten')
    <div class="mx-auto max-w-md">
        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-center text-xl font-bold text-slate-800">Lupa Kata Sandi</h1>
            <p class="mt-1 text-center text-sm text-gray-500">
                Masukkan NIK/NIP terdaftar; tautan pengaturan ulang dikirim ke surel akun Anda.
            </p>

            <form method="POST" action="{{ route('lupa_kata_sandi.kirim') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="identitas" class="block text-sm font-medium text-gray-700">NIK/NIP</label>
                    <input id="identitas" name="identitas" type="text" inputmode="numeric"
                        value="{{ old('identitas') }}" placeholder="Masukkan NIK atau NIP" required autofocus
                        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm outline-none
                            {{ $errors->has('identitas') ? 'border-red-500' : 'border-gray-300' }}
                            focus:border-sky-500 focus:ring-1 focus:ring-sky-500" />
                    @error('identitas')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full rounded-md bg-slate-800 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Kirim
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-gray-600">
                <a href="{{ route('masuk') }}" class="font-medium text-sky-700 hover:underline">Kembali ke halaman Masuk</a>
            </p>
        </div>
    </div>
@endsection
