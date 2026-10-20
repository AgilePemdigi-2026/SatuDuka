# Urutan Pengerjaan SATUDUKA di OpenCode (free plan)

Cara pakai: buka sesi BARU di OpenCode untuk setiap fase (konteks tetap kecil). Tempel prompt fase di bawah.
Baris awal semua prompt sama: "Baca AGENTS.md dan PROGRESS.md dulu." Setelah fase selesai, cek manual, lalu lanjut ke fase berikutnya.

Urutan: 0 Setup → 1 Database (BERHENTI, rekomendasi) → 2 Auth → 3 Ajukan Laporan → 4 Status Pengajuan → 5 Profil → 6 Verifikasi → 7 Rekonsiliasi → 8 TTE → 9 Penguatan → 10 Deploy.

---
## FASE 0 — Setup proyek
```
Baca AGENTS.md dulu. Ini fase 0 (setup), belum ada PROGRESS.md: buat.
1. Buat proyek Laravel versi stabil terbaru di folder kerja ini (pertahankan folder docs/, AGENTS.md, PHASES.md).
2. Konfigurasi .env untuk PostgreSQL di Supabase (DB_CONNECTION=pgsql, host/port/database/user/password dari connection string Supabase, DB_SSLMODE=require; gunakan koneksi langsung atau session pooler port 5432). Tulis placeholder di .env.example dan tambahkan .env ke .gitignore. APP_NAME=SATUDUKA, APP_LOCALE=id, timezone Asia/Jakarta. Jika koneksi gagal (misalnya IPv6), coba session pooler, lalu laporkan.
3. Pasang Tailwind via Vite (bawaan Laravel), pasang barryvdh/laravel-dompdf, siapkan lang/id (pesan validasi bahasa Indonesia).
4. Buat layout Blade dasar `resources/views/tata_letak/utama.blade.php` (header, flash message, slot konten) dengan Tailwind.
5. Coba pasang Behat + Mink untuk Laravel. Jika macet >30 menit, pakai PHPUnit dan catat di PROGRESS.md.
6. git init, commit "Setup awal proyek".
Selesai bila `php artisan serve` menampilkan halaman awal dan `php artisan migrate` terhubung ke Supabase. Lalu BERHENTI.
```

## FASE 1 — Database, lalu BERHENTI untuk rekomendasi
```
Baca AGENTS.md dan PROGRESS.md. Ini fase 1 (database).
Baca docs/DATABASE.md sepenuhnya dan lihat docs/diagram/gambar_8_2_pdm.png bila bisa membaca gambar.
1. Buat migration untuk 7 tabel (pengguna, laporan_kematian, berkas_pendukung, hasil_verifikasi, anomali_dokumen, dokumen_resmi, log_audit) beserta foreign key dan index. Pertahankan tabel sesi/cache/job bawaan Laravel.
2. Buat model Eloquent (Pengguna, LaporanKematian, BerkasPendukung, HasilVerifikasi, AnomaliDokumen, DokumenResmi, LogAudit) dengan $table/$primaryKey eksplisit, relasi sesuai Tabel 8.2, casts, dan konstanta status. Jadikan Pengguna model autentikasi (kata_sandi, id_pengguna).
3. Buat factory dan seeder sesuai bagian "Seeder data dummy" di DATABASE.md.
4. Buat tes sederhana yang memastikan semua relasi berfungsi.
5. Buat migration terakhir yang menjalankan `ALTER TABLE ... ENABLE ROW LEVEL SECURITY` untuk semua tabel aplikasi (tanpa policy). Jalankan `php artisan migrate:fresh --seed` dan tes. Perbaiki sampai hijau. Awas: migrate:fresh menghapus semua tabel di database; pastikan hanya database SATUDUKA yang dituju.
6. Tulis docs/REKOMENDASI.md berisi: (a) ringkasan skema yang terbentuk dan semua keputusan improvisasi yang diambil, (b) ketidakkonsistenan SRS yang ditemukan, (c) risiko teknis tiap fitur berikutnya, (d) rekomendasi urutan dan penyesuaian fase 2–10, (e) pertanyaan yang butuh keputusan saya.
7. Update PROGRESS.md, commit. BERHENTI dan tunggu saya membaca REKOMENDASI.md. Jangan mulai fase 2.
```

