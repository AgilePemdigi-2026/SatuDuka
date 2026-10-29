# Sumber: SRS Bab VI, Tabel 6.2 User Story Proses Register (6.2.2).
# Penyesuaian dari naskah SRS:
#   - Kolom "Alamat Surel" WAJIB diisi (pengguna.email NOT NULL UNIQUE dan
#     dipakai untuk pengaturan ulang kata sandi).
#   - Nama lengkap tidak diminta; diambil dari SIAK bila tersedia dan memakai
#     nama bawaan bila NIK belum ada pada data kependudukan.
# Setiap skenario dipetakan ke tests/Feature/Autentikasi/DaftarTest.php karena
# integrasi Behat-Mink diganti Pest.

Feature: Register
  In order to submit a request for death registration and administration
  As a calon pemohon (masyarakat)
  I want to register and create a new account

  Scenario: Berhasil registrasi dengan NIK yang terdapat pada data kependudukan
    Given saya melihat halaman "Registrasi"
    When saya mengisi "9999990101900115" pada kolom "NIK"
    And saya mengisi "pemohon.baru@demo.satuduka.id" pada kolom "Alamat Surel"
    And saya mengisi "Baru12345" pada kolom "Kata Sandi"
    And saya mengisi "Baru12345" pada kolom "Konfirmasi Kata Sandi"
    And saya menekan tombol "Daftar"
    Then saya melihat notifikasi "Registrasi berhasil!"
    And saya melihat halaman "Masuk"

  Scenario: Berpindah ke halaman Masuk dari halaman Registrasi
    Given saya melihat halaman "Registrasi"
    When saya menekan tautan "Sudah punya akun? Masuk di sini"
    Then saya melihat halaman "Masuk"

  Scenario: Registrasi gagal karena kata sandi lemah
    Given saya melihat halaman "Registrasi"
    When saya mengisi "9999990101900114" pada kolom "NIK"
    And saya mengisi "pemohon.baru@demo.satuduka.id" pada kolom "Alamat Surel"
    And saya mengisi "abc123" pada kolom "Kata Sandi"
    And saya mengisi "abc123" pada kolom "Konfirmasi Kata Sandi"
    And saya menekan tombol "Daftar"
    Then saya melihat pesan galat di bawah kolom "Kata Sandi" "Kata sandi minimal 8 karakter dan mengandung huruf serta angka"

  Scenario: Registrasi gagal karena konfirmasi kata sandi tidak cocok
    Given saya melihat halaman "Registrasi"
    When saya mengisi "9999990101900114" pada kolom "NIK"
    And saya mengisi "pemohon.baru@demo.satuduka.id" pada kolom "Alamat Surel"
    And saya mengisi "Baru12345" pada kolom "Kata Sandi"
    And saya mengisi "Baru12346" pada kolom "Konfirmasi Kata Sandi"
    And saya menekan tombol "Daftar"
    Then saya melihat pesan galat di bawah kolom "Konfirmasi Kata Sandi" "Kata sandi tidak cocok"

  Scenario: Registrasi gagal karena format NIK tidak valid
    Given saya melihat halaman "Registrasi"
    When saya mengisi "123456789012" pada kolom "NIK"
    And saya mengisi "pemohon.baru@demo.satuduka.id" pada kolom "Alamat Surel"
    And saya mengisi "Baru12345" pada kolom "Kata Sandi"
    And saya mengisi "Baru12345" pada kolom "Konfirmasi Kata Sandi"
    And saya menekan tombol "Daftar"
    Then saya melihat pesan galat di bawah kolom "NIK" "NIK tidak valid"

  Scenario: Registrasi gagal karena NIK sudah terdaftar
    Given akun dengan NIK "9999990101900001" sudah ada
    And saya melihat halaman "Registrasi"
    When saya mengisi "9999990101900001" pada kolom "NIK"
    And saya mengisi "pemohon.baru@demo.satuduka.id" pada kolom "Alamat Surel"
    And saya mengisi "Baru12345" pada kolom "Kata Sandi"
    And saya mengisi "Baru12345" pada kolom "Konfirmasi Kata Sandi"
    And saya menekan tombol "Daftar"
    Then saya melihat pesan galat "NIK sudah terdaftar"

  Scenario: Berhasil registrasi dengan NIK 16 digit yang tidak ditemukan pada data kependudukan
    Given saya melihat halaman "Registrasi"
    When saya mengisi "9999990101900099" pada kolom "NIK"
    And saya mengisi "pemohon.baru@demo.satuduka.id" pada kolom "Alamat Surel"
    And saya mengisi "Baru12345" pada kolom "Kata Sandi"
    And saya mengisi "Baru12345" pada kolom "Konfirmasi Kata Sandi"
    And saya menekan tombol "Daftar"
    Then saya melihat notifikasi "Registrasi berhasil!"
    And saya melihat halaman "Masuk"

  Scenario: Registrasi gagal karena surel sudah terdaftar
    Given akun dengan surel "pemohon@demo.satuduka.id" sudah ada
    And saya melihat halaman "Registrasi"
    When saya mengisi "9999990101900114" pada kolom "NIK"
    And saya mengisi "pemohon@demo.satuduka.id" pada kolom "Alamat Surel"
    And saya mengisi "Baru12345" pada kolom "Kata Sandi"
    And saya mengisi "Baru12345" pada kolom "Konfirmasi Kata Sandi"
    And saya menekan tombol "Daftar"
    Then saya melihat pesan galat "Surel sudah terdaftar"

  Scenario: Registrasi gagal karena kolom kosong
    Given saya melihat halaman "Registrasi"
    When saya mengisi "9999990101900114" pada kolom "NIK"
    And saya membiarkan kolom "Kata Sandi" kosong
    And saya membiarkan kolom "Konfirmasi Kata Sandi" kosong
    And saya menekan tombol "Daftar"
    Then saya melihat pesan galat "Harap isi semua field yang diperlukan"
    And kolom yang kosong ditandai
