# Sumber: SRS Bab V Tabel 5.4 (use case 5.2.4 Mengelola User Profile) dan
# Bab VI Tabel 6.4 (user story 6.2.4), serta deskripsi Gambar 7.17 Bab VII.
# Skenario ini dijalankan oleh Behat melalui features/bootstrap/FeatureContext.php.
# Tes Pest terkait tersedia sebagai cakupan regresi tambahan.

Feature: Kelola Profil
  In order to menjaga data pribadi dan keamanan akun
  As a Pengguna (Pemohon, Verifikator, atau Pejabat)
  I want to melihat dan memperbarui profil serta kata sandi saya

  Scenario: Menampilkan halaman Kelola Profil
    Given saya sudah masuk sebagai "Pemohon"
    When saya membuka menu "Kelola Profil"
    Then saya melihat bagian "Foto Profil", "Data Diri", dan "Pengaturan Keamanan"
    And saya melihat NIK saya tetapi tidak dapat mengubahnya

  Scenario: Berhasil memperbarui data diri
    Given saya sudah masuk sebagai "Pemohon"
    When saya membuka menu "Kelola Profil"
    And saya mengubah "Nama Lengkap", "Alamat Surel", "Nomor Telepon", dan "Alamat"
    And saya menekan tombol "Simpan Perubahan"
    Then saya melihat notifikasi "Profil berhasil diperbarui."
    And data saya tersimpan di database

  Scenario: Format data diri tidak valid
    Given saya sudah masuk sebagai "Pemohon"
    When saya membuka menu "Kelola Profil"
    And saya mengubah "Nama Lengkap", "Alamat Surel", "Nomor Telepon", dan "Alamat"
    And saya mengisi "Alamat Surel" dengan "bukan-email"
    And saya mengisi "Nomor Telepon" dengan "telepon-salah"
    And saya menekan tombol "Simpan Perubahan"
    Then saya melihat pesan kesalahan pada kolom "Alamat Surel"
    And saya melihat pesan kesalahan pada kolom "Nomor Telepon"
    And data saya tidak berubah

  Scenario: Email atau nomor telepon sudah dipakai pengguna lain
    Given saya sudah masuk sebagai "Pemohon"
    And sudah ada pengguna lain dengan email atau nomor telepon tertentu
    When saya membuka menu "Kelola Profil"
    And saya mengisi data diri dengan email atau nomor telepon milik pengguna lain
    And saya menekan tombol "Simpan Perubahan"
    Then saya melihat pesan "Email / Nomor Telepon sudah digunakan oleh pengguna lain."
    And data saya tidak berubah

  Scenario: Foto profil tidak valid
    Given saya sudah masuk sebagai "Pemohon"
    When saya membuka menu "Kelola Profil"
    And saya mengunggah berkas yang bukan JPG/PNG atau lebih dari 2 MB
    Then saya melihat pesan "Ukuran berkas melebihi batas (maks. 2MB) atau format tidak didukung (gunakan JPG/PNG)."
    And foto profil lama saya tetap tersimpan

  Scenario: Berhasil mengganti foto profil
    Given saya sudah masuk sebagai "Pemohon"
    When saya membuka menu "Kelola Profil"
    And saya mengunggah foto berformat JPG atau PNG maksimal 2 MB
    Then foto profil baru saya tersimpan
    And foto profil lama saya dihapus

  Scenario: Berhasil mengubah kata sandi
    Given saya sudah masuk sebagai "Pemohon"
    And saya berada pada bagian "Pengaturan Keamanan"
    When saya mengisi "Kata Sandi Lama" dengan kata sandi yang benar
    And saya mengisi "Kata Sandi Baru" dengan "sandiBaru1"
    And saya mengisi "Konfirmasi Kata Sandi" dengan "sandiBaru1"
    And saya menekan tombol "Simpan Perubahan"
    Then saya melihat notifikasi "Kata sandi berhasil diperbarui"
    And kata sandi saya tersimpan dalam bentuk hash

  Scenario: Kata sandi lama tidak sesuai
    Given saya sudah masuk sebagai "Pemohon"
    And saya berada pada bagian "Pengaturan Keamanan"
    When saya mengisi "Kata Sandi Lama" dengan kata sandi yang salah
    And saya mengisi "Kata Sandi Baru" dengan "sandiBaru1"
    And saya mengisi "Konfirmasi Kata Sandi" dengan "sandiBaru1"
    And saya menekan tombol "Simpan Perubahan"
    Then saya melihat pesan "Kata sandi lama tidak sesuai" di bawah kolom "Kata Sandi Lama"
    And kata sandi saya tidak berubah

  Scenario: Kata sandi baru terlalu lemah
    Given saya sudah masuk sebagai "Pemohon"
    And saya berada pada bagian "Pengaturan Keamanan"
    When saya mengisi "Kata Sandi Lama" dengan kata sandi yang benar
    And saya mengisi "Kata Sandi Baru" dengan "abc"
    And saya menekan tombol "Simpan Perubahan"
    Then saya melihat pesan "Kata sandi minimal 8 karakter dan harus mengandung huruf serta angka"
    And kata sandi saya tidak berubah

  Scenario: Konfirmasi kata sandi tidak cocok
    Given saya sudah masuk sebagai "Pemohon"
    And saya berada pada bagian "Pengaturan Keamanan"
    When saya mengisi "Kata Sandi Lama" dengan kata sandi yang benar
    And saya mengisi "Kata Sandi Baru" dengan "sandiBaru1"
    And saya mengisi "Konfirmasi Kata Sandi" dengan "sandiLain2"
    And saya menekan tombol "Simpan Perubahan"
    Then saya melihat pesan "Konfirmasi kata sandi tidak cocok"
    And kata sandi saya tidak berubah

  Scenario: NIK tidak dapat diubah melalui permintaan
    Given saya sudah masuk sebagai "Pemohon"
    When saya mengirim perubahan profil disertai NIK yang berbeda
    Then NIK saya tetap seperti semula

  Scenario: Pengguna hanya mengubah profilnya sendiri
    Given saya sudah masuk sebagai "Pemohon"
    And ada pengguna lain dengan profil tertentu
    When saya memperbarui profil saya
    Then hanya profil saya yang berubah
    And profil pengguna lain tidak berubah
