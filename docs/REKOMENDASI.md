# Rekomendasi Fase 1 - Basis Data SATUDUKA

Dokumen ini disusun setelah **Fase 1 (Database)** selesai: 7 tabel aplikasi, 7 model
Eloquent, 7 factory, 4 seeder, migration RLS, dan tes relasi sudah terpasang,
`php artisan migrate:fresh --seed` berhasil di Supabase, dan `php artisan test` hijau.

> **Status: SUDAH DIPUTUSKAN pemilik proyek (2026-10-20).** Seluruh pertanyaan pada
> bagian (e) telah dijawab dan keputusannya tercatat pada `PROGRESS.md` bagian
> "Keputusan Pemilik Proyek", termasuk tambahan: kolom waktu buat/ubah pada empat
> tabel pengajuan, nomor pengajuan aman race condition, login satu formulir
> "NIK atau NIP", reset kata sandi lewat surel, dan `LayananAudit::catat(...)`.
> Dokumen ini tetap dipertahankan sebagai catatan temuan dan alasan.

Bagian:
(a) Ringkasan skema dan keputusan improvisasi
(b) Ketidakkonsistenan SRS yang ditemukan
(c) Risiko teknis tiap fitur berikutnya
(d) Rekomendasi urutan dan penyesuaian Fase 2-10
(e) Pertanyaan yang membutuhkan keputusan Anda (sudah terjawab, lihat PROGRESS.md)

---

## (a) Ringkasan skema dan keputusan improvisasi

### Skema yang terbentuk

| Tabel | Kunci utama | Isi |
|---|---|---|
| `pengguna` | `id_pengguna` (bigint, auto) | `nama_lengkap` (100), `nik` (16, unik, nullable), `nip` (20, unik, nullable), `email` (100, unik), `kata_sandi` (255, hash), `no_telepon` (15, nullable), `peran` (enum pemohon/verifikator/pejabat), `dibuat_pada`, `diperbarui_pada` |
| `laporan_kematian` | `id_laporan` | `id_pengguna` (FK, pemohon), `no_pengajuan` (30, unik), `nama_pelapor` (100), `hubungan_pelapor` (50), `nama_almarhum` (100), `nik_almarhum` (16), `tanggal_kematian` (date), `tempat_kematian` (100), `sebab_kematian` (text), `status_pengajuan` (30), `tanggal_pengajuan` (timestamp, nullable), `catatan_revisi` (text, nullable), `perlu_verifikasi_manual` (boolean) |
| `berkas_pendukung` | `id_berkas` | `id_laporan` (FK, cascade), `jenis_dokumen` (50), `nama_file` (255), `path_file` (255), `status_unggahan` (20), `tanggal_unggah` (timestamp) |
| `hasil_verifikasi` | `id_verifikasi` | `id_laporan` (FK, cascade), `id_pengguna` (FK verifikator, restrict), `status_verifikasi` (30), `catatan` (text), `tanggal_verifikasi` (timestamp) |
| `anomali_dokumen` | `id_anomali` | `id_verifikasi` (FK, cascade), `jenis_anomali` (50), `deskripsi` (text), `status_rekonsiliasi` (20), `tanggal_rekonsiliasi` (timestamp, nullable) |
| `dokumen_resmi` | `id_dokumen` | `id_laporan` (FK, cascade, **unik** = relasi 1:1), `id_pengguna` (FK pejabat, nullable), `no_dokumen` (30, unik, nullable), `tanggal_terbit` (nullable), `status_ttd` (20), `file_dokumen` (255, nullable), `alasan_pengembalian` (text, nullable) |
| `log_audit` | `id_log` | `id_pengguna` (FK, set null), `aksi` (100), `objek` (50), `id_objek` (bigint), `detail` (**jsonb**), `layanan_eksternal` (30), `hasil` (20), `dibuat_pada` (timestamp). Tanpa kolom ubah; append-only |

