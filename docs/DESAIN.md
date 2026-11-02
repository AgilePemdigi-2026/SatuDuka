# DESAIN.md — Sistem Desain UI SATUDUKA

Acuan tunggal untuk tampilan. Diturunkan dari mockup SVG (Login, Registrasi, Lacak Status,
Halaman Tanda Tangan, Detail Dokumen) dan kode Blade yang ada. Stack: Laravel Blade + Tailwind v4 (Vite).

> Nilai bertanda `~` adalah hasil pengukuran dari render mockup (teks di SVG sudah berupa outline),
> jadi akurat dalam kisaran 1–2 px. Jika ada konflik dengan Figma asli, Figma yang menang.

## 0. Aturan keras (jangan dilanggar)

1. Ubah **hanya tampilan**: file `resources/views/**`, `resources/css/app.css`, `resources/js/app.js`.
   Jangan ubah controller, route, nama field form, atribut `name`/`id`, validasi, atau logika `@if/@foreach`.
2. Semua teks dan label tetap **Bahasa Indonesia**.
3. Jangan pakai CDN. Font dimuat lewat `@import` / `<link>` di layout (lihat §2) dan ikon berupa SVG sebaris.
4. Tanpa warna di luar token §1. Dilarang `gray-*`, `sky-*`, `indigo-*` mentah; pakai token (`ink`, `muted`, `line`, `brand`, dst.).
5. Setiap pola yang berulang ≥ 2 kali harus jadi komponen Blade (§4), bukan salin-tempel class.

## 1. Token warna

Daftarkan di `resources/css/app.css` dalam `@theme` (Tailwind v4 otomatis membuat utilitas
`bg-brand`, `text-ink`, `border-line`, dst.).

| Token | Hex | Pakai untuk |
|---|---|---|
| `canvas` | `#f8fafc` | latar halaman |
| `surface` | `#ffffff` | kartu, header, input |
| `line` | `#e2e8f0` | border kartu, pemisah, garis tabel |
| `line-strong` | `#cbd5e1` | border input, garis stepper |
| `ink` | `#0f172a` | judul, teks utama |
| `ink-soft` | `#334155` | label, teks isi |
| `muted` | `#64748b` | teks sekunder, subjudul |
| `faint` | `#94a3b8` | placeholder, caption, footer |
| `brand` | `#1e3a5f` | tombol utama, tautan penting, stepper selesai |
| `brand-hover` | `#17304f` | hover tombol utama |
| `brand-soft` | `#eff6ff` | panel info/verifikasi (dengan border `#bfdbfe`) |
| `link` | `#1d4ed8` | tautan inline / otoritas |
| `gold` | `#8e7036` | lambang pada pratinjau akta |

Warna status (latar / teks / border), dipakai oleh `<x-ui.badge>` dan kartu statistik:

| Varian | Latar | Teks | Border |
|---|---|---|---|
| `warning` (menunggu) | `#fef3c7` | `#b45309` | `#f59e0b` |
| `success` (selesai) | `#dcfce7` | `#15803d` | `#22c55e` |
| `danger` (dikembalikan/ditolak) | `#fee2e2` | `#b91c1c` | `#ef4444` |
| `info` (diproses/ditinjau) | `#d9f1fb` | `#1e3a5f` | `#a5dcf3` |
| `neutral` (draf) | `#f1f5f9` | `#475569` | `#e2e8f0` |
| `purple` (rekonsiliasi) | `#f3e8ff` | `#6b21a8` | `#d8b4fe` |

Pemetaan status laporan → varian (ganti `match` di `komponen/badge_status.blade.php`):

| Konstanta `LaporanKematian::` | Varian |
|---|---|
| `STATUS_DRAF` | `neutral` |
| `STATUS_MENUNGGU_VERIFIKASI` | `info` |
| `STATUS_DALAM_REKONSILIASI` | `purple` |
| `STATUS_MENUNGGU_PERBAIKAN`, `STATUS_DITOLAK_BUTUH_REVISI` | `danger` |
| `STATUS_DIKEMBALIKAN` | `warning` |
| `STATUS_DISETUJUI_MENUNGGU_TTE` | `info` |
| `STATUS_SELESAI` | `success` |

