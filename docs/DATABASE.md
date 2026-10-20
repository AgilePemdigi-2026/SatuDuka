# Spesifikasi Database SATUDUKA (dari PDM Gambar 8.2 dan Tabel 8.1/8.2 SRS)

DBMS: PostgreSQL. Gunakan nama tabel/kolom PERSIS seperti di bawah (Bahasa Indonesia).
Gambar acuan: `docs/diagram/gambar_8_2_pdm.png`, `gambar_8_1_cdm.png`.
Gunakan `$table`, `$primaryKey`, dan `$timestamps` eksplisit di model karena tidak mengikuti konvensi Laravel.

## pengguna
| Kolom | Tipe | Catatan |
|---|---|---|
| id_pengguna | bigint PK auto | |
| nama_lengkap | varchar(100) | |
| nik | varchar(16) | unik, nullable untuk petugas |
| nip | varchar(20) | IMPROVISASI: unik, nullable; dipakai login Verifikator/Pejabat |
| email | varchar(100) | unik, WAJIB diisi saat registrasi (deviasi dari use case 5.2.1) |
| kata_sandi | varchar(255) | hash; override `getAuthPassword()` |
| no_telepon | varchar(15) | |
| peran | enum('pemohon','verifikator','pejabat') | |
| remember_token | varchar(100) nullable | IMPROVISASI (Fase 2): tidak ada di PDM; dibutuhkan Laravel untuk fitur "Ingat Saya" use case 5.2.2 alur 4.b. Migration `2026_10_20_000010` |
| dibuat_pada / diperbarui_pada | timestamp | map ke created_at/updated_at |

Auth Laravel: set `config/auth.php` provider ke model `Pengguna`; `getAuthPassword()` mengembalikan `kata_sandi`; `getAuthIdentifierName()` = `id_pengguna`.
Login memakai satu formulir "NIK atau NIP": identitas 16 digit dibaca sebagai `nik`, identitas 18 digit dibaca sebagai `nip`. Pencarian NIK dipusatkan pada `Pengguna::cariByNik()` agar mudah dienkripsi pada Fase 9.

## laporan_kematian
id_laporan (PK), id_pengguna (FK → pengguna, pemohon), no_pengajuan varchar(30) unik (format mis. `SD-20261020-0001`), nama_pelapor varchar(100), hubungan_pelapor varchar(50), nama_almarhum varchar(100), nik_almarhum varchar(16), tanggal_kematian date, tempat_kematian varchar(100), sebab_kematian text, status_pengajuan varchar(30), tanggal_pengajuan timestamp nullable, dibuat_pada timestamp nullable, diperbarui_pada timestamp nullable.
Nilai status_pengajuan: `Draf` (IMPROVISASI), `Menunggu Verifikasi`, `Ditolak/Butuh Revisi`, `Disetujui/Menunggu TTE`, `Selesai`, `Dikembalikan`, `Dalam Rekonsiliasi Anomali`, `Pending - Menunggu Perbaikan Berkas Pelapor`.
IMPROVISASI: tambah `catatan_revisi` text nullable dan `perlu_verifikasi_manual` boolean default false (fallback layanan eksternal).
IMPROVISASI (keputusan pemilik proyek, migration `2026_10_20_000009_tambah_kolom_waktu_ke_tabel_pengajuan`): tambah `dibuat_pada` dan `diperbarui_pada` timestamp nullable.

## berkas_pendukung
id_berkas (PK), id_laporan (FK), jenis_dokumen varchar(50) (Surat Keterangan Kematian, KTP Almarhum, KTP Pelapor, Kartu Keluarga), nama_file varchar(255), path_file varchar(255), status_unggahan varchar(20), tanggal_unggah timestamp.

## hasil_verifikasi
id_verifikasi (PK), id_laporan (FK), id_pengguna (FK → pengguna, verifikator), status_verifikasi varchar(30), catatan text, tanggal_verifikasi timestamp, dibuat_pada timestamp nullable, diperbarui_pada timestamp nullable.
IMPROVISASI (migration `2026_10_20_000009_tambah_kolom_waktu_ke_tabel_pengajuan`): tambah `dibuat_pada` dan `diperbarui_pada` timestamp nullable.

## anomali_dokumen
id_anomali (PK), id_verifikasi (FK → hasil_verifikasi), jenis_anomali varchar(50), deskripsi text, status_rekonsiliasi varchar(20) (`Terbuka`, `Selesai`, `Ditolak - Anomali Data Fatal`), tanggal_rekonsiliasi timestamp nullable, dibuat_pada timestamp nullable, diperbarui_pada timestamp nullable.
IMPROVISASI (migration `2026_10_20_000009_tambah_kolom_waktu_ke_tabel_pengajuan`): tambah `dibuat_pada` dan `diperbarui_pada` timestamp nullable.

## dokumen_resmi
id_dokumen (PK), id_laporan (FK, unik → relasi 1:1), id_pengguna (FK → pengguna, pejabat, nullable sampai ditandatangani), no_dokumen varchar(30) unik nullable, tanggal_terbit timestamp nullable, status_ttd varchar(20) (`Menunggu`, `Selesai`, `Dikembalikan`), file_dokumen varchar(255) nullable, dibuat_pada timestamp nullable, diperbarui_pada timestamp nullable.
IMPROVISASI: tambah `alasan_pengembalian` text nullable.
IMPROVISASI (migration `2026_10_20_000009_tambah_kolom_waktu_ke_tabel_pengajuan`): tambah `dibuat_pada` dan `diperbarui_pada` timestamp nullable.

## log_audit (IMPROVISASI, kebutuhan NFR-06)
id_log (PK), id_pengguna (FK nullable), aksi varchar(100), objek varchar(50), id_objek bigint nullable, detail jsonb nullable, layanan_eksternal varchar(30) nullable, hasil varchar(20) nullable, dibuat_pada timestamp. Tanpa updated_at. Append-only.

## Relasi (Tabel 8.2)
1. pengguna 1:N laporan_kematian (pemohon)
2. laporan_kematian 1:N berkas_pendukung
3. laporan_kematian 1:N hasil_verifikasi
4. pengguna 1:N hasil_verifikasi (verifikator)
5. hasil_verifikasi 1:N anomali_dokumen
6. laporan_kematian 1:1 dokumen_resmi
7. pengguna 1:N dokumen_resmi (pejabat)

## Seeder data dummy
- Akun demo (kata sandi `password123`): 1 pemohon (email demo), 1 verifikator (NIP demo), 1 pejabat (NIP demo). Tampilkan kredensial demo di `PROGRESS.md`.
  - Pemohon: email `pemohon@demo.satuduka.id`, NIK `9999990101900001`.
  - Verifikator: email `verifikator@demo.satuduka.id`, NIP `999999991234567890`.
  - Pejabat: email `pejabat@demo.satuduka.id`, NIP `999999992345678901`.
- 10 laporan kematian tersebar di semua status, masing-masing 3 sampai 4 berkas.
- 3 hasil verifikasi, 2 anomali (1 Terbuka, 1 Selesai), 2 dokumen resmi (1 Menunggu, 1 Selesai).
- Data SIAK dummy (NIK → nama, tanggal lahir) untuk SIAKService mock: simpan di file `database/data/siak_dummy.json`, bukan di tabel aplikasi.