Tabel bawaan Laravel yang **dipertahankan**: `sessions`, `password_reset_tokens`,
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

Relasi Tabel 8.2 terimplementasi penuh: pengguna 1:N laporan, laporan 1:N berkas,
laporan 1:N hasil verifikasi, pengguna 1:N hasil verifikasi, hasil verifikasi 1:N anomali,
laporan 1:1 dokumen resmi, pengguna 1:N dokumen resmi. Semua foreign key memakai aturan
hapus yang jelas (cascade untuk anak laporan, restrict untuk pemohon/verifikator,
set null untuk log audit dan pejabat pada dokumen resmi).

### Keputusan improvisasi

1. **Tabel `users` bawaan Laravel DIHAPUS.** Autentikasi memakai tabel `pengguna`
   (`config/auth.php` -> `App\Models\Pengguna`). Alasannya: tabel `users` tidak pernah
   dipakai, tidak ada kaitan dengan tabel `pengguna`, dan keberadaannya berarti satu
   tabel lebih yang harus dilindungi RLS serta tidak sengaja dipakai kembali.
   `password_reset_tokens` dan `sessions` tetap ada karena dibutuhkan framework
   (reset kata sandi berbasis surel dan penyimpanan sesi). Konsekuensi: `App\Models\User`
   dan `UserFactory` ikut dihapus, dan file migrasi bawaan diganti nama menjadi
   `0001_01_01_000000_create_sessions_and_password_reset_tokens_table.php`.
2. **`peran` memakai `enum` Laravel**, yang di PostgreSQL menjadi
   `varchar(255) check ("peran" in ('pemohon','verifikator','pejabat'))` dan di SQLite
   menjadi `varchar check (...)`. Sengaja **tidak** memakai tipe ENUM asli PostgreSQL
   agar penambahan nilai peran di kemudian hari tidak memerlukan `ALTER TYPE` yang
   merepotkan. Validasi peran juga disediakan di level aplikasi melalui
   `Pengguna::DAFTAR_PERAN`.
3. **`detail` pada `log_audit` memakai `jsonb`** (sesuai DATABASE.md). Pada SQLite
   Laravel otomatis menurunkan `jsonb` menjadi `text`, jadi migrasi tetap jalan di
   kedua driver.
4. **Hanya `pengguna` yang memiliki `dibuat_pada`/`diperbarui_pada`; `log_audit` hanya
   `dibuat_pada`; tabel lain tidak memiliki kolom waktu buat/ubah.** Ini mengikuti
   DATABASE.md persis. Model terkait memakai `$timestamps = false` dan kolom waktu
   domainnya (`tanggal_pengajuan`, `tanggal_unggah`, `tanggal_verifikasi`,
   `tanggal_rekonsiliasi`, `tanggal_terbit`) dipetakan dengan cast.
5. **`dokumen_resmi.id_pengguna` nullable** (pejabat baru diisi saat penandatanganan
   TTE) dan **`no_dokumen` nullable** (nomor dokumen terbit pada fase TTE). Relasi 1:1
   dijamin oleh indeks unik pada `id_laporan`.
6. **Status yang tidak dijelaskan DATABASE.md tetap dibuat sebagai konstanta** agar
   alur SRS dapat dikerjakan: `LaporanKematian::STATUS_DALAM_REKONSILIASI` dan
   `STATUS_MENUNGGU_PERBAIKAN` (dari use case 5.2.6/5.2.7),
   `AnomaliDokumen::STATUS_DITOLAK_PERMANEN` (dari use case 5.2.7 alur 6a),
   `BerkasPendukung::STATUS_MENUNGGU/BERHASIL/GAGAL` (tidak disebut SRS),
   `HasilVerifikasi::STATUS_DITERIMA/DITOLAK/PERLU_REVISI/ANOMALI`.
