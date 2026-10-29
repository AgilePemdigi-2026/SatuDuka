Feature: Pengajuan laporan kematian
  Sebagai pemohon
  Saya ingin mengajukan laporan kematian
  Agar laporan dapat diproses oleh petugas

  Scenario: Pemohon membuka formulir pengajuan
    Given pemohon sudah login
    When pemohon membuka halaman pengajuan laporan
    Then halaman pengajuan berhasil ditampilkan
    And bagian Data Pelapor ditampilkan
    And tombol Lanjut tersedia
    And bagian Data Almarhum belum ditampilkan

  Scenario: Pemohon mengisi data dan menyimpan draf
    Given pemohon sudah login
    And pemohon berada pada formulir pengajuan
    When pemohon menyimpan data pelapor dan almarhum yang lengkap
    Then data pengajuan tersimpan sebagai draf
    And pemohon dapat melanjutkan pengajuan

  Scenario: Pemohon mengirim pengajuan dengan data lengkap
    Given pemohon sudah login
    And data pelapor dan almarhum sudah lengkap
    And seluruh berkas wajib sudah diunggah
    And pemohon berada pada halaman review
    When pemohon menyetujui pernyataan dan mengirim pengajuan
    Then pengajuan berhasil dikirim
    And pemohon diarahkan ke halaman detail pengajuan

  Scenario: Pemohon mengirim pengajuan tanpa bukti kematian
    Given pemohon sudah login
    And data pengajuan sudah lengkap
    And tidak ada bukti kematian yang diunggah
    When pemohon mengirim pengajuan
    Then pengajuan ditolak karena bukti kematian tidak tersedia

  Scenario: Pemohon mencoba mengakses laporan milik orang lain
    Given terdapat laporan milik pemohon lain
    And pemohon sudah login
    When pemohon mencoba melihat atau mengubah laporan tersebut
    Then akses ke laporan milik pemohon lain ditolak