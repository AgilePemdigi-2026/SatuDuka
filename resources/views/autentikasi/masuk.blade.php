@extends('tata_letak.tamu')

@section('judul', 'Masuk')

@section('konten')
    <div class="text-center">
        <h1 class="text-[28px] font-bold leading-tight text-ink">Masuk ke Akun Anda</h1>
        <p class="mt-2 text-sm text-muted">Layanan Pencatatan &amp; Administrasi Kematian</p>
    </div>

    <form method="POST" action="{{ route('masuk.coba') }}" class="mt-8 space-y-5">
        @csrf

        <x-ui.input id="identitas" name="identitas" label="NIK / NIP" type="text" inputmode="numeric"
            maxlength="18" :value="old('identitas')" placeholder="Masukkan NIK atau NIP" autocomplete="username" required autofocus />

        <x-ui.password id="kata_sandi" name="kata_sandi" label="Kata Sandi" placeholder="Masukkan kata sandi"
            autocomplete="current-password" required />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-ink-soft">
                <input type="checkbox" name="ingat_saya" value="1"
                    class="h-4 w-4 rounded border-line-strong text-brand focus:ring-brand/30" />
                Ingat Saya
            </label>
            <a href="{{ route('lupa_kata_sandi') }}" class="text-sm font-semibold text-brand hover:text-brand-hover">
                Lupa Kata Sandi?
            </a>
        </div>

        <x-ui.button type="submit" class="w-full">Masuk</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Belum punya akun?
        <a href="{{ route('registrasi') }}" class="font-semibold text-brand hover:text-brand-hover">Daftar di sini</a>
    </p>
@endsection