7. **`log_audit` sudah dilindungi append-only di level model** (`updating` dan
   `deleting` melempar `LogicException`). Ini memenuhi aturan AGENTS.md lebih awal;
   Fase 9 tinggal menambahkan pengaman di level database (trigger atau revoke privilege).
8. **NIK dan NIP dummy memakai kode wilayah fiktif** (`999999` untuk NIK, `99999999`
   untuk NIP) sehingga mustahil menabrak data warga asli. Data SIAK dummy disimpan di
   `database/data/siak_dummy.json` (15 catatan) **bukan** di tabel aplikasi, dengan
   `App\Support\DataSIAK` dan `App\Support\NikDummy` sebagai pembacanya.
9. **Data dummy pada laporan memakai data SIAK yang sama** (nama dan NIK almarhum
   diambil dari `siak_dummy.json`) agar verifikasi silang pada Fase 6 selalu punya
   data acuan.
10. **Pengujian memakai SQLite in-memory** (`phpunit.xml`) dan **tidak pernah** menyentuh
    Supabase. `RefreshDatabase` diaktifkan untuk seluruh suite `Feature`.
11. **Ekstensi `pdo_sqlite` dan `sqlite3` diaktifkan pada `php.ini` lokal** (bawaan
    Winget PHP 8.3 tidak mengaktifkannya), karena seluruh pengujian diwajibkan memakai
    SQLite in-memory. Ini perubahan pada lingkungan, bukan pada proyek.
12. **Tabel `migrations`, `cache`, `jobs`, dan tabel bawaan lain juga dilindungi RLS**
    karena berada di skema `public` yang dapat diakses lewat Data API Supabase.
    Migration RLS dibungkus pemeriksaan driver `pgsql` sehingga aman pada SQLite.

---

## (b) Ketidakkonsistenan SRS yang ditemukan

1. **Jumlah entitas.** SRS 8.2 (Tabel 8.1/8.2) hanya menyebut 6 entitas; `log_audit`
   adalah tambahan DATABASE.md untuk NFR-06. Implementasi memakai 7 tabel.
2. **Nama kolom FK.** PDM SRS memakai `id_pemohon`, `id_verifikator`, `id_pejabat`;
   DATABASE.md memakai `id_pengguna` seragam dengan pembeda kolom `peran`. Diikuti
   DATABASE.md (sumber kebenaran fase ini).
3. **Daftar status pengajuan tidak lengkap.** DATABASE.md menyebut 6 status, tetapi
   use case 5.2.6 dan 5.2.7 memakai status "Dalam Rekonsiliasi Anomali" dan
   "Pending - Menunggu Perbaikan Berkas Pelapor" yang tidak ada di daftar tersebut.
4. **Status anomali tidak sinkron.** DATABASE.md: `Terbuka`/`Selesai`. Use case 5.2.7:
   "Resolved", "In Progress - Pending System Response", "Ditolak - Anomali Data Fatal".
   Hanya "Ditolak - Anomali Data Fatal" yang ditambahkan sebagai konstanta; dua status
   lain dipetakan ke `Terbuka`/`Selesai`.
5. **Kredensial akun percobaan berbeda.** Tabel 6.10 SRS memakai `Pemohon@123`,
   `Verif@2024`, `Pejabat@2024`; DATABASE.md menetapkan `password123` untuk semua akun
   demo. Diikuti DATABASE.md.
6. **Kolom "NIK" untuk petugas.** Tabel 6.10 menyebut kolom NIK untuk tiga akun
   percobaan, padahal petugas login memakai NIP (use case 5.2.2). Tabel `pengguna`
   mendukung keduanya (NIK untuk pemohon, NIP untuk petugas).
7. **Registrasi tidak meminta email.** Use case 5.2.1 hanya meminta NIK dan kata sandi,
   sedangkan `pengguna.email` NOT NULL UNIQUE dan reset kata sandi berbasis surel
   membutuhkan email. Ini membutuhkan keputusan (lihat bagian (e) butir 1).
