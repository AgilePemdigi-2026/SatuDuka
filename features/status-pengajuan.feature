Feature: Mengelola status pengajuan
  Sebagai pemohon
  Saya ingin melihat dan melacak status pengajuan
  Agar saya mengetahui perkembangan permohonan dan dapat mengunduh akta setelah terbit

  Scenario: Melihat daftar pengajuan
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya membuka daftar pengajuan
    Then daftar pengajuan berhasil ditampilkan
    And saya melihat pengajuan "Almarhum Status BDD"

  Scenario: Tidak memiliki pengajuan
    Given pemohon tidak memiliki pengajuan terkirim
    When saya membuka daftar pengajuan
    Then daftar pengajuan berhasil ditampilkan
    And pesan belum ada pengajuan ditampilkan

  Scenario: Melihat pengajuan milik sendiri
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    And terdapat pengajuan milik pemohon lain
    When saya membuka daftar pengajuan
    Then saya melihat pengajuan "Almarhum Status BDD"
    And pengajuan milik pemohon lain tidak ditampilkan

  Scenario: Melacak nomor pengajuan valid
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya melacak nomor pengajuan tersebut
    Then saya diarahkan ke detail pengajuan tersebut

  Scenario: Melacak nomor tidak ditemukan
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya mencari nomor pengajuan "SD-99999999-9999"
    Then pesan nomor pengajuan tidak ditemukan ditampilkan

  Scenario: Melacak tanpa mengisi nomor
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya melacak tanpa mengisi nomor pengajuan
    Then validasi nomor pengajuan kosong ditampilkan

  Scenario: Melihat detail pengajuan
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya membuka detail pengajuan
    Then detail pengajuan berhasil ditampilkan

  Scenario: Melihat timeline pengajuan
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya membuka detail pengajuan
    Then lima tahapan timeline pengajuan ditampilkan

  Scenario: Melihat tahapan yang sudah selesai
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya membuka detail pengajuan
    Then tahapan yang sudah selesai ditandai

  Scenario: Melihat tahapan yang sedang berlangsung
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya membuka detail pengajuan
    Then tahapan yang sedang berlangsung ditampilkan

  Scenario: Melihat catatan revisi
    Given pemohon memiliki pengajuan yang memerlukan revisi dengan catatan "Surat keterangan kurang jelas"
    When saya membuka detail pengajuan
    Then catatan revisi ditampilkan

  Scenario: Melihat alasan penolakan
    Given pemohon memiliki pengajuan yang ditolak dengan alasan "Data tidak sesuai"
    When saya membuka detail pengajuan
    Then alasan penolakan ditampilkan

  Scenario: Mengunduh akta yang sudah diterbitkan
    Given akta sudah diterbitkan untuk pengajuan pemohon
    When saya mengunduh akta pengajuan
    Then akta berhasil diunduh

  Scenario: Tidak bisa mengunduh akta sebelum diterbitkan
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When saya mengunduh akta pengajuan
    Then unduhan akta ditolak

  Scenario: Menampilkan status terbaru setelah perubahan
    Given pemohon memiliki pengajuan dengan status "Menunggu Verifikasi"
    When status pengajuan berubah menjadi "Disetujui/Menunggu TTE"
    Then status terbaru pengajuan ditampilkan