## FASE 2 — Register, Login, kerangka dashboard per peran
```
Baca AGENTS.md, PROGRESS.md, docs/REKOMENDASI.md. Ini fase 2 (autentikasi).
Baca dari docs/SRS_clean.md (lihat docs/PETA_SRS.md): use case 5.2.1 Register, 5.2.2 Login, user story 6.2.1 dan 6.2.2, dan GUI 7.1. Mockup: docs/gui/gambar_7_1.png dan gambar_7_2.png.
1. Implementasikan RegistrasiController dan LoginController sesuai alur utama, alur alternatif, dan aturan validasi di use case. Pemohon mendaftar sendiri; Verifikator/Pejabat login dengan NIP.
2. Middleware peran dan redirect dashboard sesuai peran. Buat kerangka dashboard kosong untuk 3 peran dengan navigasi (isi menyusul di fase berikutnya).
3. Catat aksi login/registrasi ke log_audit.
4. Buat file Gherkin login.feature dan register.feature dari Bab VI dan step-nya, lalu jalankan.
Selesai bila skenario hijau dan ketiga akun demo bisa login. Laporkan dan BERHENTI.
```

## FASE 3 — Mengajukan Laporan Kematian (Pemohon)
```
Baca AGENTS.md, PROGRESS.md. Ini fase 3.
Baca use case 5.2.3, user story 6.2.3, GUI 7.2 dan mockup docs/gui/gambar_7_3.png sampai gambar_7_6.png.
Implementasikan LaporanController: form stepper 4 langkah (Data Pelapor, Data Almarhum, Unggah Berkas, Review), simpan sebagai Draf, kirim (status jadi Menunggu Verifikasi), pembuat no_pengajuan unik, upload multipart ke storage dengan validasi jenis/ukuran, ubah draf, dan validasi sisi server sesuai use case. Catat ke log_audit.
Tulis Gherkin dari 6.2.3 dan jalankan. Laporkan dan BERHENTI.
```

## FASE 4 — Mengelola Status Pengajuan (Pemohon)
```
Baca AGENTS.md, PROGRESS.md. Ini fase 4.
Baca use case 5.2.5, user story 6.2.5, mockup docs/gui/gambar_7_7.png dan gambar_7_8.png. Catatan: gambar_7_10.png identik dengan gambar_7_17.png dan ternyata layar Verifikasi & Rekonsiliasi (bukan detail status), jadi untuk detail status akhir improvisasikan dari deskripsi teks Bab VII.
Implementasikan StatusPengajuanController: daftar pengajuan milik pemohon, halaman "Lacak Status Pengajuan" berupa lini masa status, tampilan catatan revisi, tombol perbaiki/kirim ulang untuk status Ditolak/Butuh Revisi, dan unduh dokumen resmi hanya bila status Selesai. Pemohon hanya boleh melihat pengajuannya sendiri (uji akses terhadap pengguna lain).
Tulis Gherkin 6.2.5, jalankan. Laporkan dan BERHENTI.
```

## FASE 5 — Mengelola User Profile
```
Baca AGENTS.md, PROGRESS.md. Ini fase 5.
Baca use case 5.2.4, user story 6.2.4 dan deskripsi teks Bab VII. Tidak ada mockup profil yang valid (gambar_7_17.png ternyata layar Rekonsiliasi), jadi rancang sendiri dengan gaya yang konsisten dengan mockup lain.
Implementasikan ProfilController untuk semua peran: lihat dan ubah profil, ubah kata sandi (verifikasi kata sandi lama, hash baru), validasi unik email/NIK. Catat ke log_audit. Gherkin 6.2.4, jalankan. Laporkan dan BERHENTI.
```

## FASE 6 — Layanan mock + Memverifikasi Berkas (Verifikator)
```
Baca AGENTS.md, PROGRESS.md. Ini fase 6.
Baca bagian 2.2.2 dan 2.2.3 (sistem eksternal), use case 5.2.6, user story 6.2.6, mockup docs/gui/gambar_7_9.png dan gambar_7_11.png.
1. Buat interface dan implementasi Mock: SIAKService (cek NIK dari database/data/siak_dummy.json), FaskesService (cek surat keterangan kematian), NotifikasiService (notifikasi sederhana di database/halaman). Mock harus bisa disimulasikan gagal/down lewat konfigurasi .env.
2. Implementasikan VerifikasiController: dasbor verifikator (statistik dan antrean Menunggu Verifikasi), detail berkas, verifikasi silang otomatis lewat layanan, setujui (status Disetujui/Menunggu TTE dan buat dokumen_resmi berstatus Menunggu), tolak/minta revisi dengan catatan, tandai anomali (membuat hasil_verifikasi dan anomali_dokumen).
3. Fallback: bila layanan down, tandai perlu_verifikasi_manual, jangan tolak otomatis, catat ke log_audit.
Gherkin 6.2.6, jalankan. Laporkan dan BERHENTI.
```