8. **Reset kata sandi berbasis NIK/NIP.** Use case 5.2.2 alur 4.a meminta NIK/NIP,
   sedangkan tabel `password_reset_tokens` (bawaan Laravel) berkey email. Perlu
   penyesuaian implementasi di Fase 2 atau Fase 5.
9. **NFR-02/NFR-03 butuh penyimpanan penghitung.** Kunci akun 15 menit setelah
   percobaan login gagal dan kunci TTE 15 menit setelah 3 kali kredensial TTE salah
   membutuhkan tempat menyimpan hitungan dan waktu kunci. Tujuh tabel tidak menyediakan
   kolom itu. Kredensial TTE sendiri tidak boleh disimpan (SRS 2.6), sehingga
   penghitung sebaiknya di cache, bukan tabel.
10. **Optimistic locking pada rekonsiliasi.** Use case 5.2.7 alur 6b meminta deteksi
    bahwa data master berubah selama proses analisis. Tabel `anomali_dokumen` dan
    `laporan_kematian` tidak memiliki kolom versi maupun `diperbarui_pada`, sehingga
    deteksi ini tidak dapat dilakukan secara andal. Butuh kolom tambahan atau
    implementasi lain.
11. **Nomor pengajuan.** Format `SD-YYYYMMDD-NNNN` berasal dari DATABASE.md; SRS tidak
    mengatur formatnya, dan tidak ada mekanisme pengaman duplikasi selain indeks unik.
12. **Kolom avatar/foto profil.** Use case 5.2.4 (Mengelola User Profile) tidak punya
    kolom avatar di PDM; bila UI membutuhkan foto, kolom baru diperlukan.
13. **Keterlacakan perubahan status.** Karena tabel selain `pengguna` tidak memiliki
    kolom waktu buat/ubah, riwayat perubahan status pengajuan hanya terekam di
    `log_audit` yang harus diisi konsisten di setiap fase.

---

## (c) Risiko teknis tiap fitur berikutnya

**Fase 2 - Register/Login/dashboard per peran**
- Login NIK/NIP: `EloquentUserProvider` bawaan memetakan kredensial apa adanya ke nama
  kolom, jadi `Auth::attempt(['nik' => ...])` sudah bisa jalan. Namun agar satu
  formulir menerima NIK *dan* NIP, butuh **provider autentikasi kustom**
  (override `retrieveByCredentials`) atau resolver manual.
- `email` NOT NULL UNIQUE memblokir registrasi yang hanya mengumpulkan NIK
  (use case 5.2.1).
- Penguncian 15 menit (NFR-02) butuh penghitung; `login_throttles` bawaan Laravel
  berbasis cache/IP tidak cocok dengan penguncian per akun.
- RLS aktif: kredensial service_role/anon tidak boleh dipakai di aplikasi.
- `log_audit` wajib diisi pada setiap login/registrasi, dan model menolak update/delete.

**Fase 3 - Mengajukan laporan**
- Unggah berkas: `FILESYSTEM_DISK=local` tidak persisten di hosting gratis (Fase 10).
- Nomor pengajuan unik: gunakan sequence/DB-generated pattern, bukan `max()+1`
  (race condition antar permintaan).
- Validasi NIK ke SIAK memakai `database/data/siak_dummy.json` (15 data); perlu
  penyesuaian agar data demo juga mencakup NIK pelapor, bukan hanya almarhum.
- Batas 5 MB dan format PDF/JPG/PNG (NFR-05) harus divalidasi di sisi server.
- Penyimpanan `path_file` hanya teks; penghapusan berkas fisik harus dijaga sinkron.

**Fase 4 - Status pengajuan**
- Lini masa status menuntut urutan status yang baku; status tambahan (Rekonsiliasi,
  Menunggu Perbaikan) harus punya urutan dan gaya tampilan yang disepakati.
