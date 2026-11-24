# Audit Otorisasi Rute

Dihasilkan ulang dari `php artisan route:list --json` pada 2026-11-18 (regenerasi
seluruh tabel; pengganti dokumen versi Fase 9 yang sudah usang). Total rute: 56.

Rute yang memakai parameter `{laporan}`/`{berkas}` wajib memiliki salah satu dari:
pembatasan peran, pemeriksaan `id_pengguna` pemilik, pemeriksaan `id_laporan` berkas,
atau pembatasan status yang hanya dapat dicapai lewat alur sah.

| Metode | URI | Nama | Middleware | Pemeriksaan kepemilikan/peran |
|---|---|---|---|---|
| `GET|HEAD` | `/` | `beranda` | `web` | Publik. Hanya mengalihkan ke dashboard/masuk. |
| `POST` | `atur-ulang-kata-sandi` | `atur_ulang_kata_sandi.simpan` | `web, guest, throttle:atur-ulang-kata-sandi` | Publik (guest) + throttle. Token + email diverifikasi. |
| `GET|HEAD` | `atur-ulang-kata-sandi/{token}` | `atur_ulang_kata_sandi` | `web, guest` | Publik (guest) via token surel. |
| `GET|HEAD` | `dashboard` | `dashboard` | `web, auth` | auth. Data difilter berdasarkan id_pengguna/peran pengguna aktif. |
| `POST` | `keluar` | `keluar` | `web, auth` | auth. Hanya sesi sendiri; aksi dicatat ke log_audit. |
| `GET|HEAD` | `laporan/baru` | `laporan.baru` | `web, auth, peran:pemohon` | peran:pemohon. Pengguna hanya membuat miliknya sendiri. |
| `POST` | `laporan/draf` | `laporan.draf` | `web, auth, peran:pemohon` | peran:pemohon. id_pengguna diisi dari sesi, bukan input. |
| `GET|HEAD` | `laporan/{laporan}` | `laporan.lihat` | `web, auth, peran:pemohon` | peran:pemohon + laporanMilik(). |
| `POST` | `laporan/{laporan}/berkas` | `laporan.berkas.unggah` | `web, auth, peran:pemohon, throttle:unggah-berkas` | peran:pemohon + laporanMilik() + throttle unggah. |
| `GET|HEAD` | `laporan/{laporan}/berkas/{berkas}` | `laporan.berkas` | `web, auth, peran:pemohon` | peran:pemohon + laporanMilik() + berkas milik laporan. |
| `POST` | `laporan/{laporan}/draf` | `laporan.draf.ubah` | `web, auth, peran:pemohon` | peran:pemohon + laporanMilik() + status boleh diubah. |
| `POST` | `laporan/{laporan}/kirim` | `laporan.kirim` | `web, auth, peran:pemohon` | peran:pemohon + laporanMilik() + lockForUpdate + anti ganda. |
| `POST` | `laporan/{laporan}/periksa-ai` | `laporan.periksa_ai` | `web, auth, peran:pemohon, throttle:analisis-ai` | peran:pemohon + laporanMilik() + throttle AI. |
| `GET|HEAD` | `laporan/{laporan}/ubah` | `laporan.ubah` | `web, auth, peran:pemohon` | peran:pemohon + laporanMilik() + status boleh diubah. |
| `GET|HEAD` | `lupa-kata-sandi` | `lupa_kata_sandi` | `web, guest` | Publik (guest). Form permintaan tautan. |
| `POST` | `lupa-kata-sandi` | `lupa_kata_sandi.kirim` | `web, guest, throttle:lupa-kata-sandi` | Publik (guest) + throttle. Pesan netral anti-enumerasi. |
| `GET|HEAD` | `masuk` | `masuk` | `web, guest` | Publik (guest). Form login. |
| `POST` | `masuk` | `masuk.coba` | `web, guest, throttle:masuk, throttle:masuk-ip` | Publik (guest) + throttle. Kredensial generik, penguncian 5 gagal. |
| `GET|HEAD` | `notifikasi` | `notifikasi.index` | `web, auth, peran:pemohon` | auth. Hanya notifikasi milik pengguna aktif (id_pengguna sesi). |
| `POST` | `notifikasi/tandai-semua` | `notifikasi.tandai_semua` | `web, auth, peran:pemohon` | auth. Hanya notifikasi milik pengguna aktif. |
| `POST` | `notifikasi/{notifikasi}/tandai` | `notifikasi.tandai` | `web, auth, peran:pemohon` | auth + kepemilikan: id_pengguna notifikasi == sesi, selain itu 404. |
| `GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS|QUERY` | `pengajuan-laporan` | `-` | `web` | Publik. |
| `GET|HEAD` | `pengajuan/status` | `pengajuan.status` | `web, auth, peran:pemohon` | peran:pemohon. Query dibatasi id_pengguna sesi. |
| `GET|HEAD` | `pengajuan/status/{laporan}` | `pengajuan.detail` | `web, auth, peran:pemohon` | peran:pemohon + laporanMilik(). |
| `GET|HEAD` | `pengajuan/{laporan}/dokumen` | `pengajuan.unduh` | `web, auth, peran:pemohon` | peran:pemohon + laporanMilik() + status Selesai + berkas ada. |
| `GET|HEAD` | `profil` | `profil.tampilkan` | `web, auth` | auth. Hanya data pengguna aktif. |
| `PUT` | `profil` | `profil.simpan` | `web, auth` | auth. NIK/NIP diabaikan dari request. |
| `POST` | `profil/foto` | `profil.foto` | `web, auth` | auth. Unggah foto milik sendiri; MIME + ukuran divalidasi. |
| `GET|HEAD` | `profil/foto` | `profil.foto.tampil` | `web, auth` | auth. Menyajikan foto pengguna aktif saja. |
| `PUT` | `profil/kata-sandi` | `profil.kata_sandi` | `web, auth` | auth. Memverifikasi kata sandi lama pengguna aktif. |
| `GET|HEAD` | `registrasi` | `registrasi` | `web, guest` | Publik (guest). Form registrasi. |
| `POST` | `registrasi` | `registrasi.simpan` | `web, guest, throttle:registrasi` | Publik (guest) + throttle. NIK divalidasi ke SIAK. |
| `GET|HEAD` | `rekonsiliasi` | `rekonsiliasi.index` | `web, auth, peran:verifikator` | peran:verifikator. Antrean status Dalam Rekonsiliasi. |
| `GET|HEAD` | `rekonsiliasi/{laporan}` | `rekonsiliasi.show` | `web, auth, peran:verifikator` | peran:verifikator + status harus Dalam Rekonsiliasi. |
| `POST` | `rekonsiliasi/{laporan}/selesaikan` | `rekonsiliasi.selesaikan` | `web, auth, peran:verifikator` | peran:verifikator + status Dalam Rekonsiliasi + transaksi lock. |
| `POST` | `rekonsiliasi/{laporan}/tolak-fatal` | `rekonsiliasi.tolak_fatal` | `web, auth, peran:verifikator` | peran:verifikator + status Dalam Rekonsiliasi + transaksi lock. |
| `GET|HEAD` | `tanda-tangan` | `tanda_tangan.index` | `web, auth, peran:pejabat` | peran:pejabat. Daftar dokumen seluruh pemohon (tugas pejabat). |
| `GET|HEAD` | `tanda-tangan/{laporan}` | `tanda_tangan.show` | `web, auth, peran:pejabat` | peran:pejabat + laporan harus sudah disetujui verifikator. |
| `POST` | `tanda-tangan/{laporan}/kembalikan` | `tanda_tangan.kembalikan` | `web, auth, peran:pejabat` | peran:pejabat + status Menunggu + lock. |
| `POST` | `tanda-tangan/{laporan}/tandatangani` | `tanda_tangan.tandatangani` | `web, auth, peran:pejabat, throttle:tandatangani` | peran:pejabat + status Menunggu + lock + throttle + PIN tidak pernah disimpan. |
| `POST` | `tanda-tangan/{laporan}/terbitkan` | `tanda_tangan.terbitkan` | `web, auth, peran:pejabat, throttle:terbitkan` | peran:pejabat + status Selesai + lock + throttle. |
| `GET|HEAD` | `tanda-tangan/{laporan}/unduh` | `tanda_tangan.unduh` | `web, auth, peran:pejabat` | peran:pejabat + status Selesai + berkas ada. |
| `GET|HEAD` | `up` | `-` | `-` | Publik. |
| `GET|HEAD` | `verifikasi` | `-` | `web, auth, peran:verifikator` | peran:verifikator + pemeriksaan status/kepemilikan di controller. |
| `GET|HEAD` | `verifikasi-berkas` | `verifikasi.index` | `web, auth, peran:verifikator` | peran:verifikator. Antrean lintas pemohon (sesuai tugas); pencarian NIK via blind index. |
| `GET|HEAD` | `verifikasi-berkas/{laporan}` | `verifikasi.show` | `web, auth, peran:verifikator` | peran:verifikator + status harus dalam antrean. |
| `POST` | `verifikasi-berkas/{laporan}/analisis-ai` | `verifikasi.analisis_ai` | `web, auth, peran:verifikator, throttle:analisis-ai` | peran:verifikator + berkas milik laporan + throttle AI. |
| `POST` | `verifikasi-berkas/{laporan}/anomali` | `verifikasi.anomali` | `web, auth, peran:verifikator` | peran:verifikator + lockForUpdate + override wajib bila beda rekomendasi. |
| `GET|HEAD` | `verifikasi-berkas/{laporan}/berkas/{berkas}` | `verifikasi.pratinjau` | `web, auth, peran:verifikator` | peran:verifikator + berkas milik laporan. |
| `GET|HEAD` | `verifikasi-berkas/{laporan}/berkas/{berkas}/unduh` | `verifikasi.unduh` | `web, auth, peran:verifikator` | peran:verifikator + berkas milik laporan. |
| `POST` | `verifikasi-berkas/{laporan}/perbaikan` | `verifikasi.perbaikan` | `web, auth, peran:verifikator` | peran:verifikator + lockForUpdate + override wajib bila beda rekomendasi. |
| `POST` | `verifikasi-berkas/{laporan}/revisi` | `verifikasi.revisi` | `web, auth, peran:verifikator` | peran:verifikator + lockForUpdate + override wajib bila beda rekomendasi. |
| `POST` | `verifikasi-berkas/{laporan}/ringkasan-ai` | `verifikasi.ringkasan_ai` | `web, auth, peran:verifikator, throttle:analisis-ai` | peran:verifikator + throttle AI. |
| `POST` | `verifikasi-berkas/{laporan}/setujui` | `verifikasi.setujui` | `web, auth, peran:verifikator` | peran:verifikator + lockForUpdate + override wajib bila beda rekomendasi. |
| `POST` | `verifikasi-berkas/{laporan}/tolak-fatal` | `verifikasi.tolak_fatal` | `web, auth, peran:verifikator` | peran:verifikator + lockForUpdate. |
| `GET|HEAD` | `verifikasi/{laporan}` | `-` | `web, auth, peran:verifikator` | peran:verifikator + pemeriksaan status/kepemilikan di controller. |

