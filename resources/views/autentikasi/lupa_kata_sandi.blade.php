@extends('tata_letak.tamu')

@section('judul', 'Lupa Kata Sandi')

@section('konten')
    <div class="text-center">
        <h1 class="text-[28px] font-bold leading-tight text-ink">Lupa Kata Sandi</h1>
        <p class="mt-2 text-sm text-muted">
            Masukkan NIK/NIP terdaftar; tautan pengaturan ulang dikirim ke surel akun Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('lupa_kata_sandi.kirim') }}" class="mt-8 space-y-5">
        @csrf

        <x-ui.input id="identitas" name="identitas" label="NIK/NIP" type="text" inputmode="numeric"
            maxlength="18" :value="old('identitas')" placeholder="Masukkan NIK atau NIP" required autofocus />

        <x-ui.button type="submit" class="w-full">Kirim</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        <a href="{{ route('masuk') }}" class="font-semibold text-brand hover:text-brand-hover">
            Kembali ke halaman Masuk
        </a>
    </p>
@endsection