- Download akta hanya boleh tersedia bila status `Selesai`: authorize per pemohon
  (IDOR) dan jangan bocorkan `path_file`.
- Notifikasi belum ada tabel; NotifikasiService baru dimock di Fase 6, jadi Fase 4
  hanya menampilkan pesan di dalam aplikasi.

**Fase 5 - Profil**
- NFR-04: NIK tidak dapat diubah sendiri, sehingga form profil harus mengunci NIK.
- Pengubahan kata sandi memerlukan verifikasi kata sandi lama dan hash baru.
- Reset kata sandi berbasis NIK/NIP tidak sinkron dengan `password_reset_tokens`.

**Fase 6 - Mock layanan + verifikasi**
- Fallback wajib: bila SIAK/Faskes gagal, tandai `perlu_verifikasi_manual = true` dan
  catat ke `log_audit`; jangan menolak otomatis (SRS 2.6 butir 4).
- Mock harus bisa disimulasikan gagal/down lewat konfigurasi, bukan percabangan mati.
- Transaksi panjang saat verifikasi silang dapat menahan lock baris pada PostgreSQL.
- `dokumen_resmi` dibuat saat verifikasi menyetujui; pastikan relasi 1:1 tidak bentrok
  bila verifikasi diulang.

**Fase 7 - Rekonsiliasi anomali**
- Konkurensi: dua verifikator dapat membuka anomali yang sama (butuh locking atau
  optimistic locking, lihat ketidakkonsistenan butir 10 pada bagian (b)).
- Status anomali belum seragam antara SRS dan DATABASE.md (lihat bagian (b) butir 4).
- Setelah rekonsiliasi selesai, berkas harus kembali ke antrean verifikator: perlu
  transisi status yang jelas.

**Fase 8 - TTE**
- PIN/OTP tidak boleh disimpan; penghitung 3 kali salah dan kunci 15 menit harus di cache.
- PDF memakai dompdf 3.1.2: beware kebutuhan memori pada dokumen panjang, dan file PDF
  harus disimpan di storage yang persisten.
- `no_dokumen` unik: format dan sumber angka (sequence) perlu ditetapkan.
- Setelah TTE berhasil, `status_ttd` dan `status_pengajuan` harus berubah dalam satu
  transaksi.

**Fase 9 - Penguatan**
- Enkripsi NIK (UU PDP): kolom terenkripsi membuat pencarian NIK sulit; pertimbangkan
  `nik_hash` (blind index) sebagai kolom tambahan.
- `log_audit` append-only sudah ada di model; tambahkan pengaman di level database.
- Rate limit login memakai cache; cache bawaan memakai tabel `cache` (sudah ada).
- Halaman 403/404/500 berbahasa Indonesia perlu view dan handler.

**Fase 10 - Deploy**
- Port: server di luar jaringan lokal dapat memakai session pooler `5432`; jangan
  memakai transaction pooler `6543` untuk sesi dan prepared statement.
- `SESSION_DRIVER=database` dan `CACHE_STORE=database` berarti sesi ikut tersimpan di
  Supabase; pastikan RLS tetap aktif dan role aplikasi adalah pemilik tabel.
- Storage berkas tidak persisten di filesystem hosting gratis, sehingga butuh disk
  eksternal.

---

## (d) Rekomendasi urutan dan penyesuaian Fase 2-10

Urutan utama tetap mengikuti `PHASES.md` (2 Auth, 3 Ajukan, 4 Status, 5 Profil,
6 Verifikasi, 7 Rekonsiliasi, 8 TTE, 9 Penguatan, 10 Deploy). Penyesuaian yang
saya usulkan:

