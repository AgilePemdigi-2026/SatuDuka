// Tampilkan/sembunyikan kata sandi untuk semua tombol [data-tampilkan-sandi].
// Event delegation dipakai agar tetap bekerja pada konten yang dimuat ulang.
document.addEventListener('click', (peristiwa) => {
    const tombol = peristiwa.target.closest?.('[data-tampilkan-sandi]');

    if (!tombol) {
        return;
    }

    const kolom = document.getElementById(tombol.dataset.tampilkanSandi);

    if (!kolom) {
        return;
    }

    const terlihat = kolom.type === 'text';
    kolom.type = terlihat ? 'password' : 'text';

    tombol.setAttribute('aria-pressed', terlihat ? 'false' : 'true');
    tombol.setAttribute(
        'aria-label',
        terlihat ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi',
    );
});
