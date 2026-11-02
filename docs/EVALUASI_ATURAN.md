# Evaluasi Aturan Verifikasi

Dokumen ini dihasilkan oleh `php artisan aturan:evaluasi` pada 31 Oktober 2026 08:42 WIB.

Label berasal dari rancangan kasus uji, sehingga evaluasi ini menguji kebenaran implementasi aturan, BUKAN akurasi terhadap data kependudukan nyata.

## Ringkasan

- Total kasus: 62
- Kasus bersih: 15
- Kasus ambigu: 5
- Kasus anomali: 42
- Kecocokan himpunan kode persis: 62 / 62 (100,00%).

## Metrik per Kode Anomali

| Kode | Dukungan | Benar Positif | Positif Palsu | Negatif Palsu | Presisi | Recall |
|---|---:|---:|---:|---:|---:|---:|
| `PENGAJUAN_GANDA` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `SIAK_SUDAH_MENINGGAL` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `KASUS_KHUSUS` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `NIK_TIDAK_VALID` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `NIK_TIDAK_DITEMUKAN` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `JENIS_KELAMIN_TIDAK_COCOK` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `TANGGAL_TIDAK_KONSISTEN` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `NAMA_TIDAK_COCOK` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `TERLAMBAT_TANPA_SPTJM` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `BERKAS_TIDAK_LENGKAP` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `DOKUMEN_TIDAK_TERBACA` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |
| `NAMA_BEDA_TIPIS` | 6 | 6 | 0 | 0 | 1,0000 | 1,0000 |
| `ALAMAT_DI_LUAR_WILAYAH` | 4 | 4 | 0 | 0 | 1,0000 | 1,0000 |
| `LAYANAN_TIDAK_TERSEDIA` | 3 | 3 | 0 | 0 | 1,0000 | 1,0000 |

## Matriks Kebingungan Kategori Nama

| Harapan \ Aktual | PERSIS | BEDA_TIPIS | TIDAK_COCOK | NA |
|---|---:|---:|---:|---:|
| PERSIS | 47 | 0 | 0 | 0 |
| BEDA_TIPIS | 0 | 6 | 0 | 0 |
| TIDAK_COCOK | 0 | 0 | 3 | 0 |
| NA | 0 | 0 | 0 | 6 |

## Kalibrasi Ambang Nama (39 Pasangan)

Ambang saat ini: `nama_cocok_persis=1`, `nama_beda_tipis=0.8`.

Hasil: 39 / 39 pasangan sesuai label.

| Pasangan | Skor | Harapan | Aktual | Status |
|---|---:|---|---|---|
| Soeharto vs Suharto | 1,0000 | PERSIS | PERSIS | Sesuai |
| Djoko vs Joko | 1,0000 | PERSIS | PERSIS | Sesuai |
| Tjokro vs Cokro | 1,0000 | PERSIS | PERSIS | Sesuai |
| Joko vs Djoko | 1,0000 | PERSIS | PERSIS | Sesuai |
| Soekarno vs Sukarno | 1,0000 | PERSIS | PERSIS | Sesuai |
| Dr. Ahmad vs Ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
| H. Ahmad vs Ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
| Hj. Siti vs Siti | 1,0000 | PERSIS | PERSIS | Sesuai |
| KH. Ahmad vs Ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
| K.H. Ahmad vs Ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
| Ir. Soekarno vs Soekarno | 1,0000 | PERSIS | PERSIS | Sesuai |
| Prof. Dr. Ir. Budi vs Budi | 1,0000 | PERSIS | PERSIS | Sesuai |
| Ahmad, S.Pd. vs Ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
| Ahmad S.Pd vs Ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
| Ahmad M.Pd. vs Ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
| M. Farhan vs Mohammad Farhan | 0,9500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| M. Farhan vs Muhammad Farhan | 0,9500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| R. Farhan vs R. Farhan | 1,0000 | PERSIS | PERSIS | Sesuai |
| R. Farhan vs Raden Farhan | 0,9500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| R. Ahmad vs Raden Ahmad | 0,9500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| R. Mas Farhan vs Mas Farhan | 1,0000 | PERSIS | PERSIS | Sesuai |
| R. Farhan vs Farhan | 0,8500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| R. M. Farhan vs Raden Muhammad Farhan | 0,8000 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| Suharto vs Suharto Wijaya | 0,8500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| Siti vs Siti Aminah | 0,8500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| Budi vs Budi Santoso | 0,8500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| Ahmad Farhan vs Ahmad Farhan Wijaya | 0,8500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| Farhan vs M. Farhan | 0,8500 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| Ahmad Farhan vs Farhan Ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
| Siti Aminah vs Aminah Siti | 1,0000 | PERSIS | PERSIS | Sesuai |
| Ahmad vs Ahmas | 0,8000 | BEDA_TIPIS | BEDA_TIPIS | Sesuai (meragukan) |
| Suharto Wijaya vs Suharto Wijoyo | 0,8571 | BEDA_TIPIS | BEDA_TIPIS | Sesuai |
| Ahmad vs Budi | 0,0000 | TIDAK_COCOK | TIDAK_COCOK | Sesuai |
| Siti Aminah vs Budi Santoso | 0,2500 | TIDAK_COCOK | TIDAK_COCOK | Sesuai |
| AHMAD vs ahmad | 1,0000 | PERSIS | PERSIS | Sesuai |
|   Ahmad   Farhan   vs Ahmad Farhan | 1,0000 | PERSIS | PERSIS | Sesuai |
| Ahmad-Farhan vs Ahmad Farhan | 1,0000 | PERSIS | PERSIS | Sesuai |
| José vs Jose | 1,0000 | PERSIS | PERSIS | Sesuai |
| Muhammad Farhan vs Mohamad Farhan | 0,8667 | BEDA_TIPIS | BEDA_TIPIS | Sesuai |