## 2. Tipografi

- Sans: **Plus Jakarta Sans** (400, 500, 600, 700, 800). Muat dari Bunny Fonts (tanpa pelacakan Google):
  `https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800`
- Mono (nomor pengajuan, NIK): `ui-monospace, 'JetBrains Mono', 'Courier New', monospace`
- Serif (hanya pratinjau akta): `'Noto Serif', Georgia, serif`
- Angka tabel pakai `tabular-nums`.

| Peran | Ukuran / bobot | Warna |
|---|---|---|
| Merek "SKD" (halaman tamu) | 52px / 800 / tracking rapat | `ink` |
| Tagline merek | 20px / 500 | `ink-soft` |
| Judul kartu auth | 28px / 700 | `ink` |
| Judul halaman (h1) | 30px / 700 | `ink` |
| Subjudul halaman | 14–16px / 400 | `muted` |
| Judul seksi (h2) | 18px / 600 | `ink` |
| Label form | 14px / 600 | `ink-soft` |
| Teks isi | 14px / 400 | `ink-soft` |
| Caption, footer | 12px / 400 | `faint` |
| Header tabel | 12px / 700 / uppercase / tracking-wider | `ink-soft` |
| Angka statistik | 48px / 800 | warna varian |

## 3. Bentuk, jarak, bayangan

- Skala jarak: kelipatan 4px. Padding kartu 32px (desktop) / 20px (ponsel).
- Radius: input & tombol `12px` (`rounded-xl`), kartu `16px` (`rounded-2xl`), badge `9999px`, kotak kecil/pagination `8px`.
- Tinggi kontrol: input `46px`, tombol utama `48px`, tombol kecil (tabel) `32px`.
- Border kartu 1px `line`. Bayangan sangat tipis: `0 1px 2px rgb(15 23 42 / 0.04)`. Jangan memakai `shadow-lg`.
- Lebar konten: kartu auth `max-w-[520px]`, halaman daftar/tabel `max-w-6xl`, halaman lacak status `max-w-3xl`.
- Fokus: ring 2px `brand` / 30% opasitas, border input jadi `brand`. Jangan hapus outline tanpa pengganti.

## 4. Komponen (Blade anonymous components)

Letakkan di `resources/views/components/ui/`. Pemakaian: `<x-ui.button>…</x-ui.button>`.

| Komponen | File | Props | Catatan gaya |
|---|---|---|---|
| Tombol | `button.blade.php` | `variant` = primary\|secondary\|ghost\|danger, `size` = md\|sm, `href` (opsional → render `<a>`), `type` | primary: `bg-brand text-white rounded-xl h-12 font-semibold`; secondary: putih, border `line-strong`, teks `ink-soft`; sm: `h-8 px-4 text-xs uppercase tracking-wide` |
| Input | `input.blade.php` | `name`, `label`, `type`, `placeholder`, `value`, `hint`, `required` | label di atas; error otomatis dari `$errors->first($name)`; border merah `danger` saat error; pass-through `$attributes` |
| Input sandi | `password.blade.php` | seperti input | tombol mata di kanan; ikon `eye` / `eye-slash` SVG sebaris; pakai script toggle yang sudah ada (`data-tampilkan-sandi`) |
| Select | `select.blade.php` | `name`, `label`, `options` | tinggi & radius sama dengan input, chevron SVG |
| Kartu | `card.blade.php` | `title` (opsional), `padding` | `bg-surface border border-line rounded-2xl` |
| Lencana | `badge.blade.php` | `variant` | pil, `px-3 py-1 text-xs font-bold uppercase tracking-wide`, warna §1 |
| Kartu statistik | `stat.blade.php` | `label`, `value`, `variant` | latar & border varian, label 14/600 di tengah, angka 48/800 di tengah |
| Tabel | `table.blade.php` | slot `head`, slot isi | pembungkus `rounded-2xl border border-line overflow-hidden`, `thead` latar `#e8edf4`, baris `border-t border-line`, hover `bg-canvas`; **bungkus `overflow-x-auto` agar aman di ponsel** |
| Notifikasi | `alert.blade.php` | `variant` = success\|info\|danger | menggantikan blok flash di layout |
| Stepper vertikal | `stepper.blade.php` | `steps` (array: judul, waktu, selesai) | lingkaran 28px `brand` dengan centang putih; langkah belum selesai: lingkaran `line-strong`; garis penghubung 2px `line-strong` |
| Stepper form | `wizard.blade.php` | `steps`, `current` | untuk form 4 langkah: nomor dalam lingkaran, langkah aktif `brand`, selesai centang, sisanya `line-strong`; di ponsel tampilkan "Langkah 2 dari 4" + bar progres |
| Header halaman | `page-header.blade.php` | `title`, `subtitle`, slot aksi | h1 kiri, aksi/kartu pengguna kanan, `flex-wrap` |
| Avatar pengguna | (restyle `komponen/menu_avatar`) | — | pil putih border `line`, nama 14/700, peran 12/`faint`, lingkaran avatar `bg-line` |
| Panel info | `notice.blade.php` | `icon`, slot | `bg-brand-soft border-[#bfdbfe] rounded-2xl p-4 text-sm text-ink-soft` |

