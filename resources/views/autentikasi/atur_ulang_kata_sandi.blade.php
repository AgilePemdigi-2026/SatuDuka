@extends('tata_letak.tamu')

@section('judul', 'Atur Ulang Kata Sandi')

@section('konten')
    <div class="text-center">
        <h1 class="text-[28px] font-bold leading-tight text-ink">Atur Ulang Kata Sandi</h1>
        <p class="mt-2 text-sm text-muted">
            Kata sandi minimal 8 karakter dan mengandung huruf serta angka.
        </p>
    </div>

    <form method="POST" action="{{ route('atur_ulang_kata_sandi.simpan') }}" class="mt-8 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}" />
        <input type="hidden" name="email" value="{{ $email }}" />

        <x-ui.password id="kata_sandi" name="kata_sandi" label="Kata Sandi Baru"
            placeholder="Masukkan kata sandi baru" hint="Minimal 8 karakter, mengandung huruf dan angka."
            autocomplete="new-password" required autofocus />

        <x-ui.password id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" label="Konfirmasi Kata Sandi"
            placeholder="Masukkan ulang kata sandi baru" autocomplete="new-password" required />

        @error('email')
            <p class="mt-1 text-xs font-medium text-[#b91c1c]">{{ $message }}</p>
        @enderror

        <x-ui.button type="submit" class="w-full">Simpan</x-ui.button>
    </form>
@endsection
