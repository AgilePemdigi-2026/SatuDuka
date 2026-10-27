@extends('tata_letak.tamu')

@section('judul', 'Registrasi ke Akun Pemohon')

@section('konten')
    <div class="text-center">
        <h1 class="text-[28px] font-bold leading-tight text-ink">Registrasi Akun Pemohon</h1>
        <p class="mt-2 text-sm text-muted">Layanan Pencatatan &amp; Administrasi Kematian</p>
    </div>

    <form method="POST" action="{{ route('registrasi.simpan') }}" class="mt-8 space-y-5">
        @csrf

        <x-ui.input id="nik" name="nik" label="Nomor Induk Kependudukan (NIK)" type="text" inputmode="numeric"
            maxlength="16" :value="old('nik')" placeholder="Masukkan NIK" required autofocus />

        <x-ui.input id="email" name="email" label="Alamat Surel" type="email" :value="old('email')"
            placeholder="nama@contoh.id" required />

        <x-ui.password id="kata_sandi" name="kata_sandi" label="Kata Sandi" placeholder="Masukkan kata sandi"
            autocomplete="new-password" required />

        <x-ui.password id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" label="Konfirmasi Kata Sandi"
            placeholder="Masukkan ulang kata sandi" autocomplete="new-password" required />

        <x-ui.button type="submit" class="w-full">Daftar</x-ui.button>
    </form>

    <p class="mt-6 text-right text-sm text-muted">
        Sudah punya akun?
        <a href="{{ route('masuk') }}" class="font-semibold text-brand hover:text-brand-hover">Masuk di sini</a>
    </p>

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