Ikon: SVG sebaris gaya Heroicons outline (stroke 1.5, `h-5 w-5`, `currentColor`). Simpan sebagai
`components/icon/{nama}.blade.php` (mis. `eye`, `eye-slash`, `arrow-left`, `arrow-down-tray`, `printer`,
`check`, `shield-check`, `user-circle`, `cog`, `chevron-down`, `chevron-left`, `chevron-right`).

## 5. Layout

- **`tata_letak/utama.blade.php`** (halaman terautentikasi): header putih tinggi 64px, border bawah `line`;
  kiri: tombol kembali (bila `@yield('kembali')`) atau nama aplikasi; kanan: `@yield('navigasi')`.
  `main` pakai `bg-canvas`. Footer teks 12px `faint`, rata tengah. Tambahkan `<link>` font §2 di `<head>`.
  Flash message diganti `<x-ui.alert>`.
- **`tata_letak/tamu.blade.php`** (baru, untuk masuk/daftar/lupa/atur ulang sandi): latar `canvas`, blok merek di atas
  (nama aplikasi besar + tagline), satu kartu auth terpusat, footer hak cipta di bawah. Tanpa header navigasi.
- Responsif: desktop-first, tetapi semua halaman wajib terbaca di lebar 360px. Grid statistik
  `grid-cols-1 sm:grid-cols-3`; toolbar filter `flex-wrap`; tabel dibungkus `overflow-x-auto`.
- Tanpa dark mode.

## 6. Spesifikasi per halaman

### Masuk (`autentikasi/masuk`) — mockup Login
Layout `tamu`. Merek, lalu kartu: judul "Masuk ke Akun Anda" (tetap, karena staf juga login), subjudul
"Layanan Pencatatan & Administrasi Kematian". Field: `identitas` (label **"NIK / NIP"** — pertahankan, jangan ubah
menjadi "Nomor Induk Kependudukan"), `kata_sandi` (dengan mata). Baris: checkbox "Ingat Saya" kiri, "Lupa Kata Sandi?"
kanan (`brand`, 14/600). Tombol "Masuk" penuh lebar. Bawah kartu: "Belum punya akun? **Daftar di sini**".

### Registrasi (`autentikasi/daftar`) — mockup Registrasi
Layout `tamu`. Sama dengan Masuk; judul "Registrasi Akun Pemohon". Pertahankan semua field yang ada di Blade sekarang
(jangan hapus field yang tidak ada di mockup). Tautan bawah rata kanan: "Sudah punya akun? **Masuk di sini**".

### Lupa / Atur ulang kata sandi
Layout `tamu`, kartu yang sama dengan Masuk, satu tombol utama.

### Lacak status (`pengajuan/status`, `pengajuan/detail`) — mockup Lacak Status
Layout `utama`, `max-w-3xl`. Baris cari: input + tombol "CARI" (primary, huruf kapital). Kartu ringkasan:
"No. Pengajuan: …" (18/700), nama almarhum, tanggal pengajuan (nilai `ink`, label `muted`), `<x-ui.badge>` kanan atas.
Di bawahnya `<x-ui.stepper>`: Diajukan → Verifikasi Silang Data → Ditinjau Verifikator → Disetujui & Tanda Tangan
Elektronik → Diterbitkan / Selesai, dengan stempel waktu 12px `faint`. Garis pemisah, lalu tombol "UNDUH DOKUMEN"
(kanan, nonaktif sampai status Selesai, caption "*Aktif setelah status "Selesai"").