1. **Fase 2 (Auth) tambahkan 3 hal** sebelum menulis halaman:
   a. provider autentikasi kustom (atau resolver NIK/NIP) agar pemohon dan petugas
      bisa login dari satu formulir dengan format berbeda;
   b. tempat penyimpanan penghitung kunci 15 menit (disarankan cache, bukan tabel);
   c. keputusan tentang email pada registrasi (lihat bagian (e)).
   Selain itu, siapkan pencatatan `log_audit` untuk login berhasil dan gagal.
2. **Fase 3 (Ajukan) tambahkan penomoran pengajuan yang aman** (sequence) dan
   tentukan bentuk `path_file` serta masa simpan berkas unggahan.
3. **Fase 4 (Status) tetapkan urutan lini masa status**, termasuk status tambahan
   Rekonsiliasi dan Penunggu Perbaikan, sebelum membuat tampilan.
4. **Fase 5 (Profil) sediakan dukungan reset kata sandi berbasis NIK/NIP**; bila tidak
   dipakai, tetapkan bahwa reset hanya lewat email.
5. **Fase 6 (Verifikasi) letakkan mock layanan lebih dulu** (sesuai rencana) dan
   siapkan flag konfigurasi "layanan down" beserta skenario fallback.
6. **Fase 7 (Rekonsiliasi) putuskan strategi konkurensi** lebih dulu: tambah kolom
   `diperbarui_pada` atau versi pada `anomali_dokumen`, atau gunakan
   `SELECT ... FOR UPDATE`.
7. **Fase 8 (TTE) buat layanan TTE mock dan penyimpanan penghitung kunci** sebelum
   halaman, dan siapkan penyimpanan PDF yang persisten.
8. **Fase 9 (Penguatan) tambahkan butir enkripsi NIK** dan audit trail tingkat
   database; setelah itu lakukan uji performa ringan pada daftar pengajuan.
9. **Fase 10 (Deploy) siapkan `.env.production` contoh** dengan port `5432`, storage
   persisten, dan pastikan RLS tetap aktif setelah migrasi.

Rekomendasi tambahan lintas fase: buat **service pencatatan audit** (misalnya
`LayananAudit::catat(...)`) di Fase 2 agar semua fase berikutnya memakai satu cara
pencatatan dan tidak ada aksi penting yang lupa masuk `log_audit`.

---

## (e) Pertanyaan yang membutuhkan keputusan Anda

1. **Email pada registrasi.** Use case 5.2.1 hanya meminta NIK dan kata sandi, tetapi
   `pengguna.email` wajib dan unik. Pilihan: (a) email wajib diisi saat registrasi,
   (b) email nullable (menyulitkan reset kata sandi berbasis email), atau
   (c) registrasi dua tahap dengan verifikasi surel. Rekomendasi: (a) paling sederhana.
2. **Tempat menyimpan penghitung kunci 15 menit.** Cache (disarankan, mudah
   kedaluwarsa) atau kolom `percobaan_gagal` dan `dikunci_sampai` pada `pengguna`
   (lebih audit-friendly, tetapi menambah skema tabel yang sudah final).
3. **Enkripsi NIK.** UU PDP 27/2022 mensyaratkan perlindungan data sensitif. Apakah NIK
   perlu dienkripsi (butuh kolom `nik_hash` untuk pencarian), atau cukup minimalisasi
   data dan pembatasan akses seperti sekarang?
4. **Status pengajuan tambahan.** Apakah "Dalam Rekonsiliasi Anomali" dan "Pending -
   Menunggu Perbaikan Berkas Pelapor" resmi menjadi status yang dapat dipilih pengguna
   (masuk daftar status pada basis data), atau tetap status internal?
5. **Status anomali.** Apakah "Ditolak - Anomali Data Fatal" perlu tetap ada sebagai
   status tersendiri, atau cukup `Terbuka`/`Selesai` dengan penanda di `deskripsi`?
6. **Kolom waktu pada tabel lain.** Apakah boleh menambahkan `dibuat_pada` dan
   `diperbarui_pada` pada `laporan_kematian`, `hasil_verifikasi`, `anomali_dokumen`, dan
   `dokumen_resmi`? Sangat disarankan untuk rekonsiliasi dan deteksi perubahan data.