## Temuan dan Perbaikan (2026-11-18)

- Rute `/verifikasi` dan `/verifikasi/{laporan}` kini menjadi **redirect terautentikasi**
  ke alur baru `verifikasi-berkas` (sebelumnya 404 setelah pembenahan verifikasi v2).
- Rute `/notifikasi/*` baru: pusat notifikasi milik pengguna dengan pemeriksaan
  kepemilikan pada penandaan satu notifikasi (bukan milik pengguna lain -> 404).
- Pencarian antrean verifikasi memakai **blind index NIK** (`nik_almarhum_hash`);
  kolom NIK terenkripsi tidak pernah dicari dengan LIKE (kepatuhan AGENTS.md).
- Aksi verifikator yang berbeda dari rekomendasi mesin kini **wajib** mengisi
  `alasan_override` (dipulihkan dari alur Fase 9b).
- Rute penyaji `storage/{path}` bawaan tetap tidak terdaftar; berkas privat hanya
  disajikan lewat controller ber-otorisasi.
- Rate limit pada rute sensitif (masuk, registrasi, lupa/atur ulang sandi, unggah
  berkas, AI, tandatangani, terbitkan) dibaca dari `config/satuduka.php`.
- Enumerasi surel pada registrasi tetap ada sebagai temuan yang sengaja tidak
  diperbaiki (pesan unik dibutuhkan alur registrasi).
