# Sumber: SRS 5.2.7 dan 6.2.7 (rekonsiliasi anomali data kependudukan).
# Perbandingan data hanya antara isian pemohon dan hasil ekstraksi surat
# keterangan; SATUDUKA tidak lagi membaca data kependudukan (SIAK).
# Skenario ini dijalankan oleh Behat melalui features/bootstrap/FeatureContext.php.

Feature: Rekonsiliasi Anomali
  Sebagai verifikator
  Saya ingin merekonsiliasi anomali data pengajuan
  Agar berkas dapat dilanjutkan atau ditolak secara sah

  Scenario: Melihat antrean rekonsiliasi anomali
    Given terdapat pengajuan dalam rekonsiliasi anomali
    And saya masuk sebagai verifikator
    When saya membuka halaman rekonsiliasi
    Then halaman antrean rekonsiliasi berhasil ditampilkan
    And saya melihat pengajuan yang berada dalam rekonsiliasi

  Scenario: Komparasi data membandingkan isian pemohon dengan ekstraksi dokumen
    Given terdapat pengajuan dalam rekonsiliasi anomali dengan hasil ekstraksi dokumen
    And saya masuk sebagai verifikator
    When saya membuka detail rekonsiliasi tersebut
    Then halaman detail rekonsiliasi berhasil ditampilkan
    And komparasi data menampilkan kolom "Data Pemohon"
    And komparasi data menampilkan kolom "Ekstraksi Surat Keterangan"
    And komparasi data tidak memiliki kolom "Data SIAK"

  Scenario: Aksi rekonsiliasi memakai satu dropdown dengan nilai awal selesaikan
    Given terdapat pengajuan dalam rekonsiliasi anomali
    And saya masuk sebagai verifikator
    When saya membuka detail rekonsiliasi tersebut
    Then halaman menampilkan dropdown aksi rekonsiliasi dengan pilihan awal "selesaikan"
    And halaman menampilkan ketiga panel aksi rekonsiliasi

  Scenario: Menyelesaikan rekonsiliasi mengembalikan berkas ke antrean verifikasi
    Given terdapat pengajuan dalam rekonsiliasi anomali
    And saya masuk sebagai verifikator
    When saya menyelesaikan rekonsiliasi dengan catatan "Data diperbaiki sesuai surat keterangan"
    Then status pengajuan menjadi "Menunggu Verifikasi"
    And anomali rekonsiliasi berstatus "Selesai"
    And saya melihat notifikasi "Anomali berhasil diselesaikan dan berkas dikembalikan ke antrean verifikasi."

  Scenario: Menolak permanen menyimpan alasan eskalasi dan catatan resmi
    Given terdapat pengajuan dalam rekonsiliasi anomali
    And saya masuk sebagai verifikator
    When saya menolak permanen rekonsiliasi dengan alasan eskalasi "Identitas fiktif" dan catatan resmi "Pengajuan ditolak permanen"
    Then status pengajuan menjadi "Ditolak - Anomali Data Fatal"
    And catatan revisi pengajuan tersimpan sebagai "Pengajuan ditolak permanen"
    And anomali rekonsiliasi berstatus "Ditolak - Anomali Data Fatal"
    And alasan eskalasi tersimpan pada hasil verifikasi sebagai "Identitas fiktif"

  Scenario: Mencegah pemohon membuka halaman rekonsiliasi
    Given terdapat pengajuan dalam rekonsiliasi anomali
    And saya masuk sebagai pemohon
    When saya membuka halaman rekonsiliasi
    Then akses ke halaman rekonsiliasi ditolak