7. **Nomor pengajuan.** Apakah format `SD-YYYYMMDD-NNNN` sudah sesuai, atau perlu
   tambahan kode wilayah/kecamatan?
8. **Tabel notifikasi.** NotifikasiService (Fase 6) sebaiknya menyimpan notifikasi di
   basis data (tabel baru `notifikasi`) agar pengaju bisa melihat riwayat notifikasi,
   atau cukup ditampilkan sebagai pesan di aplikasi?
9. **Data dummy SIAK.** Cukup 15 catatan dengan wilayah fiktif, atau perlu data yang
   lebih banyak dan bervariasi (misalnya beberapa NIK sengaja tidak ditemukan agar
   alur "NIK tidak ditemukan pada data kependudukan" dapat didemokan)?
10. **Penghapusan tabel `users` bawaan.** Sudah dihapus pada fase ini. Mohon konfirmasi
    bahwa tidak ada kebutuhan lain (misalnya integrasi dengan tooling pihak ketiga).

---

## (f) Ringkasan jawaban pemilik proyek (2026-10-20)

| Pertanyaan (e) | Jawaban | Dampak teknis |
|---|---|---|
| 1. Email pada registrasi | **Wajib** (deviasi dari use case 5.2.1) | `pengguna.email` NOT NULL UNIQUE; dicatat sebagai penyimpangan di `PROGRESS.md` |
| 2. Penyimpanan penghitung kunci 15 menit | **Cache**, bukan tabel; penguncian dicatat ke `log_audit` | `App\Services\LayananPenguncian` dengan TTL 15 menit |
| 3. Enkripsi NIK | **Ditunda ke Fase 9**; pencarian NIK dipusatkan | `Pengguna::cariByNik()` sebagai satu-satunya jalur pencarian NIK |
| 4. Status pengajuan tambahan | **Resmi** menjadi status pengajuan | Sudah konstanta dan masuk `LaporanKematian::DAFTAR_STATUS` |
| 5. Status anomali "Ditolak - Anomali Data Fatal" | **Dipertahankan** | Sudah konstanta `AnomaliDokumen::STATUS_DITOLAK_PERMANEN` |
| 6. Kolom waktu pada tabel lain | **Ditambahkan** lewat migration baru | `2026_10_20_000009_tambah_kolom_waktu_ke_tabel_pengajuan`; model, factory, tes, `docs/DATABASE.md` diperbarui |
| 7. Nomor pengajuan | Tetap `SD-YYYYMMDD-NNNN`, tanpa kode wilayah, aman race condition | `App\Services\LayananNomorPengajuan` memakai `pg_advisory_xact_lock` + indeks unik |
| 8. Tabel notifikasi | Dibuat **nanti pada Fase 6** | Tidak ada perubahan skema sekarang |
| 9. Data SIAK dummy | **Diperbanyak pada Fase 6** | Tetap 15 catatan untuk sekarang |
| 10. Penghapusan tabel `users` | **Disetujui** | Migration bawaan diganti nama (hanya sesi + token reset) |

Tambahan: login satu formulir "NIK atau NIP" (16 digit ke `nik`, 18 digit ke `nip`),
reset kata sandi lewat surel terdaftar yang dicari dari NIK/NIP (`MAIL_MAILER=log` untuk
demo), dan pembuatan `LayananAudit::catat(...)` yang dipakai pada semua aksi penting.

---

*Lampiran: 21 tes Pest hijau (75 asersi), `php artisan migrate:fresh --seed` berhasil
di Supabase (host `aws-0-ap-northeast-1.pooler.supabase.com`, port `6543`, basis data
`postgres`, project ref `grurcuiuzmnozciyjhiy`), seluruh tabel di skema `public` memiliki
RLS aktif tanpa policy.*
