# Audit Otorisasi Rute

Dihasilkan dari `php artisan route:list` pada sesi Fase 9. Total rute: 56.

Kolom terakhir menjelaskan pemeriksaan kepemilikan/peran di controller. Rute yang
memakai parameter `{laporan}`/`{berkas}`/`{dokumen}` wajib memiliki salah satu dari:
pembatasan peran, pemeriksaan `id_pengguna` pemilik, pemeriksaan `id_laporan` berkas,
atau pembatasan status yang hanya dapat dicapai lewat alur sah.

| Metode | URI | Nama | Middleware | Pemeriksaan kepemilikan/peran |
|---|---|---|---|---|
| `GET|HEAD` | `/` | `beranda` | `web` | Publik. Hanya mengalihkan ke dashboard/masuk. |
| `POST` | `atur-ulang-kata-sandi` | `atur_ulang_kata_sandi.simpan` | `web`, `guest`, `throttle:atur-ulang-kata-sandi` | Publik (guest) + throttle. Token + email diverifikasi. |
| `GET|HEAD` | `atur-ulang-kata-sandi/{token}` | `atur_ulang_kata_sandi` | `web`, `guest` | Publik (guest) via token surel. |
| `GET|HEAD` | `dashboard` | `dashboard` | `web`, `auth` | auth. Data difilter berdasarkan `id_pengguna`/peran pengguna aktif. |
| `POST` | `keluar` | `keluar` | `web`, `auth` | auth. Hanya sesi sendiri; aksi dicatat ke log_audit. |
| `GET|HEAD` | `laporan/baru` | `laporan.baru` | `web`, `auth`, `peran:pemohon` | peran:pemohon. Pengguna hanya membuat miliknya sendiri. |
| `POST` | `laporan/draf` | `laporan.draf` | `web`, `auth`, `peran:pemohon` | peran:pemohon. `id_pengguna` diisi dari sesi, bukan input. |
| `GET|HEAD` | `laporan/{laporan}` | `laporan.lihat` | `web`, `auth`, `peran:pemohon` | peran:pemohon + `laporanMilik()` (id_pengguna == sesi). |
| `POST` | `laporan/{laporan}/berkas` | `laporan.berkas.unggah` | `web`, `auth`, `peran:pemohon`, `throttle:unggah-berkas` | peran:pemohon + `laporanMilik()` + throttle unggah. |
| `GET|HEAD` | `laporan/{laporan}/berkas/{berkas}` | `laporan.berkas` | `web`, `auth`, `peran:pemohon` | peran:pemohon + `laporanMilik()` + berkas harus milik laporan. |
| `POST` | `laporan/{laporan}/draf` | `laporan.draf.ubah` | `web`, `auth`, `peran:pemohon` | peran:pemohon + `laporanMilik()` + status boleh diubah. |
| `POST` | `laporan/{laporan}/kirim` | `laporan.kirim` | `web`, `auth`, `peran:pemohon` | peran:pemohon + `laporanMilik()` + status boleh diubah. |
| `POST` | `laporan/{laporan}/periksa-ai` | `laporan.periksa_ai` | `web`, `auth`, `peran:pemohon`, `throttle:analisis-ai` | peran:pemohon + `laporanMilik()` + throttle AI. |
| `GET|HEAD` | `laporan/{laporan}/ubah` | `laporan.ubah` | `web`, `auth`, `peran:pemohon` | peran:pemohon + `laporanMilik()` + status boleh diubah. |
| `GET|HEAD` | `lupa-kata-sandi` | `lupa_kata_sandi` | `web`, `guest` | Publik (guest). Form permintaan tautan. |
| `POST` | `lupa-kata-sandi` | `lupa_kata_sandi.kirim` | `web`, `guest`, `throttle:lupa-kata-sandi` | Publik (guest) + throttle. Pesan netral (anti-enumerasi). |
| `GET|HEAD` | `masuk` | `masuk` | `web`, `guest` | Publik (guest). Form login. |
| `POST` | `masuk` | `masuk.coba` | `web`, `guest`, `throttle:masuk` | Publik (guest) + throttle. Kredensial generik, penguncian 5 gagal. |
| `GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS|QUERY` | `pengajuan-laporan` | — | `web` | Publik. Redirect ke `/laporan/baru` yang dilindungi auth. |
| `GET|HEAD` | `pengajuan/status` | `pengajuan.status` | `web`, `auth`, `peran:pemohon` | peran:pemohon. Query dibatasi `id_pengguna` sesi. |
| `GET|HEAD` | `pengajuan/status/{laporan}` | `pengajuan.detail` | `web`, `auth`, `peran:pemohon` | peran:pemohon + `laporanMilik()`. |
| `GET|HEAD` | `pengajuan/{laporan}/dokumen` | `pengajuan.unduh` | `web`, `auth`, `peran:pemohon` | peran:pemohon + `laporanMilik()` + status Selesai + berkas ada. |
| `GET|HEAD` | `profil` | `profil.tampilkan` | `web`, `auth` | auth. Hanya data `$request->user()`. |
| `PUT` | `profil` | `profil.simpan` | `web`, `auth` | auth. Memperbarui baris pengguna aktif saja; NIK/NIP diabaikan. |
| `POST` | `profil/foto` | `profil.foto` | `web`, `auth` | auth. Unggah foto milik sendiri; MIME + ukuran divalidasi. |
| `GET|HEAD` | `profil/foto` | `profil.foto.tampil` | `web`, `auth` | auth. Menyajikan foto pengguna aktif saja (bukan parameter id). |
| `PUT` | `profil/kata-sandi` | `profil.kata_sandi` | `web`, `auth` | auth. Memverifikasi kata sandi lama pengguna aktif. |
| `GET|HEAD` | `registrasi` | `registrasi` | `web`, `guest` | Publik (guest). Form registrasi. |
| `POST` | `registrasi` | `registrasi.simpan` | `web`, `guest`, `throttle:registrasi` | Publik (guest) + throttle. NIK divalidasi ke SIAK. |
| `GET|HEAD` | `rekonsiliasi` | `rekonsiliasi.index` | `web`, `auth`, `peran:verifikator` | peran:verifikator. Antrean status Dalam Rekonsiliasi. |
| `GET|HEAD` | `rekonsiliasi/{laporan}` | `rekonsiliasi.show` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status harus Dalam Rekonsiliasi. |
| `POST` | `rekonsiliasi/{laporan}/selesaikan` | `rekonsiliasi.selesaikan` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status Dalam Rekonsiliasi + transaksi lock. |
| `POST` | `rekonsiliasi/{laporan}/tolak-fatal` | `rekonsiliasi.tolak_fatal` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status Dalam Rekonsiliasi + transaksi lock. |
| `GET|HEAD` | `tanda-tangan` | `tanda_tangan.index` | `web`, `auth`, `peran:pejabat` | peran:pejabat. Daftar dokumen seluruh pemohon (tugas pejabat). |
| `GET|HEAD` | `tanda-tangan/{laporan}` | `tanda_tangan.show` | `web`, `auth`, `peran:pejabat` | peran:pejabat + laporan harus sudah disetujui verifikator. |
| `POST` | `tanda-tangan/{laporan}/kembalikan` | `tanda_tangan.kembalikan` | `web`, `auth`, `peran:pejabat` | peran:pejabat + status Menunggu + lock. |
| `POST` | `tanda-tangan/{laporan}/tandatangani` | `tanda_tangan.tandatangani` | `web`, `auth`, `peran:pejabat`, `throttle:tandatangani` | peran:pejabat + status Menunggu + lock + throttle. |
| `POST` | `tanda-tangan/{laporan}/terbitkan` | `tanda_tangan.terbitkan` | `web`, `auth`, `peran:pejabat`, `throttle:terbitkan` | peran:pejabat + status Selesai + lock + throttle. |
| `GET|HEAD` | `tanda-tangan/{laporan}/unduh` | `tanda_tangan.unduh` | `web`, `auth`, `peran:pejabat` | peran:pejabat + status Selesai + berkas ada. |
| `GET|HEAD` | `up` | — | — | Health check bawaan Laravel (publik). |
| `GET|HEAD` | `verifikasi` | — | `web`, `auth`, `peran:verifikator` | BELUM DIPETAKAN |
| `GET|HEAD` | `verifikasi-berkas` | `verifikasi.index` | `web`, `auth`, `peran:verifikator` | peran:verifikator. Seluruh antrean lintas pemohon (sesuai tugas). |
| `GET|HEAD` | `verifikasi-berkas/{laporan}` | `verifikasi.show` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status harus dalam antrean. |
| `POST` | `verifikasi-berkas/{laporan}/analisis-ai` | `verifikasi.analisis_ai` | `web`, `auth`, `peran:verifikator`, `throttle:analisis-ai` | peran:verifikator + berkas harus milik laporan + throttle AI. |
| `POST` | `verifikasi-berkas/{laporan}/anomali` | `verifikasi.anomali` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status boleh diverifikasi + transaksi lock. |
| `GET|HEAD` | `verifikasi-berkas/{laporan}/berkas/{berkas}` | `verifikasi.pratinjau` | `web`, `auth`, `peran:verifikator` | peran:verifikator + `berkas->id_laporan == laporan->id_laporan`. |
| `GET|HEAD` | `verifikasi-berkas/{laporan}/berkas/{berkas}/unduh` | `verifikasi.unduh` | `web`, `auth`, `peran:verifikator` | peran:verifikator + `berkas->id_laporan == laporan->id_laporan`. |
| `POST` | `verifikasi-berkas/{laporan}/perbaikan` | `verifikasi.perbaikan` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status boleh diverifikasi + transaksi lock. |
| `POST` | `verifikasi-berkas/{laporan}/revisi` | `verifikasi.revisi` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status boleh diverifikasi + transaksi lock. |
| `POST` | `verifikasi-berkas/{laporan}/ringkasan-ai` | `verifikasi.ringkasan_ai` | `web`, `auth`, `peran:verifikator`, `throttle:analisis-ai` | peran:verifikator + throttle AI. |
| `POST` | `verifikasi-berkas/{laporan}/setujui` | `verifikasi.setujui` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status boleh diverifikasi + transaksi lock. |
| `POST` | `verifikasi-berkas/{laporan}/tolak-fatal` | `verifikasi.tolak_fatal` | `web`, `auth`, `peran:verifikator` | peran:verifikator + status boleh diverifikasi + transaksi lock. |
| `GET|HEAD` | `verifikasi/{laporan}` | — | `web`, `auth`, `peran:verifikator` | Redirect rute lama; tetap peran:verifikator. |
| `POST` | `verifikasi/{laporan}/analisis-ai` | — | `web`, `auth`, `peran:verifikator` | Redirect rute lama; tetap peran:verifikator. |
| `GET|HEAD` | `verifikasi/{laporan}/berkas/{berkas}` | — | `web`, `auth`, `peran:verifikator` | Redirect rute lama; tetap peran:verifikator. |
| `POST` | `verifikasi/{laporan}/ringkasan-ai` | — | `web`, `auth`, `peran:verifikator` | Redirect rute lama; tetap peran:verifikator. |

## Temuan dan Perbaikan

- Rute bawaan `storage/{path}` (dari `filesystems.disks.local.serve = true`) tidak lagi
  terdaftar setelah `serve` diubah menjadi `false`; berkas privat hanya disajikan lewat
  controller ber-otorisasi.
- Tidak ditemukan IDOR pada rute `{laporan}`/`{berkas}`/`{dokumen}`: seluruh akses pemohon
  memakai `laporanMilik()`, berkas diverifikasi terhadap `id_laporan`, dan aksi status
  memakai transaksi + `lockForUpdate`.
- Rate limit ditambahkan pada rute sensitif (masuk, registrasi, lupa/atur ulang sandi,
  unggah berkas, analisis/ringkasan AI, tandatangani, terbitkan) dan nilainya dibaca dari
  `config/satuduka.php`.
- Enumerasi surel masih mungkin terjadi pada registrasi (`Surel sudah terdaftar`), karena
  pesan itu dibutuhkan alur registrasi; dicatat sebagai temuan yang tidak diperbaiki.
