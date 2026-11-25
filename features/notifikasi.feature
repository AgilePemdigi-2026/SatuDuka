# Sumber: SRS 5.2.8 (pusat notifikasi) dan D7 (tombol tandai dibaca).
# Skenario ini dijalankan oleh Behat melalui features/bootstrap/FeatureContext.php.

Feature: Pusat Notifikasi
  Sebagai pemohon
  Saya ingin membaca dan menandai notifikasi
  Agar saya mengetahui tindakan yang perlu dilakukan

  Scenario: Notifikasi belum dibaca menampilkan tombol tandai dibaca
    Given terdapat notifikasi belum dibaca milik pemohon
    And saya masuk sebagai pemohon
    When saya membuka pusat notifikasi
    Then halaman pusat notifikasi menampilkan tombol "Tandai dibaca"
    And halaman pusat notifikasi menampilkan tombol "Tandai Semua Dibaca"

  Scenario: Menandai satu notifikasi sebagai dibaca
    Given terdapat notifikasi belum dibaca milik pemohon
    And saya masuk sebagai pemohon
    When saya membuka pusat notifikasi
    And saya menandai notifikasi tersebut sebagai dibaca
    Then notifikasi tersebut berstatus sudah dibaca
    And halaman pusat notifikasi menampilkan label "Sudah dibaca"
    And halaman pusat notifikasi tidak menampilkan tombol "Tandai dibaca"

  Scenario: Tombol tandai semua tidak tampil saat semua notifikasi sudah dibaca
    Given terdapat notifikasi yang sudah dibaca milik pemohon
    And saya masuk sebagai pemohon
    When saya membuka pusat notifikasi
    Then halaman pusat notifikasi tidak menampilkan tombol "Tandai Semua Dibaca"

  Scenario: Pemohon tidak dapat menandai notifikasi milik pengguna lain
    Given terdapat notifikasi belum dibaca milik pengguna lain
    And saya masuk sebagai pemohon
    When saya mencoba menandai notifikasi milik pengguna lain
    Then akses ke notifikasi milik pengguna lain ditolak
    And notifikasi tersebut berstatus belum dibaca