## FASE 7 — Merekonsiliasi Anomali Data
```
Baca AGENTS.md, PROGRESS.md. Ini fase 7.
Baca use case 5.2.7, user story 6.2.7, mockup docs/gui/gambar_7_11.png dan gambar_7_17.png (daftar kasus anomali).
Implementasikan RekonsiliasiController: daftar anomali terbuka, detail perbandingan data pemohon vs data SIAK/Faskes, selesaikan anomali (koreksi data atau kembalikan ke pemohon), ubah status_rekonsiliasi dan tanggal_rekonsiliasi, lanjutkan berkas ke alur normal bila selesai. Catat ke log_audit. Gherkin 6.2.7, jalankan. Laporkan dan BERHENTI.
```

## FASE 8 — Menandatangani Dokumen (TTE, Pejabat)
```
Baca AGENTS.md, PROGRESS.md. Ini fase 8.
Baca bagian 2.6 poin TTE, use case 5.2.8, user story 6.2.8, mockup docs/gui/gambar_7_12.png sampai gambar_7_16.png.
1. TTEService: interface + Mock (PIN dummy valid tertentu). PIN/OTP tidak disimpan di database.
2. TandaTanganController: daftar berkas MENUNGGU dengan statistik (Menunggu/Selesai/Dikembalikan), pencarian dan filter (No Pengajuan, NIK, status), detail dan pratinjau surat, modal konfirmasi TTE, kunci 15 menit setelah 3 kali salah beruntun (simpan penghitung kegagalan dan waktu kunci, bukan PIN), kembalikan dokumen dengan alasan.
3. Saat berhasil: status_ttd Selesai, laporan Selesai, hasilkan PDF Kutipan Akta Kematian dengan dompdf, nomor dokumen otomatis, tersedia untuk diunduh pemohon.
Gherkin 6.2.8, jalankan. Laporkan dan BERHENTI.
```

## FASE 9 — Penguatan
```
Baca AGENTS.md, PROGRESS.md, Bab XII SRS (12.1 sampai 12.3).
1. Pastikan log_audit append-only (cegah update/delete lewat model) dan semua aksi penting tercatat.
2. Keamanan sesuai 12.2: rate limit login, hash, CSRF, header keamanan dasar, validasi upload, otorisasi per peran, perlindungan IDOR. Bahas trade-off enkripsi data sensitif (NIK) dan terapkan yang aman bila tidak merusak pencarian.
3. Performa 12.1 yang realistis: index, eager loading, paginasi.
4. Responsif di ponsel, pesan error ramah, halaman 403/404/500 berbahasa Indonesia.
5. Jalankan seluruh test, rapikan README (cara instal, akun demo, cara test). Laporkan dan BERHENTI.
```

## FASE 10 — Deploy
```
Baca AGENTS.md, PROGRESS.md. Ini fase 10 (deploy). Tujuan: aplikasi hidup di URL publik gratis.
Rencana awal yang disarankan (verifikasi dulu syarat free tier terbaru sebelum menerapkan, lalu beri saya opsi bila berubah):
- Web: Render web service gratis memakai Docker (buat Dockerfile PHP + Nginx/Apache, jalankan migrate dan seed demo saat start).
- Database: Supabase (sudah dipakai sejak fase 0), gunakan connection string yang cocok untuk server (session pooler 5432 atau koneksi langsung). Cek batas dan aturan jeda (pause) proyek gratis Supabase.
- Upload berkas: filesystem Render gratis tidak permanen. Buat disk upload bisa dikonfigurasi (`local` atau `s3`) lewat .env; gunakan penyimpanan kompatibel S3 gratis bila ada, atau catat keterbatasan untuk demo.
Langkah: Dockerfile, .env.production contoh, konfigurasi APP_KEY/APP_URL/DB, force HTTPS, optimasi (config:cache, route:cache, view:cache), healthcheck, panduan deploy langkah demi langkah di docs/DEPLOY.md. Bila saya tidak bisa menjalankan deploy dari sini, buat panduan lengkap yang saya ikuti manual.
Catat bahwa web service gratis bisa tidur saat idle. Laporkan URL dan BERHENTI.
```
