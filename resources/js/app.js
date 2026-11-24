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

// Panel keputusan: satu dropdown [data-pilih-tindakan] memilih panel
// [data-panel-tindakan] yang tampil di dalam wadah [data-wadah-tindakan].
// Tanpa JavaScript semua panel tetap tampil sehingga halaman tidak rusak.
document.querySelectorAll('[data-pilih-tindakan]').forEach((pemilih) => {
    const wadah = document.querySelector(
        `[data-wadah-tindakan="${pemilih.dataset.pilihTindakan}"]`,
    );

    if (!wadah) {
        return;
    }

    const panel = wadah.querySelectorAll('[data-panel-tindakan]');

    const terapkan = () => {
        panel.forEach((item) => {
            item.hidden = item.dataset.panelTindakan !== pemilih.value;
        });
    };

    pemilih.addEventListener('change', terapkan);
    terapkan();
});