### Kasus Meragukan

- `M. Farhan` vs `Mohammad Farhan`: skor 0,9500, kategori BEDA_TIPIS (skor mendekati ambang persis).
- `M. Farhan` vs `Muhammad Farhan`: skor 0,9500, kategori BEDA_TIPIS (skor mendekati ambang persis).
- `R. Farhan` vs `Raden Farhan`: skor 0,9500, kategori BEDA_TIPIS (skor mendekati ambang persis).
- `R. Ahmad` vs `Raden Ahmad`: skor 0,9500, kategori BEDA_TIPIS (skor mendekati ambang persis).
- `R. Farhan` vs `Farhan`: skor 0,8500, kategori BEDA_TIPIS (skor berjarak maksimal 0,05 dari ambang beda tipis).
- `R. M. Farhan` vs `Raden Muhammad Farhan`: skor 0,8000, kategori BEDA_TIPIS (skor tepat di ambang beda tipis).
- `Suharto` vs `Suharto Wijaya`: skor 0,8500, kategori BEDA_TIPIS (skor berjarak maksimal 0,05 dari ambang beda tipis).
- `Siti` vs `Siti Aminah`: skor 0,8500, kategori BEDA_TIPIS (skor berjarak maksimal 0,05 dari ambang beda tipis).
- `Budi` vs `Budi Santoso`: skor 0,8500, kategori BEDA_TIPIS (skor berjarak maksimal 0,05 dari ambang beda tipis).
- `Ahmad Farhan` vs `Ahmad Farhan Wijaya`: skor 0,8500, kategori BEDA_TIPIS (skor berjarak maksimal 0,05 dari ambang beda tipis).
- `Farhan` vs `M. Farhan`: skor 0,8500, kategori BEDA_TIPIS (skor berjarak maksimal 0,05 dari ambang beda tipis).
- `Ahmad` vs `Ahmas`: skor 0,8000, kategori BEDA_TIPIS (skor tepat di ambang beda tipis).

### Keputusan Ambang

Ambang **tidak diubah** (tetap `0.8`). Alasan: Seluruh pasangan berlabel sudah sesuai dengan ambang saat ini dan tidak ada label negatif yang berjarak dekat dengan ambang. Mengubah ambang tanpa data lapangan berisiko menggeser kategori tanpa bukti.

Skor positif terendah pada dataset: `0,8000`. Pasangan dengan skor tepat di ambang atau mendekati ambang perlu dikonfirmasi ke Dukcapil sebelum ambang dinaikkan.

## Sebaran Alamat Kasus Uji

| Kecamatan | Jumlah Kasus |
|---|---:|
| Umbulharjo | 8 |
| Gondokusuman | 6 |
| Jetis | 5 |
| Mergangsan | 5 |
| Kotagede | 5 |
| Tegalrejo | 4 |
| Danurejan | 4 |
| Wirobrajan | 4 |
| Mantrijeron | 4 |
| Luar Kota Yogyakarta | 4 |
| Gedongtengen | 3 |
| Ngampilan | 3 |
| Kraton | 3 |
| Gondomanan | 2 |
| Pakualaman | 2 |

## Kasus yang Tidak Sesuai Label

Tidak ada kasus yang berbeda dari label pada dataset ini.

## Keterbatasan dan Catatan Jujur

- Label dibuat dari rancangan kasus uji, bukan dari keputusan petugas Dukcapil. Karena itu angka presisi dan recall mengukur kesesuaian implementasi dengan rancangan aturan, bukan akurasi di lapangan.
- Sebagian kasus memakai data SIAK fiktif yang ditempelkan langsung; pengujian tidak memanggil API SIAK atau AI nyata.
- Evaluasi berjalan dalam transaksi yang di-rollback sehingga tidak mengubah data aplikasi.
- Ambang nama belum diuji pada variasi ejaan, gelar, dan nama tunggal yang luas. Hasil 39 pasangan hanya menjadi batas awal, bukan bukti statistik.
- Kasus dengan layanan SIAK/AI gagal hanya menguji jalur fallback manual, bukan perilaku penyedia eksternal yang sebenarnya.

## Hal yang Perlu Dikonfirmasi ke Dukcapil

- Daftar berkas wajib dan apakah KTP saksi diwajibkan di Kota Yogyakarta.
- Bentuk resmi SPTJM Kebenaran Data Kematian dan apakah dipakai untuk keterlambatan lebih dari 30 hari.
- Perlakuan nama tunggal, gelar adat, inisial, dan ejaan lama pada dokumen kependudukan.
- Kebijakan penanganan NIK ganda, identitas fiktif, dan sengketa data sebelum penolakan permanen.
- Apakah surat pengantar RT/RW atau surat kelurahan menjadi syarat lokal.
