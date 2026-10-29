# Sumber: SRS Bab VI, Tabel 6.1 User Story Proses Login (6.2.1).
# Penyesuaian dari naskah SRS:
#   - Satu kolom "NIK/NIP" untuk semua peran (16 digit = NIK, 18 digit = NIP).
#   - Kredensial demo memakai kata sandi "password123" (docs/DATABASE.md).
#   - Pengaturan ulang kata sandi dikirim ke surel terdaftar, bukan menampilkan
#     formulir kata sandi baru langsung setelah menekan "Kirim".
# Skenario ini dijalankan oleh Behat melalui features/bootstrap/FeatureContext.php.
# Tes Pest terkait tersedia sebagai cakupan regresi tambahan.

Feature: Login
  In order to access the system with proper authentication and authorization
  As a Petugas (Verifikator Dukcapil and Pejabat) or Pemohon
  I want to login using my credentials

  Scenario: Berpindah ke halaman Registrasi dari halaman Masuk
    Given saya melihat halaman "Masuk"
    When saya menekan tautan "Belum punya akun? Daftar di sini"
    Then saya melihat halaman "Registrasi"

  Scenario: Berhasil mengatur ulang kata sandi melalui tautan surel
    Given saya melihat halaman "Masuk"
    When saya menekan tautan "Lupa Kata Sandi?"
    Then saya melihat halaman "Lupa Kata Sandi"
    When saya mengisi "9999990101900001" pada kolom "NIK/NIP"
    And saya menekan tombol "Kirim"
    Then tautan pengaturan ulang dikirim ke surel akun
    When saya membuka tautan pengaturan ulang dari surel
    Then saya melihat halaman "Kata Sandi Baru"
    When saya mengisi "Baru12345" pada kolom "Kata Sandi"
    And saya mengisi "Baru12345" pada kolom "Konfirmasi Kata Sandi"
    And saya menekan tombol "Simpan"
    Then saya melihat notifikasi "Kata sandi berhasil direset. Silakan login."
    And saya melihat halaman "Masuk"
    And kata sandi baru dapat digunakan untuk masuk

  Scenario: Tersedia kontrol untuk menampilkan kata sandi
    Given saya melihat halaman "Masuk"
    When saya menekan ikon "tampilkan kata sandi"
    Then kontrol tampilkan kata sandi terhubung ke kolom kata sandi

  Scenario: Berhasil masuk dengan kredensial Pemohon yang valid
    Given saya melihat halaman "Masuk"
    When saya mengisi "9999990101900001" pada kolom "NIK/NIP"
    And saya mengisi "password123" pada kolom "Kata Sandi"
    And saya menekan tombol "Masuk"
    Then saya diarahkan ke dashboard Pemohon

  Scenario: Berhasil masuk dengan kredensial Verifikator yang valid (NIP)
    Given saya melihat halaman "Masuk"
    When saya mengisi "999999991234567890" pada kolom "NIK/NIP"
    And saya mengisi "password123" pada kolom "Kata Sandi"
    And saya menekan tombol "Masuk"
    Then saya diarahkan ke dashboard Verifikator

  Scenario: Berhasil masuk dengan kredensial Pejabat yang valid (NIP)
    Given saya melihat halaman "Masuk"
    When saya mengisi "999999992345678901" pada kolom "NIK/NIP"
    And saya mengisi "password123" pada kolom "Kata Sandi"
    And saya menekan tombol "Masuk"
    Then saya diarahkan ke dashboard Pejabat

  Scenario: Berhasil masuk dengan opsi Ingat Saya
    Given saya melihat halaman "Masuk"
    When saya mengisi "9999990101900001" pada kolom "NIK/NIP"
    And saya mengisi "password123" pada kolom "Kata Sandi"
    And saya mencentang opsi "Ingat Saya"
    And saya menekan tombol "Masuk"
    Then sesi saya diingat pada perangkat tersebut

  Scenario: Gagal masuk karena kata sandi salah
    Given saya melihat halaman "Masuk"
    When saya mengisi "9999990101900001" pada kolom "NIK/NIP"
    And saya mengisi "password124" pada kolom "Kata Sandi"
    And saya menekan tombol "Masuk"
    Then saya melihat pesan galat "NIK/NIP atau kata sandi salah"

  Scenario: Gagal masuk karena NIK/NIP tidak valid
    Given saya melihat halaman "Masuk"
    When saya mengisi "123456789012" pada kolom "NIK/NIP"
    And saya mengisi "password123" pada kolom "Kata Sandi"
    And saya menekan tombol "Masuk"
    Then saya melihat pesan galat "NIK/NIP atau kata sandi salah"

  Scenario: Gagal masuk karena kolom kosong
    Given saya melihat halaman "Masuk"
    When saya mengisi "9999990101900001" pada kolom "NIK/NIP"
    And saya membiarkan kolom "Kata Sandi" kosong
    And saya menekan tombol "Masuk"
    Then saya melihat pesan galat "Harap isi semua field yang diperlukan"
    And kolom yang kosong ditandai

  Scenario: Gagal masuk karena NIK/NIP kosong
    Given saya melihat halaman "Masuk"
    When saya membiarkan kolom "NIK/NIP" kosong
    And saya mengisi "password123" pada kolom "Kata Sandi"
    And saya menekan tombol "Masuk"
    Then saya melihat pesan galat "Harap isi semua field yang diperlukan"
    And kolom yang kosong ditandai

  Scenario: Akun terkunci setelah terlalu banyak percobaan gagal
    Given saya melihat halaman "Masuk"
    And saya telah mencapai batas maksimum percobaan login gagal
    When saya menekan tombol "Masuk"
    Then saya melihat pesan galat "Terlalu banyak percobaan gagal. Akun Anda dikunci sementara. Coba lagi dalam 15 menit"

  Scenario: Gagal masuk saat akun terkunci
    Given akun NIK "9999990101900001" sedang terkunci
    When saya mengisi "9999990101900001" pada kolom "NIK/NIP"
    And saya mengisi "password123" pada kolom "Kata Sandi"
    And saya menekan tombol "Masuk"
    Then saya melihat pesan galat "Akun Anda dikunci sementara"