### Dasbor pemohon (`dashboard/pemohon`)
`page-header` (judul "Dashboard Pemohon", sapaan, tombol primary "Ajukan Laporan Baru"). Baris `<x-ui.stat>`
untuk ringkasan status (ganti daftar `$ringkasanStatus` menjadi kartu; tetap pakai data yang sama). Seksi
"Perlu Tindakan Anda" dan "Pengajuan Saya" masing-masing memakai `<x-ui.table>` (restyle `dashboard/tabel_laporan`).
Hapus kartu pembungkus besar bersarang (kartu di dalam kartu).

### Dasbor verifikator (`dashboard/verifikator`, `verifikasi/daftar`) dan pejabat (`dashboard/pejabat`) — mockup Halaman Tanda Tangan
`page-header` kiri (judul + subjudul), kartu pengguna kanan. Tiga `<x-ui.stat>` (warning / success / danger).
Toolbar filter rata kanan: input cari, select status ("Semua Status"), tombol "Cari". Tabel kolom:
NO, NO. PENGAJUAN (13/600), NIK (mono 12px, dimasker — tetap pakai `MaskerNik`), NAMA, WAKTU (tanggal 12/600 + jam 11/`faint`),
STATUS (badge), AKSI (tombol sm: primary "LIHAT" bila perlu tindakan, secondary "DETAIL" bila tidak). Footer tabel:
"Menampilkan 1–5 dari 22 pembaruan" kiri, pagination kanan (kotak 32px, halaman aktif `bg-brand text-white`).

### Detail dokumen / verifikasi (`pengajuan/detail`, `verifikasi/detail`, `laporan/lihat`) — mockup Detail Dokumen 2
Bar atas putih: "← Kembali ke Daftar Dokumen" kiri, teks kecil kanan. Judul nama almarhum (30/700) + "Nomor Dokumen:" (mono) kiri,
badge kanan. Grid 2 kolom `lg:grid-cols-[1fr_360px]`: kiri area pratinjau dokumen (latar `#d9dcde`, kertas putih di tengah,
isi akta pakai serif); kanan kartu "Informasi Dokumen" (label 12/`muted`, nilai 14/600, pemisah `line`), panel verifikasi
(`notice`), tombol "Unduh Dokumen (PDF)" (primary, ikon) dan "Cetak Dokumen" (secondary), lalu catatan hukum UU ITE.
Di bawah `lg` jadi satu kolom (pratinjau di atas).

### Form laporan (`laporan/formulir`, `laporan/create`)
Tidak ada mockup. Pakai `<x-ui.wizard>` untuk 4 langkah, satu `<x-ui.card>` besar per langkah, field dengan `<x-ui.input>`
dan `<x-ui.select>`, grid 2 kolom di desktop (`sm:grid-cols-2`) dan 1 kolom di ponsel. Tombol "Sebelumnya" (secondary) kiri,
"Lanjut"/"Ajukan" (primary) kanan. Unggah berkas: kotak putus-putus border `line-strong`, `rounded-xl`, ikon, teks bantuan.
`panel_analisis_ai` dipertahankan logikanya, tampilannya: `<x-ui.notice>` dengan lencana keyakinan ekstraksi.

### Profil (`profil/tampilkan`)
Avatar inisial lingkaran 64px (`profil/inisial`), kartu data dengan pasangan label/nilai seperti "Informasi Dokumen".

## 7. Definisi selesai (per halaman)

- [ ] Tidak ada class `gray-*`, `sky-*`, `slate-*` mentah tersisa di file itu (kecuali lewat token).
- [ ] Memakai komponen `x-ui.*`, tanpa salin-tempel class tombol/input/kartu.
- [ ] Terbaca tanpa scroll horizontal di 360px dan di 1280px.
- [ ] Semua teks Bahasa Indonesia, semua `name`/`id`/route tak berubah.
- [ ] `php artisan test` hijau dan `npm run build` sukses.
