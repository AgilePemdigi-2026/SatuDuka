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
| email | varchar(100) | unik |
| kata_sandi | varchar(255) | hash; override `getAuthPassword()` |
| no_telepon | varchar(15) | |
| peran | enum('pemohon','verifikator','pejabat') | |
| dibuat_pada / diperbarui_pada | timestamp | map ke created_at/updated_at |

Auth Laravel: set `config/auth.php` provider ke model `Pengguna`; `getAuthPassword()` mengembalikan `kata_sandi`; `getAuthIdentifierName()` = `id_pengguna`.

## laporan_kematian
id_laporan (PK), id_pengguna (FK → pengguna, pemohon), no_pengajuan varchar(30) unik (format mis. `SD-20261020-0001`), nama_pelapor varchar(100), hubungan_pelapor varchar(50), nama_almarhum varchar(100), nik_almarhum varchar(16), tanggal_kematian date, tempat_kematian varchar(100), sebab_kematian text, status_pengajuan varchar(30), tanggal_pengajuan timestamp nullable.
Nilai status_pengajuan: `Draf` (IMPROVISASI), `Menunggu Verifikasi`, `Ditolak/Butuh Revisi`, `Disetujui/Menunggu TTE`, `Selesai`, `Dikembalikan`.
IMPROVISASI: tambah `catatan_revisi` text nullable dan `perlu_verifikasi_manual` boolean default false (fallback layanan eksternal).

## berkas_pendukung
id_berkas (PK), id_laporan (FK), jenis_dokumen varchar(50) (mengikuti `BerkasPendukung::DAFTAR_JENIS`: 4 berkas wajib — Surat Keterangan Kematian, KTP Almarhum, KTP Pelapor, Kartu Keluarga — dan 5 berkas opsional — Surat Keterangan Kelurahan, SPTJM Kebenaran Data Kematian, Surat Keterangan Kepolisian, KTP Saksi 1, KTP Saksi 2), nama_file varchar(255), path_file varchar(255), status_unggahan varchar(20), tanggal_unggah timestamp.

## hasil_verifikasi
id_verifikasi (PK), id_laporan (FK), id_pengguna (FK → pengguna, verifikator), status_verifikasi varchar(30), catatan text, tanggal_verifikasi timestamp.

## anomali_dokumen
id_anomali (PK), id_verifikasi (FK → hasil_verifikasi), jenis_anomali varchar(50), deskripsi text, status_rekonsiliasi varchar(20) (mis. `Terbuka`, `Selesai`), tanggal_rekonsiliasi timestamp nullable.

## dokumen_resmi
id_dokumen (PK), id_laporan (FK, unik → relasi 1:1), id_pengguna (FK → pengguna, pejabat), no_dokumen varchar(30), tanggal_terbit timestamp nullable, status_ttd varchar(20) (`Menunggu`, `Selesai`, `Dikembalikan`), file_dokumen varchar(255) nullable.
IMPROVISASI: tambah `alasan_pengembalian` text nullable.

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
- 10 laporan kematian tersebar di semua status, masing-masing 3 sampai 4 berkas.
- 3 hasil verifikasi, 2 anomali (1 Terbuka, 1 Selesai), 2 dokumen resmi (1 Menunggu, 1 Selesai).
- Data SIAK dummy (NIK → nama, tanggal lahir) untuk SIAKService mock: simpan di file `database/data/siak_dummy.json`, bukan di tabel aplikasi.
