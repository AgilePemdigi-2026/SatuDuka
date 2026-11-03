Feature: Memverifikasi Berkas Pengajuan
  Sebagai verifikator
  Saya ingin memeriksa dan memproses berkas pengajuan
  Agar setiap pengajuan mendapatkan keputusan verifikasi yang sesuai

  Scenario: Melihat daftar antrean pengajuan
    Given saya masuk sebagai verifikator
    When saya membuka daftar antrean pengajuan
    Then halaman antrean pengajuan berhasil ditampilkan
    And saya melihat pengajuan yang menunggu verifikasi

  Scenario: Melihat detail pengajuan
    Given terdapat pengajuan yang menunggu verifikasi
    And saya masuk sebagai verifikator
    When saya membuka detail pengajuan tersebut
    Then halaman detail pengajuan berhasil ditampilkan
    And saya melihat data laporan dan berkas pendukung

  Scenario: Menyetujui berkas yang valid
    Given terdapat pengajuan dengan berkas lengkap yang menunggu verifikasi
    And saya masuk sebagai verifikator
    When saya menyetujui pengajuan tersebut
    Then status pengajuan menjadi "Disetujui/Menunggu TTE"
    And saya melihat notifikasi persetujuan berhasil

  Scenario: Meminta revisi berkas
    Given terdapat pengajuan yang menunggu verifikasi
    And saya masuk sebagai verifikator
    When saya meminta revisi dengan alasan "Dokumen pendukung tidak terbaca"
    Then status pengajuan menjadi "Ditolak/Butuh Revisi"
    And catatan revisi tersimpan

  Scenario: Menandai berkas sebagai anomali
    Given terdapat pengajuan yang menunggu verifikasi
    And saya masuk sebagai verifikator
    When saya menandai pengajuan sebagai anomali "Perbedaan Identitas" dengan catatan "Terdapat perbedaan identitas pada dokumen"
    Then status pengajuan menjadi "Dalam Rekonsiliasi Anomali"
    And data anomali berhasil disimpan

  Scenario: Mencegah persetujuan ketika dokumen wajib belum lengkap
    Given terdapat pengajuan tanpa seluruh dokumen wajib yang menunggu verifikasi
    And saya masuk sebagai verifikator
    When saya mencoba menyetujui pengajuan tersebut
    Then status pengajuan tetap "Menunggu Verifikasi"
    And persetujuan ditolak karena dokumen belum lengkap

  Scenario: Mencegah alasan revisi yang kosong
    Given terdapat pengajuan yang menunggu verifikasi
    And saya masuk sebagai verifikator
    When saya mengirim permintaan revisi dengan alasan ""
    Then status pengajuan tetap "Menunggu Verifikasi"
    And validasi alasan revisi ditampilkan

  Scenario: Mencegah alasan revisi yang terlalu pendek
    Given terdapat pengajuan yang menunggu verifikasi
    And saya masuk sebagai verifikator
    When saya mengirim permintaan revisi dengan alasan "abc"
    Then status pengajuan tetap "Menunggu Verifikasi"
    And validasi alasan revisi ditampilkan

  Scenario: Mencegah catatan anomali yang kosong
    Given terdapat pengajuan yang menunggu verifikasi
    And saya masuk sebagai verifikator
    When saya menandai pengajuan sebagai anomali "Perbedaan Identitas" dengan catatan ""
    Then status pengajuan tetap "Menunggu Verifikasi"
    And validasi catatan anomali ditampilkan

  Scenario: Mencegah pengguna non-verifikator membuka halaman verifikasi
    Given terdapat pengajuan yang menunggu verifikasi
    And saya masuk sebagai pemohon
    When saya membuka daftar antrean pengajuan
    Then akses ke halaman verifikasi ditolak

  Scenario: Mencegah pengguna non-verifikator menyetujui pengajuan
    Given terdapat pengajuan yang menunggu verifikasi
    And saya masuk sebagai pemohon
    When saya mencoba menyetujui pengajuan tersebut
    Then akses ke aksi persetujuan ditolak
    And status pengajuan tetap "Menunggu Verifikasi"