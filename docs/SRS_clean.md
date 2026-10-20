**PEMBANGUNAN PERANGKAT LUNAK (PRAKTIKUM)** \- **I4**  
**SOFTWARE REQUIREMENTS SPECIFICATION (SRS)**

**SATUDUKA: DIGITALISASI PENCATATAN DAN ADMINISTRASI KEMATIAN KOTA YOGYAKARTA**

**Dosen Pengajar:**  
Indra Kharisma Raharjana, S. Kom., M. T.

**Disusun Oleh: Kelompok 1**  
Zahra Mufida 187241087  
Florecita Zulfa Nasifa 187241088  
Farhan Zuso Putra Jaya 187241102  
M Farhan Yanuar A 187241082

**PROGRAM STUDI SISTEM INFORMASI**  
**FAKULTAS SAINS DAN TEKNOLOGI**  
**UNIVERSITAS AIRLANGGA**  
**SURABAYA**  
**2026**

# **DAFTAR ISI** {#daftar-isi}

[**DAFTAR ISI	1**](#daftar-isi)

[**DAFTAR TABEL	4**](#daftar-tabel)

[**DAFTAR GAMBAR	5**](#daftar-gambar)

[**BAB I**](#bab-i) [**PENDAHULUAN	1**](#pendahuluan)

[1.1 Tujuan	1](#heading=)

[1.2 Konvensi Dokumen	1](#heading=)

[1.3 Target Pembaca dan Saran Membaca	1](#heading=)

[1.4 Referensi	1](#heading=)

[**BAB II**](#bab-ii) [**DESKRIPSI SISTEM	1**](#deskripsi-sistem)

[2.1 Product Scope	2](#heading=)

[2.2 Product Perspective	2](#heading=)

[2.2.1 Konteks Sistem	2](#heading=)

[2.2.2 Sistem Eksternal dan Antarmuka Integrasi	3](#heading=)

[2.2.3 Alur Interaksi dengan Sistem Eksternal	4](#heading=)

[2.2.4 Asumsi dan Ketergantungan	5](#2.2.4-asumsi-dan-ketergantungan)

[2.3 Product Functions	5](#heading=)

[2.4 User Classes and Characteristics	5](#heading=)

[2.4.1 Pemohon / Masyarakat	5](#heading=)

[2.4.2 Verifikator / Petugas Dukcapil	6](#heading=)

[2.4.3 Pejabat Penanda Tangan	7](#heading=)

[2.5 Operating Environment	7](#heading=)

[2.6 Design and Implementation Constraints	9](#heading=)

[2.7 System Architecture / Environment Diagram	11](#heading=)

[**BAB III KEBUTUHAN ANTARMUKA EKSTERNAL	12**](#heading=)

[3.1 Antarmuka Pengguna	12](#heading=)

[3.2 Antarmuka Perangkat Keras	12](#heading=)

[3.3 Antarmuka Perangkat Lunak	12](#heading=)

[3.4 Antarmuka Komunikasi	13](#heading=)

[**BAB IV BUSINESS PROCESS MODEL AND NOTATION (BPMN)	15**](#heading=)

[4.1 As-Is Proses Penerimaan dan Validasi Awal Berkas Kematian	15](#heading=)

[4.2 To-Be Proses Penerimaan dan Validasi Awal Berkas Kematian	16](#heading=)

[4.3 As-Is Proses Verifikasi Silang, Pemetaan, dan Penerbitan	16](#heading=)

[4.4 To-Be Proses Verifikasi Silang, Pemetaan, dan Penerbitan	17](#heading=)

[**BAB V**](#heading=) **USE CASE	18**

[5.1 Use Case Diagram	18](#heading=)

[5.2 Use Case Specification	18](#heading=)

[5.2.1 Register	19](#heading=)

[5.2.2 Login	23](#heading=)

[5.2.3 Mengajukan Laporan Kematian	27](#heading=)

[5.2.4 Mengelola User Profile	29](#heading=)

[5.2.5 Mengelola Status Pengajuan	31](#heading=)

[5.2.6 Memverifikasi Berkas Pengajuan	33](#heading=)

[5.2.7 Merekonsiliasi Anomali Data	35](#heading=)

[5.2.8 Menandatangani Dokumen (TTE)	39](#heading=)

[**BAB VI USER STORY DAN USER STORY SCENARIO	44**](#heading=)

[6.1 Behat in Two Words	44](#heading=)

[6.2 Feature	44](#heading=)

[6.2.1 Login	44](#heading=)

[6.2.2 Register	47](#heading=)

[6.2.3 Mengajukan Laporan Kematian	50](#heading=)

[6.2.4 Mengelola User Profile	52](#heading=)

[6.2.5 Mengelola Status Pengajuan	54](#heading=)

[6.2.6 Memverifikasi Berkas Pengajuan	56](#heading=)

[6.2.7 Merekonsiliasi Anomali	57](#heading=)

[6.2.8 Menandatangani Dokumen (TTE)	58](#heading=)

[6.3 Use Example	62](#heading=)

[6.4 Web Apps (Mink)	63](#heading=)

[**BAB VII GUI DESIGN	65**](#heading=)

[7.1 Register & Login	65](#heading=)

[7.2 Mengajukan Laporan Kematian	67](#heading=)

[7.3 Mengelola Status Pengajuan	71](#heading=)

[7.4 Memverifikasi Berkas dan Merekonsiliasi Anomali	73](#heading=)

[7.5 Menandatangani Dokumen (TTE)	75](#heading=)

[7.6 Mengelola User Profile	80](#heading=)

[**BAB VIII DATABASE	81**](#heading=)

[8.1 Conceptual Data Model (CDM)	81](#heading=)

[8.2 Physical Data Model (PDM)	82](#heading=)

[8.3 Entitas dan Relasi	83](#heading=)

[**BAB IX ACTIVITY DIAGRAM	85**](#heading=)

[9.1 Login	85](#heading=)

[9.2 Registrasi	86](#heading=)

[9.3 Mengajukan Laporan	87](#heading=)

[9.4 Mengelola User Profile	88](#heading=)

[9.5 Mengelola Status Pengajuan	89](#heading=)

[9.6 Memverifikasi Berkas	90](#heading=)

[9.7 Merekonsiliasi Anomali	91](#heading=)

[9.8 Menandatangani Dokumen (TTE)	92](#heading=)

[**BAB X SEQUENCE DIAGRAM	93**](#heading=)

[10.1 Login	93](#heading=)

[10.2 Registrasi	94](#heading=)

[10.3 Mengajukan Laporan Kematian	95](#heading=)

[10.4 Mengelola User Profile	97](#heading=)

[10.5 Mengelola Status Pengajuan	99](#heading=)

[10.6 Memverifikasi Berkas Pengajuan	100](#heading=)

[10.7 Merekonsiliasi Anomali	102](#heading=)

[10.8 Menandatangani Dokumen (TTE)	104](#heading=)

[**BAB XI**](#heading=) **[CLASS DIAGRAM	106](#heading=)**

[**BAB XII**](#heading=) **[KEBUTUHAN NONFUNGSIONAL	108](#heading=)**

[12.1 Performance Requirements	108](#heading=)

[12.2 Security Requirements	113](#heading=)

[12.3 Software Quality Attributes	114](#heading=)

[**DAFTAR PUSTAKA	117**](#heading=)

[**LAMPIRAN	119**](#heading=)

# **DAFTAR TABEL** {#daftar-tabel}

[Tabel 2.1 Sistem Eksternal dan Antarmuka Integrasi SATUDUKA	3](#tabel-2.1-sistem-eksternal-dan-antarmuka-integrasi-satuduka)

[Tabel 5.1 Use Case Specification Register	19](#tabel-5.1-use-case-specification-register)

[Tabel 5.2 Use Case Specification Login	23](#tabel-5.2-use-case-specification-login)

[Tabel 5.3 Use Case Specification Mengajukan Laporan Kematian	27](#tabel-5.3-use-case-specification-mengajukan-laporan-kematian)

[Tabel 5.4 Use Case Specification Mengelola User Profile	29](#tabel-5.4-use-case-specification-mengelola-user-profile)

[Tabel 5.5 Use Case Specification Mengelola Status Pengajuan	31](#tabel-5.5-use-case-specification-mengelola-status-pengajuan)

[Tabel 5.6 Use Case Specification Memverifikasi Berkas Pengajuan	33](#tabel-5.6-use-case-specification-memverifikasi-berkas-pengajuan)

[Tabel 5.7 Use Case Specification Merekonsiliasi Anomali Data	35](#tabel-5.7-use-case-specification-merekonsiliasi-anomali-data)

[Tabel 5.8 Use Case Specification Menandatangani Dokumen (TTE)	39](#tabel-5.8-use-case-specification-menandatangani-dokumen-\(tte\))

[Tabel 6.1 User Story Proses Login	44](#tabel-6.1-user-story-proses-login)

[Tabel 6.2 User Story Proses Register	47](#tabel-6.2-user-story-proses-register)

[Tabel 6.3 User Story Proses Mengajukan Laporan Kematian	50](#tabel-6.3-user-story-proses-mengajukan-laporan-kematian)

[Tabel 6.4 User Story Proses Mengelola User Profile	52](#tabel-6.4-user-story-proses-mengelola-user-profile)

[Tabel 6.5 User Story Proses Mengelola Status Pengajuan	54](#tabel-6.5-user-story-proses-mengelola-status-pengajuan)

[Tabel 6.6 User Story Proses Memverifikasi Berkas Pengajuan	56](#tabel-6.6-user-story-proses-memverifikasi-berkas-pengajuan)

[Tabel 6.7 User Story Proses Merekonsiliasi Anomali	57](#tabel-6.7-user-story-proses-merekonsiliasi-anomali)

[Tabel 6.8 User Story Proses Menandatangani Dokumen (TTE)	58](#tabel-6.8-user-story-proses-menandatangani-dokumen-\(tte\))

[Tabel 6.9 Use Example	62](#tabel-6.9-use-example)

[Tabel 6.10 Daftar Akun Percobaan	62](#tabel-6.10-daftar-akun-percobaan)

[Tabel 6.11 Step Definition	63](#tabel-6.11-step-definition)

[Tabel 8.1 Tabel Entitas Basis Data	83](#tabel-8.1-tabel-entitas-basis-data)

[Tabel 8.2 Relasi Basis Data	83](#tabel-8.2-relasi-basis-data)

[Tabel 12.1 Asumsi Beban Kerja	108](#tabel-12.1-asumsi-beban-kerja)

[Tabel 12.2 Waktu Respon Sistem	109](#tabel-12.2-waktu-respon-sistem)

[Tabel 12.3 Kinerja Integrasi dengan Sistem Eksternal	110](#tabel-12.3-kinerja-integrasi-dengan-sistem-eksternal)

[Tabel 12.4 Kapasitas dan Beba	111](#tabel-12.4-kapasitas-dan-beba)

[Tabel 12.5 Target Waktu Proses Layanan	112](#tabel-12.5-target-waktu-proses-layanan)

[Tabel 12.6 Kebutuhan Keamanan	113](#tabel-12.6-kebutuhan-keamanan)

[Tabel 12.7 Kebutuhan Atribut Kualitas Perangkat Lunak	114](#tabel-12.7-kebutuhan-atribut-kualitas-perangkat-lunak)

# **DAFTAR GAMBAR** {#daftar-gambar}

[Gambar 2.1 System Architecture / Environment Diagram SATUDUKA	11](#gambar-2.1-system-architecture-/-environment-diagram-satuduka)

[Gambar 4.1 BPMN As-Is Proses Penerimaan dan Validasi Awal Berkas Kematian	15](#gambar-4.1-bpmn-as-is-proses-penerimaan-dan-validasi-awal-berkas-kematian)

[Gambar 4.2 BPMN To-Be Proses Penerimaan dan Validasi Awal Berkas Kematian	16](#gambar-4.2-bpmn-to-be-proses-penerimaan-dan-validasi-awal-berkas-kematian)

[Gambar 4.3 BPMN As-Is Proses Verifikasi Silang, Pemetaan, dan Penerbitan	16](#gambar-4.3-bpmn-as-is-proses-verifikasi-silang,-pemetaan,-dan-penerbitan)

[Gambar 4.4 BPMN To-Be Proses Verifikasi Silang, Pemetaan, dan Penerbitan	17](#gambar-4.4-bpmn-to-be-proses-verifikasi-silang,-pemetaan,-dan-penerbitan)

[Gambar 5.1 Use Case Diagram Digitalisasi Pencatatan dan Administrasi Kematian Kota Yogyakarta	18](#gambar-5.1-use-case-diagram-digitalisasi-pencatatan-dan-administrasi-kematian-kota-yogyakarta)

[Gambar 7.1 High \-Fidelity Register	65](#gambar-7.1-high--fidelity-register)

[Gambar 7.2 High \-Fidelity Login	66](#gambar-7.2-high--fidelity-login)

[Gambar 7.3 High-Fidelity Mengajukan Laporan Kematian: Data Pelapor	67](#gambar-7.3-high-fidelity-mengajukan-laporan-kematian:-data-pelapor)

[Gambar 7.4 High-Fidelity Mengajukan Laporan Kematian: Data Almarhumah	68](#gambar-7.4-high-fidelity-mengajukan-laporan-kematian:-data-almarhumah)

[Gambar 7.5 High-Fidelity Mengajukan Laporan Kematian: Unggah Berkas	69](#gambar-7.5-high-fidelity-mengajukan-laporan-kematian:-unggah-berkas)

[Gambar 7.6 High-Fidelity Mengajukan Laporan Kematian: Review	70](#gambar-7.6-high-fidelity-mengajukan-laporan-kematian:-review)

[Gambar 7.7 High \-Fidelity Mengelola Status Pengajuan	71](#gambar-7.7-high--fidelity-mengelola-status-pengajuan)

[Gambar 7.8 High \-Fidelity Mengelola Status Pengajuan	72](#gambar-7.8-high--fidelity-mengelola-status-pengajuan)

[Gambar 7.9 High-Fidelity Dashboard Verifikator	73](#gambar-7.9-high-fidelity-dashboard-verifikator)

[Gambar 7.10 High-Fidelity Mengelola Status Pengajuan	74](#gambar-7.10-high-fidelity-mengelola-status-pengajuan)

[Gambar 7.11 High-Fidelity Merekonsiliasi Anomali Dokumen: Bagian Kedua	74](#gambar-7.11-high-fidelity-merekonsiliasi-anomali-dokumen:-bagian-kedua)

[Gambar 7.12 High-Fidelity Menandatangani Dokumen (TTE): Daftar Berkas	75](#gambar-7.12-high-fidelity-menandatangani-dokumen-\(tte\):-daftar-berkas)

[Gambar 7.13 High-Fidelity Menandatangani Dokumen (TTE): Detail Berkas Menunggu	76](#gambar-7.13-high-fidelity-menandatangani-dokumen-\(tte\):-detail-berkas-menunggu)

[Gambar 7.14 High-Fidelity Menandatangani Dokumen (TTE): Daftar Berkas	77](#gambar-7.14-high-fidelity-menandatangani-dokumen-\(tte\):-daftar-berkas)

[Gambar 7.15 High-Fidelity Menandatangani Dokumen (TTE): Detail Berkas Selesai	78](#gambar-7.15-high-fidelity-menandatangani-dokumen-\(tte\):-detail-berkas-selesai)

[Gambar 7.16 High-Fidelity Menandatangani Dokumen (TTE): Detail Berkas Dikembalikan	79](#gambar-7.16-high-fidelity-menandatangani-dokumen-\(tte\):-detail-berkas-dikembalikan)

[Gambar 7.17 High-Fidelity Mengelola User Profile	80](#gambar-7.17-high-fidelity-mengelola-user-profile)

[Gambar 8.1 Conceptual Data Model (CDM) Digitalisasi Pencatatan dan Administrasi Kematian Kota Yogyakarta	81](#gambar-8.1-conceptual-data-model-\(cdm\)-digitalisasi-pencatatan-dan-administrasi-kematian-kota-yogyakarta)

[Gambar 8.2 Physical Data Model (PDM) Digitalisasi Pencatatan dan Administrasi Kematian Kota Yogyakarta	82](#gambar-8.2-physical-data-model-\(pdm\)-digitalisasi-pencatatan-dan-administrasi-kematian-kota-yogyakarta)

[Gambar 9.1 Activity Diagram Login	85](#gambar-9.1-activity-diagram-login)

[Gambar 9.2 Activity Diagram Registrasi	86](#gambar-9.2-activity-diagram-registrasi)

[Gambar 9.3 Activity Diagram Mengajukan Laporan	87](#gambar-9.3-activity-diagram-mengajukan-laporan)

[Gambar 9.4 Activity Diagram Mengelola User Profile	88](#gambar-9.4-activity-diagram-mengelola-user-profile)

[Gambar 9.5 Activity Diagram Mengelola Status Pengajuan	89](#gambar-9.5-activity-diagram-mengelola-status-pengajuan)

[Gambar 9.6 Activity Diagram Memverifikasi Berkas	90](#gambar-9.6-activity-diagram-memverifikasi-berkas)

[Gambar 9.7 Activity Diagram Merekonsiliasi Anomali	91](#gambar-9.7-activity-diagram-merekonsiliasi-anomali)

[Gambar 9.8 Activity Diagram Menandatangani Dokumen (TTE)	92](#gambar-9.8-activity-diagram-menandatangani-dokumen-\(tte\))

[Gambar 10.1 Sequence Diagram Login	93](#gambar-10.1-sequence-diagram-login)

[Gambar 10.2 Sequence Diagram Registrasi	94](#gambar-10.2-sequence-diagram-registrasi)

[Gambar 10.3 Sequence Diagram Mengajukan Laporan Kematian	95](#gambar-10.3-sequence-diagram-mengajukan-laporan-kematian)

[Gambar 10.4 Sequence Diagram Mengelola User Profile	97](#gambar-10.4-sequence-diagram-mengelola-user-profile)

[Gambar 10.5 Sequence Diagram Mengelola Status Pengajuan	99](#gambar-10.5-sequence-diagram-mengelola-status-pengajuan)

[Gambar 10.6 Sequence Diagram Memverifikasi Berkas Pengajuan	100](#gambar-10.6-sequence-diagram-memverifikasi-berkas-pengajuan)

[Gambar 10.7 Sequence Diagram Merekonsiliasi Anomali	102](#gambar-10.7-sequence-diagram-merekonsiliasi-anomali)

[Gambar 10.8 Sequence Diagram Menandatangani Dokumen (TTE)	104](#gambar-10.8-sequence-diagram-menandatangani-dokumen-\(tte\))

[Gambar 11.1 Class Diagram SATUDUKA	106](#gambar-11.1-class-diagram-satuduka)

# **BAB I**   {#bab-i}

# **PENDAHULUAN** {#pendahuluan}

## **1.1 Tujuan**

Dokumen Software Requirements Specification (SRS) ini menjelaskan secara rinci sistem SATUDUKA (Sistem Administrasi Terpadu Urusan Duka): tujuan dan fitur sistem, antarmuka, apa yang dikerjakan sistem, batasan yang harus dipenuhi, serta bagaimana sistem merespons stimulus dari pengguna. Dokumen ini dimaksudkan agar pengembang dan pengguna akhir memiliki pemahaman yang sama mengenai kebutuhan fungsional dan nonfungsional SATUDUKA.

## **1.2 Konvensi Dokumen**

Struktur dokumen ini mengacu pada format SRS berbasis standar IEEE (merujuk pada contoh SRS Implementing Nigeria’s Master Facility List). Artefak pemodelan berupa diagram UML dibuat dengan PlantUML, sedangkan skenario penerimaan ditulis dengan sintaks Gherkin. Bagian yang belum tersedia ditandai dengan teks abu-abu berformat \[Belum diisi\] dan dipertahankan sebagai template untuk dilengkapi.

## **1.3 Target Pembaca dan Saran Membaca**

* Pengembang: memahami spesifikasi kebutuhan teknis dan menerjemahkannya ke dalam desain serta implementasi sistem.  
* Penguji (tester): memahami seluruh fitur sistem dan respons sistem sebagai dasar pengujian, termasuk acceptance test pada Bab VI.  
* Pengguna akhir (Pemohon, Verifikator Dukcapil, Pejabat Penanda Tangan): memahami deskripsi umum sistem pada Bab II serta antarmuka pada Bab VII.  
* Dosen pengampu: menilai kelengkapan artefak SRS.

## **1.4 Referensi**

* MEASURE Evaluation. (2019). Implementing Nigeria’s Health Facility Registry: Software Requirements Specifications. Chapel Hill, NC, USA: MEASURE Evaluation, University of North Carolina. (digunakan sebagai acuan struktur dokumen).  
* Institute of Electrical and Electronics Engineers (IEEE). (1998). IEEE Recommended Practice for Software Requirements Specifications. New York, NY, USA: IEEE, Inc.  
* Daftar pustaka lengkap tersedia pada bagian Daftar Pustaka di akhir dokumen.

# **BAB II**   {#bab-ii}

# **DESKRIPSI SISTEM** {#deskripsi-sistem}

## **2.1 Product Scope**

SATUDUKA merupakan inovasi sistem informasi berbasis web yang dirancang untuk mendigitalisasi seluruh alur pelayanan administrasi kependudukan terkait pencatatan kematian di Kota Yogyakarta secara terpadu, transparan, dan efisien. Sistem ini mentransformasi proses pelayanan konvensional yang sebelumnya dilakukan secara manual dan tatap muka di kantor Disdukcapil menjadi layanan digital yang mencakup pengajuan laporan kematian secara daring oleh pemohon, verifikasi berkas dan pencocokan data master kependudukan oleh verifikator, hingga rekonsiliasi anomali dokumen.  
Melalui integrasi Tanda Tangan Elektronik (TTE) oleh pejabat berwenang, SATUDUKA memfasilitasi penerbitan akta kematian resmi secara cepat dan legal yang dapat diunduh langsung oleh masyarakat. Platform ini bertujuan mempercepat waktu pelayanan, memangkas beban administratif petugas, mengurangi risiko kerusakan berkas fisik, serta memudahkan pemantauan status pengajuan secara real-time oleh warga.

## **2.2 Product Perspective**

SATUDUKA adalah sistem baru berbasis web yang menggantikan proses manual penerimaan dan penerbitan dokumen kematian di Disdukcapil (lihat BPMN As-Is pada Bab IV). Berdasarkan model proses To-Be dan spesifikasi use case, sistem berinteraksi dengan tiga pihak pengguna (Pemohon, Verifikator Dukcapil, Pejabat Penanda Tangan) dan memerlukan akses ke: (1) basis data kependudukan (SIAK) untuk verifikasi silang data, (2) basis data Faskes/RS sebagai pembanding surat keterangan kematian, dan (3) layanan Tanda Tangan Elektronik (TTE) untuk pengesahan dokumen.

### ***2.2.1 Konteks Sistem***

SATUDUKA berdiri sebagai aplikasi web mandiri yang dioperasikan oleh Disdukcapil Kota Yogyakarta. Sistem ini tidak menggantikan SIAK: SIAK tetap menjadi sumber data master kependudukan, sedangkan SATUDUKA mengelola data transaksi pengajuan, yaitu laporan kematian, berkas pendukung, hasil verifikasi, anomali dokumen, dan dokumen resmi (lihat Bab VIII). Pengguna mengakses sistem melalui peramban web tanpa harus datang ke kantor Disdukcapil, dan setiap kelas pengguna diarahkan ke dashboard sesuai perannya.  
Dibandingkan proses As-Is, pemeriksaan kelengkapan berkas, input ulang data, dan pencocokan data yang semula dilakukan manual oleh beberapa petugas (Bab IV, Gambar 4.1 dan 4.3) dialihkan ke sistem. Peran petugas bergeser menjadi pengambil keputusan atas kasus yang memerlukan penilaian manusia, seperti berkas yang ditolak, anomali data, dan pengesahan dokumen.

### ***2.2.2 Sistem Eksternal dan Antarmuka Integrasi***

SATUDUKA berinteraksi dengan tiga sistem eksternal sebagaimana diringkas pada Tabel 2.1 dan digambarkan pada Gambar 2.1. SATUDUKA bertindak sebagai pihak yang memanggil (client), sedangkan ketiga sistem tersebut menyediakan layanan.

#### Tabel 2.1 Sistem Eksternal dan Antarmuka Integrasi SATUDUKA {#tabel-2.1-sistem-eksternal-dan-antarmuka-integrasi-satuduka}

| Sistem Eksternal | Peran bagi SATUDUKA | Data yang Dipertukarkan | Arah dan Use Case Terkait |
| ----- | ----- | ----- | ----- |
| SIAK (basis data kependudukan) | Sumber data master untuk verifikasi silang data pelapor dan almarhum, serta tujuan pembaruan status setelah dokumen terbit. | Permintaan: NIK pelapor dan NIK almarhum. Respons: data identitas dan data keluarga yang tercatat. Setelah TTE: pembaruan status kematian pada data kependudukan. | Dua arah (baca untuk verifikasi, tulis status akhir). Memverifikasi Berkas, Merekonsiliasi Anomali, Menandatangani Dokumen. |
| Basis data Faskes/RS | Pembanding keabsahan surat keterangan kematian yang diunggah pemohon. | Permintaan: identitas almarhum dan identitas surat keterangan. Respons: data kematian yang tercatat di fasilitas kesehatan (nama, NIK, tanggal, tempat, dan sebab kematian). | Satu arah (baca). Memverifikasi Berkas, Merekonsiliasi Anomali. |
| Layanan TTE | Pengesahan dokumen resmi dengan tanda tangan elektronik pejabat. | Permintaan: draf dokumen, identitas pejabat, dan kredensial TTE (PIN/OTP). Respons: dokumen bertanda tangan dan status hasil penandatanganan. | Dua arah. Menandatangani Dokumen (TTE). |

Integrasi dengan ketiga sistem tersebut direncanakan memenuhi karakteristik berikut:

* Pola komunikasi: layanan web berbasis HTTPS dengan pertukaran data berformat JSON; dokumen untuk proses TTE dikirim sebagai berkas PDF.  
* Keamanan antarsistem: seluruh pertukaran data dienkripsi melalui TLS, autentikasi antarsistem memakai kunci atau token yang disimpan di sisi server, dan kredensial TTE pejabat tidak disimpan oleh SATUDUKA.  
* Minimisasi data: sistem hanya meminta atribut yang diperlukan untuk verifikasi, sejalan dengan prinsip pelindungan data pribadi (UU No. 27 Tahun 2022).  
* Ketahanan terhadap gangguan: bila layanan eksternal tidak merespons, sistem tidak menolak pengajuan secara otomatis, melainkan menandai berkas agar diperiksa ulang atau diverifikasi manual oleh verifikator, dan mencatat kegagalan tersebut ke log.  
* Keterlacakan: setiap pemanggilan layanan eksternal dicatat sebagai bagian dari audit trail (lihat NFR-06 pada Bab XII).

Spesifikasi teknis rinci, yaitu alamat endpoint, skema data, dan versi layanan, ditetapkan bersama pengelola SIAK, Faskes/RS, dan penyelenggara TTE pada tahap perancangan. Dokumen ini memuat kebutuhan antarmukanya pada Bab III bagian 3.3.

### ***2.2.3 Alur Interaksi dengan Sistem Eksternal***

Titik interaksi SATUDUKA dengan sistem eksternal mengikuti model proses To-Be (Bab IV, bagian 4.4) dan spesifikasi use case (Bab V):

* Verifikasi silang: setelah pengajuan berstatus "Menunggu Verifikasi", sistem mengambil data master dari SIAK berdasarkan NIK pelapor dan almarhum serta data pembanding dari Faskes/RS, lalu menampilkannya di halaman verifikasi untuk dicocokkan dengan isian formulir dan dokumen digital (Use Case Memverifikasi Berkas Pengajuan).  
* Rekonsiliasi anomali: bila ditemukan inkonsistensi, misalnya NIK ganda atau perbedaan identitas, berkas dialihkan ke daftar anomali dan data dari tiga sumber (input pelapor, SIAK, surat Faskes/RS) ditampilkan berdampingan untuk diputuskan verifikator (Use Case Merekonsiliasi Anomali Data).  
* Pengesahan dokumen: setelah berkas berstatus "Disetujui / Menunggu TTE", pejabat memasukkan kredensial TTE; sistem meneruskan dokumen ke layanan TTE. Bila berhasil, status dokumen menjadi SELESAI, status pada data kependudukan diperbarui, dan dokumen dapat diunduh pemohon. Tiga kegagalan kredensial berturut-turut mengunci akses TTE selama 15 menit (Use Case Menandatangani Dokumen dan Mengelola Status Pengajuan).

### ***2.2.4 Asumsi dan Ketergantungan*** {#2.2.4-asumsi-dan-ketergantungan}

* Disdukcapil Kota Yogyakarta memperoleh izin akses ke SIAK, basis data Faskes/RS, dan layanan TTE, serta ketiganya tersedia selama jam layanan.  
* Pejabat Penanda Tangan memiliki sertifikat elektronik yang masih berlaku untuk proses TTE.  
* Bila data suatu Faskes/RS belum tersedia secara elektronik, verifikator memeriksa surat keterangan kematian yang diunggah secara visual pada document viewer.  
* Pengguna memiliki perangkat dan koneksi internet yang memadai untuk mengakses aplikasi web dan mengunggah berkas.

## **2.3 Product Functions**

Fungsi utama SATUDUKA dijabarkan secara rinci pada spesifikasi use case (Bab V). Ringkasannya sebagai berikut:

* Sistem memungkinkan calon pemohon melakukan registrasi akun (Register).  
* Sistem memungkinkan pengguna (Pemohon, Verifikator, Pejabat) masuk ke sistem dan diarahkan ke dashboard sesuai role (Login).  
* Sistem memungkinkan pemohon mengajukan laporan kematian beserta berkas pendukung, termasuk menyimpannya sebagai draf (Mengajukan Laporan Kematian).  
* Sistem memungkinkan pemohon melihat dan memperbarui data profil serta mengubah kata sandi (Mengelola User Profile).  
* Sistem memungkinkan pemohon memantau status pengajuan melalui lini masa dan mengunduh dokumen resmi setelah terbit (Mengelola Status Pengajuan).  
* Sistem memungkinkan verifikator memeriksa, menyetujui, menolak/meminta revisi, atau menandai anomali pada berkas pengajuan (Memverifikasi Berkas Pengajuan).  
* Sistem memungkinkan verifikator menelusuri dan menyelesaikan anomali data kependudukan (Merekonsiliasi Anomali Data).  
* Sistem memungkinkan pejabat menandatangani dokumen secara elektronik atau mengembalikannya dengan alasan (Menandatangani Dokumen/TTE).

## **2.4 User Classes and Characteristics**

### ***2.4.1 Pemohon / Masyarakat***

Warga yang mendaftar akun, mengajukan laporan kematian beserta berkas pendukung, mengelola profil, serta memantau status dan mengunduh dokumen resmi.  
**Tingkat Keahlian Teknis:** Awam hingga Menengah (*Low to Moderate*). Diharapkan memiliki pemahaman dasar dalam pengoperasian peramban web (*web browser*), pengisian formulir digital, serta proses pengunggahan dokumen (*file upload*).  
**Frekuensi Penggunaan:** Insidental / Berkelanjutan Singkat (*Ad-hoc* / *Low Frequency*). Digunakan hanya saat terjadi peristiwa kematian di keluarga/lingkungan terdekat hingga dokumen Akta Kematian resmi selesai diterbitkan.  
**Hak Akses Rinci:**

* Melakukan pendaftaran akun (*Register*) dan masuk ke sistem (*Login*).  
* Mengisi dan menyimpan draf serta mengirimkan Formulir Pengajuan Laporan Kematian (Data Pelapor, Data Almarhum/Almarhumah, dan Berkas Pendukung).  
* Mengelola data profil pengguna dan mengubah kata sandi.  
* Memantau lini masa status pengajuan (*Menunggu Verifikasi*, *Ditolak/Butuh Revisi*, *Disetujui/Menunggu TTE*, *Selesai*).  
* Mengunduh dan mencetak dokumen hasil penerbitan Akta Kematian berstatus *SELESAI* yang telah dibubuhi TTE\[cite: 8, 16.2\].

### ***2.4.2 Verifikator / Petugas Dukcapil***

Petugas Dukcapil yang memeriksa kelengkapan dan keabsahan berkas, melakukan verifikasi silang terhadap data kependudukan, serta merekonsiliasi anomali data.  
**Tingkat Keahlian Teknis:** Menengah hingga Mahir (*Moderate to High*). Terlatih dalam menggunakan aplikasi administrasi perkantoran, memahami struktur data kependudukan (SIAK), serta terbiasa melakukan pemrosesan data dan validasi dokumen digital.  
**Frekuensi Penggunaan:** Rutin Harian (*High Frequency*). Digunakan secara aktif sepanjang jam kerja operasional Dinas Kependudukan dan Pencatatan Sipil Kota Yogyakarta untuk mengelola antrean berkas masuk.  
**Hak Akses Rinci:**

* Akses masuk (*Login*) ke Dasbor Verifikator menggunakan NIP.  
* Membuka dan memeriksa antrean berkas pengajuan berstatus *Menunggu Verifikasi*.  
* Melakukan pencocokan (*cross-check*) data formulir pemohon dengan dokumen pendukung digital dan data kependudukan SIAK/Faskes.  
* Menyetujui berkas pengajuan (mengubah status menjadi *Disetujui / Menunggu TTE*) untuk diteruskan ke Pejabat Penanda Tangan.  
* Menolak atau mengembalikan berkas pengajuan dengan memberikan catatan revisi/alasan penolakan kepada pemohon.  
* Menandai dan mengelola kasus ketidaksesuaian/inkonsistensi data melalui fungsi *Merekonsiliasi Anomali Data*.

### ***2.4.3 Pejabat Penanda Tangan***

Kepala Dinas atau pejabat berwenang Dukcapil Kota Yogyakarta yang membubuhkan Tanda Tangan Elektronik (TTE) pada dokumen yang telah disetujui atau mengembalikannya untuk diperbaiki.  
**Tingkat Keahlian Teknis:** Menengah (*Moderate*). Menguasai pengoperasian antarmuka web secara umum serta memahami prosedur keamanan Tanda Tangan Elektronik (TTE) terintegrasi BSrE/BSSN menggunakan PIN/OTP otorisasi.  
**Frekuensi Penggunaan:** Berkala Harian / Menyesuaikan (*Moderate Frequency*). Digunakan beberapa kali dalam sehari untuk meninjau dan mengesahkan dokumen yang telah disetujui oleh verifikator.  
**Hak Akses Rinci:**

* Akses masuk (*Login*) ke Dasbor Pejabat menggunakan NIP.  
* Melihat daftar berkas berstatus *MENUNGGU* TTE beserta ringkasan statistik dokumen (*Menunggu*, *Selesai*, *Dikembalikan*).  
* Melakukan pencarian dan penyaringan berkas berdasarkan Nomor Pengajuan, NIK, atau Status Dokumen.  
* Meninjau detail dan pratinjau lembar surat/dokumen yang akan ditandatangani.  
* Membubuhi Tanda Tangan Elektronik (TTE) resmi melalui input PIN/OTP sertifikat digital BSrE (mengubah status menjadi *SELESAI*.  
* Mengembalikan dokumen yang belum sesuai disertai pengisian alasan pengembalian (mengubah status menjadi *DIKEMBALIKAN*).

## **2.5 Operating Environment**

Website SATUDUKA yang akan dikembangkan mampu beroperasi sesuai dengan spesifikasi teknis yang mencakup aspek sistem operasi, jaringan, perangkat keras, framework, bahasa pemrograman, basis data, model arsitektur sistem, metode pengiriman data, serta integritas akses seperti pada tabel berikut.   
Tabel 2.1 Spesifikasi Operating Environment SATUDUKA 

| Penggunaan  | Jenis  |  |
| :---: | :---: | :---: |
| Sistem Operasi  | Semua sistem operasi yang telah terinstal browser  |  |
| Jaringan  | Koneksi internet yang stabil  |  |
| Perangkat Keras  | Laptop, PC, serta perangkat mobile  |  |
| Bahasa pengembangan  | **Nama**  | **Deskripsi**  |
|  | Laravel Blade  | Membangun antarmuka (UI) pengguna (frontend): halaman Registrasi, Login, Ajukan Laporan Kematian, Lacak Status Pengajuan, Verifikasi Berkas, Rekonsiliasi Anomali, Tanda Tangan Elektronik (TTE), dan Kelola Profil  |
|  | Laravel Framework (PHP)  | Membangun logika bisnis (backend): autentikasi pengguna, pengelolaan pengajuan laporan kematian, verifikasi berkas dan pencocokan data kependudukan, penerbitan dokumen resmi, serta akses basis data  |
| Basis Data  | PostgreSQL  | Basis data relasional untuk menyimpan data pengguna, laporan kematian, berkas pendukung, hasil verifikasi, anomali dokumen, dan dokumen resmi.  |
| Model arsitektur sistem  | *Model-View-Controller (MVC)*  |  |
| Metode pengiriman data  | **Metode**  | **Deskripsi**  |
|  | HTTP/HTTPS (request–response)  | Komunikasi antara browser pengguna (halaman Laravel Blade) dan backend Laravel, dengan halaman yang dirender di sisi server  |
|  | Multipart/form-data  | Pengunggahan berkas pendukung (Surat Keterangan Kematian, KTP Almarhum, KTP Pelapor, Kartu Keluarga) oleh Pemohon  |
|  | Integrasi data eksternal  | Backend memverifikasi NIK pada data kependudukan (SIAK) dan melakukan verifikasi silang dengan basis data Faskes/RS  |
|  | Unduhan file  | Pengiriman dokumen akta kematian resmi yang telah ditandatangani (TTE) kepada Pemohon  |
| Integritas akses  | **Role**  | **Hak Akses**  |
|  | Pemohon  | Registrasi dan login, mengajukan laporan kematian, mengunggah berkas pendukung, melacak status pengajuan, mengelola profil, dan mengunduh dokumen resmi  |
|  | Verifikator Dukcapil  | Memverifikasi berkas pengajuan, merekonsiliasi anomali data, mengelola status pengajuan, dan mengelola profil  |
|  | Pejabat Penanda Tangan  | Menandatangani dokumen secara elektronik (TTE) dan mengelola profil  |

## **2.6 Design and Implementation Constraints**

Batasan desain dan implementasi pada sistem SATUDUKA mencakup regulasi hukum, kewajiban keamanan, standar teknis, serta batasan integrasi sebagai berikut:   
**1\. Batasan Regulasi dan Perlindungan Data**

1. UU Pelindungan Data Pribadi (PDP): Pengelolaan data kependudukan, NIK, dan berkas kematian wajib mematuhi UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi. Sistem harus menerapkan prinsip minimisasi data (hanya meminta/mengolah atribut yang diperlukan untuk verifikasi) serta mengenkripsi data sensitif.  
2. Aturan Administrasi Kependudukan: Seluruh alur pencatatan dan penerbitan akta kematian harus tunduk pada regulasi administrasi kependudukan nasional (UU No. 24 Tahun 2013 tentang Perubahan atas UU No. 23 Tahun 2006\) dan petunjuk teknis Kementerian Dalam Negeri/Disdukcapil. 

**2\. Kewajiban Penggunaan TTE Tersertifikasi** 

1. Integrasi Penyelenggara TTE: Pengesahan dokumen resmi (Akta Kematian) wajib menggunakan Tanda Tangan Elektronik (TTE) tersertifikasi yang terintegrasi dengan layanan TTE / Balai Sertifikasi Elektronik (BSrE).   
2. Keamanan Kredensial TTE: Kredensial TTE milik Pejabat Penanda Tangan (seperti PIN/OTP) tidak boleh disimpan dalam basis data SATUDUKA dan hanya diteruskan langsung ke layanan TTE saat proses pengesahan dokumen.   
3. Mekanisme Penguncian Akses: Apabila terjadi kesalahan penginputan kredensial TTE sebanyak 3 kali berturut-turut, sistem wajib mengunci akses TTE selama 15 menit guna mencegah potensi penyalahgunaan. 

**3\. Batasan Integrasi dan Dependensi Sistem Eksternal** 

1. Ketergantungan API Eksternal: Sistem terikat pada ketersediaan antarmuka (API/Web Service) dari tiga sistem eksternal, yaitu SIAK (sumber data master kependudukan), basis data Faskes/RS (pembanding surat keterangan kematian), dan layanan TTE.   
2. Peran Sistem (Non-Replacement SIAK): SATUDUKA berdiri sebagai aplikasi web mandiri dan tidak menggantikan fungsi SIAK. SIAK tetap menjadi *single source of truth* data kependudukan, sedangkan SATUDUKA mengelola data transaksi permohonan, alur verifikasi, dan rekonsiliasi anomali.   
3. Protokol Komunikasi Aman: Pertukaran data antar-sistem wajib menggunakan protokol HTTPS (TLS) berbasis RESTful API dengan format data JSON, serta PDF untuk berkas dokumen TTE.   
4. Penanganan Gangguan (*Fallback Policy*): Jika layanan eksternal tidak merespons (*downtime*), sistem tidak boleh menolak permohonan secara otomatis. Berkas pengajuan harus ditandai untuk diverifikasi secara manual oleh verifikator dan kegagalan integrasi wajib dicatat ke dalam *log audit*. 

**4\. Standar Pengembangan dan Keterlacakan (Audit Trail)** 

1. Standar Spesifikasi & Pemodelan: Penyusunan dokumentasi dan pengembangan mengacu pada standar IEEE Std 830 / IEEE 29148, pemodelan diagram UML menggunakan PlantUML, serta skenario pengujian penerimaan (*acceptance testing*) menggunakan sintaks Gherkin.   
2. Audit Log & Keterlacakan: Setiap aktivitas pengguna (pengajuan, verifikasi, rekonsiliasi) dan riwayat pemanggilan layanan eksternal harus dicatat secara mutlak (*immutable*) dalam *audit log* (NFR-06) guna menjamin akuntabilitas dan transparansi proses. 

**5\. Batasan Operasional dan Antarmuka Pengguna** 

1. Aksesibilitas Peramban (*Cross-Browser*): Sistem berbasis web dan harus dapat diakses secara lancar melalui peramban modern (seperti Chrome, Firefox, Edge, Safari) tanpa memerlukan instalasi aplikasi khusus di sisi pengguna (*zero-client installation*).   
2. Konektivitas Pengguna: Pemohon diasumsikan memiliki perangkat (komputer/smartphone) dan koneksi internet yang memadai untuk mengakses formulir web dan mengunggah berkas pendukung. 

## **2.7 System Architecture / Environment Diagram**

Diagram berikut dimaksudkan untuk menggambarkan lingkungan sistem SATUDUKA beserta aktor dan sistem eksternal yang berinteraksi dengannya.

| *![][image1]* |
| :---: |

##### Gambar 2.1 System Architecture / Environment Diagram SATUDUKA {#gambar-2.1-system-architecture-/-environment-diagram-satuduka}

# 

# **BAB III**  

# **KEBUTUHAN ANTARMUKA EKSTERNAL**

## **3.1 Antarmuka Pengguna**

Rancangan antarmuka pengguna (high-fidelity) disajikan pada Bab VII (GUI Design).

## **3.2 Antarmuka Perangkat Keras**

SATUDUKA merupakan perangkat lunak berbasis web (*web application*) sehingga tidak memerlukan antarmuka perangkat keras khusus (*no dedicated hardware interface*). Namun, sistem berinteraksi dengan perangkat keras pendukung melalui peramban dan server: 

1. Sisi Klien (Pengguna)  
* Perangkat Masukan (Input): Papan ketik (*keyboard*), tetikus (*mouse*), atau layar sentuh (*touchscreen*) pada PC, Laptop, atau Smartphone untuk memasukkan data teks dan mengunggah berkas.   
* Perangkat Layar (Display): Monitor PC/Laptop atau layar Smartphone dengan resolusi minimal yang mampu menampilkan tata letak web secara adekuat.    
2. Sisi Server (Infrastruktur Hosting Disdukcapil):   
* Server aplikasi dan basis data berbasis arsitektur x86-64 yang terhubung ke jaringan lokal/internet Pemkot Yogyakarta untuk menjalankan lingkungan aplikasi Laravel dan basis data PostgreSQL. 

## **3.3 Antarmuka Perangkat Lunak**

SATUDUKA diharapkan berinteraksi dengan sistem eksternal berikut:

1. Basis data kependudukan (SIAK): verifikasi silang data pelapor dan almarhum.  
* Fungsi: Sumber data master untuk verifikasi silang data pelapor dan almarhum (berdasarkan NIK), serta tujuan pembaruan status kematian pada data kependudukan setelah dokumen terbit.  
* Protokol: RESTful API melalui HTTPS (Port 443).  
* Format Data: JSON (*JavaScript Object Notation*).  
* Mekanisme & Endpoint: Autentikasi menggunakan *API Key / Bearer Token* aman di sisi server Laravel (GET /api/v1/siak/verify untuk pembacaan data dan POST /api/v1/siak/update-status untuk pembaruan status).  
    
2. Basis data Faskes/RS: pembanding data surat keterangan kematian.  
* Fungsi: Pembanding keabsahan data Surat Keterangan Kematian yang diunggah pemohon (mencakup pencocokan nama, NIK, tanggal, tempat, dan sebab kematian).  
* Protokol: RESTful API melalui HTTPS (Port 443\) dengan akses satu arah (*read-only*).  
* Format Data: JSON.  
* Mekanisme & Endpoint: Autentikasi berbasis *API Key* (GET /api/v1/faskes/death-record).  
    
3. Layanan Tanda Tangan Elektronik (TTE): pembubuhan tanda tangan digital pada dokumen resmi.  
* Fungsi: Pembubuhan tanda tangan digital dan pengesahan dokumen resmi Akta Kematian oleh Pejabat Penanda Tangan.  
* Protokol: RESTful API melalui HTTPS (Port 443).  
* Format Data: JSON untuk pengiriman data kredensial (PIN/OTP) dan metadata, serta berkas PDF untuk dokumen yang ditandatangani.  
* Mekanisme & Endpoint: Transmisi dokumen draf PDF ke server layanan TTE dan penerimaan kembali berkas PDF yang telah terenkripsi/terandatandatangani (POST /api/v1/tte/sign-document).

## **3.4 Antarmuka Komunikasi**

Antarmuka komunikasi mengatur bagaimana data ditransmisikan antara peramban pengguna, server aplikasi SATUDUKA, dan sistem eksternal: 

1. Protokol Komunikasi Klien–Server:  
* HTTP/HTTPS (Port 80/443): Seluruh komunikasi antara peramban web pengguna (*Laravel Blade frontend*) dan *backend* Laravel menggunakan protokol enkripsi HTTPS (TLS 1.2/1.3) untuk menjamin kerahasiaan dan integritas data.  
2. Metode Transmisi Data Formulir & Berkas:   
* Form Request / JSON: Pengiriman data formulir pendaftaran, pengajuan, dan verifikasi menggunakan metode POST / PUT via HTTP/HTTPS.   
* Multipart/form-data: Digunakan khusus untuk mengunggah berkas pendukung digital (Surat Keterangan Kematian, KTP, KK) berformat PDF/JPG/PNG.   
3. Mekanisme Unduhan Dokumen:   
* Pengiriman berkas Akta Kematian resmi berformat PDF bertanda tangan digital kepada Pemohon dilakukan melalui mekanisme *stream / secure download* berbasis HTTPS.   
4. Protokol Komunikasi Antar-Sistem (Integrasi Eksternal):   
* Komunikasi dengan SIAK, Faskes/RS, dan Layanan TTE menggunakan RESTful API over HTTPS. Setiap pemanggilan API dicatat dalam *log audit* untuk keterlacakan sistem. 

# 

# **BAB IV**

# **BUSINESS PROCESS MODEL AND NOTATION (BPMN)**

BPMN (Business Process Model and Notation) adalah notasi grafis standar untuk memodelkan, menganalisis, dan mengomunikasikan proses bisnis secara visual. Tujuan utamanya adalah menyediakan notasi yang mudah dipahami pelaku bisnis namun tetap mampu merepresentasikan semantik proses yang kompleks bagi pelaksana teknis, sehingga menjembatani komunikasi antara analis bisnis, pengembang, dan pemangku kepentingan lainnya (Chinosi & Trombetta, 2012; White, 2004).

## **4.1 As-Is Proses Penerimaan dan Validasi Awal Berkas Kematian**

![BPMN As-Is Proses Penerimaan dan Validasi Awal Berkas Kematian][image2]

##### Gambar 4.1 BPMN As-Is Proses Penerimaan dan Validasi Awal Berkas Kematian {#gambar-4.1-bpmn-as-is-proses-penerimaan-dan-validasi-awal-berkas-kematian}

Diagram menggambarkan alur kerja antara Pelapor, Petugas Subbag/Desa, Kasubag/Sekdes, Pengelola Data, dan Kepala Disdukcapil. Pelapor menyerahkan berkas persyaratan, diperiksa kelengkapannya oleh petugas dan diverifikasi oleh Kasubag/Sekdes, kemudian Pengelola Data menginput dan memvalidasi data ke dalam database untuk dicetak sebagai draft akta, ditandatangani Kepala Disdukcapil, dan diserahkan kepada Pelapor.

## **4.2 To-Be Proses Penerimaan dan Validasi Awal Berkas Kematian**

![BPMN To-Be Proses Penerimaan dan Validasi Awal Berkas Kematian][image3]

##### Gambar 4.2 BPMN To-Be Proses Penerimaan dan Validasi Awal Berkas Kematian {#gambar-4.2-bpmn-to-be-proses-penerimaan-dan-validasi-awal-berkas-kematian}

Proses As-Is bersifat manual, reaktif, dan rentan human error, dengan petugas menghabiskan waktu pada pekerjaan repetitif. Pada To-Be, proses bersifat otomatis, proaktif, dan berbasis data: sistem membaca, memvalidasi logika, dan mengecek database, sedangkan petugas berperan sebagai pengambil keputusan untuk kasus yang membutuhkan penilaian manusia.

## **4.3 As-Is Proses Verifikasi Silang, Pemetaan, dan Penerbitan**

![][image4]

##### Gambar 4.3 BPMN As-Is Proses Verifikasi Silang, Pemetaan, dan Penerbitan {#gambar-4.3-bpmn-as-is-proses-verifikasi-silang,-pemetaan,-dan-penerbitan}

Diagram menguraikan alur kerja antara Pemohon/Masyarakat, Verifikator, Petugas Pencatatan, dan Pejabat Pencatatan Sipil: pengajuan berkas, verifikasi, input dan pengolahan oleh Petugas Pencatatan, validasi dan persetujuan Pejabat, hingga dokumen dicetak dan diserahkan kepada Pemohon.

## **4.4 To-Be Proses Verifikasi Silang, Pemetaan, dan Penerbitan**

![BPMN To-Be Proses Verifikasi Silang, Pemetaan, dan Penerbitan][image5]

##### Gambar 4.4 BPMN To-Be Proses Verifikasi Silang, Pemetaan, dan Penerbitan {#gambar-4.4-bpmn-to-be-proses-verifikasi-silang,-pemetaan,-dan-penerbitan}

Alur kerja dibagi ke dalam tiga lane: Sistem, Verifikator Dukcapil, dan Pemohon/Masyarakat. Data hasil validasi awal diverifikasi silang otomatis dengan basis data Faskes/RS; bila perlu peninjauan, verifikator merekonsiliasi anomali secara manual untuk memutuskan berkas diterima atau ditolak (dengan pencatatan log audit dan notifikasi ke warga). Berkas yang disetujui dipetakan metadatanya untuk pembuatan draf dokumen, disahkan pejabat melalui TTE, lalu sistem memperbarui status di database kependudukan dan mengirim dokumen resmi secara digital kepada pemohon.

# 

# **BAB V**  

# **USE CASE**

## **5.1 Use Case Diagram**

Use case diagram menggambarkan interaksi antara sistem dan aktor eksternal dalam mencapai tujuan tertentu, sehingga memberikan pandangan tingkat tinggi tentang fungsionalitas sistem dari sudut pandang pengguna (Microsoft, 2013a; Ambler, 2010).  
![Use Case Diagram Digitalisasi Pencatatan dan Administrasi Kematian Kota Yogyakarta][image6]

##### Gambar 5.1 Use Case Diagram Digitalisasi Pencatatan dan Administrasi Kematian Kota Yogyakarta {#gambar-5.1-use-case-diagram-digitalisasi-pencatatan-dan-administrasi-kematian-kota-yogyakarta}

Pemohon melakukan pendaftaran atau masuk ke sistem untuk mengajukan laporan kematian dengan mengunggah dokumen pendukung. Verifikator Dukcapil memverifikasi kelengkapan dan keabsahan berkas; apabila ditemukan anomali, berkas diarahkan ke proses rekonsiliasi anomali. Setelah berkas valid, Pejabat Penanda Tangan membubuhkan Tanda Tangan Elektronik hingga akta kematian diterbitkan. Sepanjang proses, Pemohon dapat memantau permohonan melalui Mengelola Status Pengajuan dan mengelola akun melalui Mengelola User Profile.

## **5.2 Use Case Specification**

Spesifikasi use case menjabarkan urutan interaksi antara aktor dan sistem, mencakup kondisi awal, alur normal, alur alternatif, dan kondisi akhir.

### ***5.2.1 Register***

#### Tabel 5.1 Use Case Specification Register {#tabel-5.1-use-case-specification-register}

| Nama Use Case | Register |  |
| :---- | ----- | ----- |
| **Aktor** | Pemohon |  |
| **Deskripsi** | Memungkinkan pengguna baru membuat akun dengan mengisi data diri dan kredensial login, yang kemudian diverifikasi oleh sistem sebelum akun dapat diaktifkan. |  |
| **Kondisi Awal** | Calon pemohon mengakses halaman registrasi. |  |
| **Kondisi Akhir** | Akun berhasil dibuat. |  |
| **Skenario Normal** | **Aktor** | **Sistem** |
|  | Membuka halaman Registrasi. |  |
|  |  | Menampilkan formulir registrasi. |
|  | Mengisi formulir (NIK, kata sandi, konfirmasi kata sandi). |  |
|  |  | Memeriksa kesesuaian kedua kolom kata sandi secara real-time. |
|  | Menekan tombol "Daftar". |  |
|  |  | Memvalidasi isian formulir dan memverifikasi NIK pada data kependudukan. |
|  |  | Menyimpan akun dan menampilkan notifikasi sukses: "Registrasi berhasil\!". |
|  |  | Menampilkan halaman Login. |
| **Skenario Alternatif** | **Aktor** | **Sistem** |
|  | 3.a.1 Menekan tautan "Sudah punya akun? Login" pada halaman registrasi. |  |
|  |  | 3.a.2 Mengarahkan pengguna ke halaman Login. |
|  | 5.b.1 Menekan ikon "tampilkan kata sandi" pada kolom kata sandi. |  |
|  |  | 5.b.2 Menampilkan isi kata sandi dalam bentuk teks biasa. |
|  | 5.b.3 Menekan tombol "Daftar". |  |
|  |  | 5.b.4 Memvalidasi isian formulir dan memverifikasi NIK pada data kependudukan (SIAK).  |
|  |  | 5.b.5 Menyimpan akun dan menampilkan notifikasi sukses: "Registrasi berhasil\!".  |
|  |  | 5.b.6 Mengarahkan pengguna ke halaman Login. |
| **Skenario Exceptional** | **Aktor** | **Sistem** |
|  | 3.a.1 Menekan tombol "Daftar" dengan kolom wajib yang masih kosong. |  |
|  |  | 3.a.2 Menampilkan pesan "Harap isi semua field yang diperlukan" dan menandai kolom yang kosong. |
|  | 5.b.2 Menekan tombol "Daftar". |  |
|  |  | 5.b.2 Menampilkan pesan kesalahan di bawah kolom NIK. |
|  | 5.c.1 Menekan tombol "Daftar". |  |
|  |   | 5.c.2 Menampilkan pesan "NIK sudah terdaftar". |
|  | 5.d.1 Menekan tombol "Daftar". |  |
|  |   | 5.d.2 Menampilkan pesan "NIK tidak ditemukan pada data kependudukan" |
|  | 3.b.1 Mengisi formulir (NIK dan kata sandi) |  |
|  |  | 3.b.2 Memeriksa kesesuaian kedua kolom kata sandi secara real-time. |
|  |  | 3.b.3 Menampilkan pesan di bawah kolom kata sandi: "Kata sandi minimal 8 karakter dan mengandung huruf serta angka". |
|  | 3.c.1 Mengisi formulir (NIK, kata sandi, konfirmasi kata sandi). |  |
|  |  | 3.c.2 Memeriksa kesesuaian kedua kolom kata sandi secara real-time. |
|  |  | 3.c.3 Menampilkan pesan "Kata sandi tidak cocok" di bawah kolom konfirmasi secara real-time dan tidak mengizinkan pendaftaran dilanjutkan. |

### ***5.2.2 Login***

#### Tabel 5.2 Use Case Specification Login {#tabel-5.2-use-case-specification-login}

| Nama Use Case | Login |  |
| :---- | ----- | ----- |
| **Aktor** | Aktor	Pemohon, Verifikator Dukcapil, Pejabat Penanda Tangan |  |
| **Deskripsi** | Deskripsi	Memungkinkan pengguna yang telah terdaftar masuk ke sistem menggunakan NIK (Pemohon) atau NIP (Verifikator Dukcapil dan Pejabat Penanda Tangan) beserta kata sandi, kemudian diarahkan ke dashboard sesuai perannya. |  |
| **Kondisi Awal** | Pengguna memiliki akun terdaftar dan berada di halaman Login. |  |
| **Kondisi Akhir** | Pengguna berhasil masuk dan diarahkan ke dashboard sesuai peran (Pemohon, Verifikator Dukcapil, atau Pejabat Penanda Tangan). |  |
| **Skenario Normal** | **Aktor** | **Sistem** |
|  | 1\. Membuka halaman Login. |   |
|  |  | 2\. Menampilkan formulir Login. |
|  | 3\. Menginputkan kredensial (NIK/NIP dan kata sandi). |  |
|  | 4\. Menekan tombol "Login". |   |
|  |   | 5\. Memvalidasi kredensial. |
|  |  | 6\. Mengarahkan pengguna ke dashboard sesuai peran. |
| **Skenario Alternatif** | **Aktor** | **Sistem** |
|  | 3.a.1 Menekan tautan "Belum punya akun? Daftar". |   |
|  |   | 3.a.2 Mengarahkan ke halaman Registrasi. |
|  | 4.a.1 Menekan tautan "Lupa Kata Sandi" pada halaman Login. |   |
|  |   | 4.a.2 Mengarahkan ke halaman reset kata sandi. |
|  |  | 4.a.3 Menampilkan formulir input NIK/NIP. |
|  | 4.a.4 Mengisi NIK/NIP yang terdaftar. |   |
|  | 4.a.5 menekan tombol "Kirim". |  |
|  |   | 4.a.6 Memverifikasi NIK/NIP. |
|  |  | 4.a.7 Menampilkan formulir kata sandi baru. |
|  | 4.a.8 Mengisi kata sandi baru. |   |
|  | 4.a.9 Mengisi konfirmasi kata sandi. |  |
|  | 4.a.10 Menekan tombol "Simpan". |  |
|  |   | 4.a.11 Menyimpan kata sandi baru |
|  |  | 4.a.12 Menampilkan notifikasi "Kata sandi berhasil direset. Silakan login." |
|  |  | 4.a.13 Mengarahkan ke halaman Login. |
|  | 4.b.1 Mencentang opsi "Ingat Saya". |   |
|  | 4.b.2 Menekan tombol "Login". |  |
|  |   | 4.b.3 Memvalidasi kredensial. |
|  |  | 4.b.4 Menyimpan sesi pengguna pada perangkat tersebut. |
|  |  | 4.b.5 Mengarahkan pengguna ke dashboard sesuai peran. |
|  | 4.c.1 Menekan ikon "tampilkan kata sandi" pada kolom kata sandi. |   |
|  |   | 4.c.2 Menampilkan isi kata sandi dalam bentuk teks biasa. |
|  | 4.c.3 Menekan tombol "Login". |   |
|  |   | 4.c.4 Memvalidasi kredensial. |
|  |  | 4.c.5 Mengarahkan pengguna ke dashboard sesuai peran. |
| **Skenario Exceptional** | **Aktor** | **Sistem** |
|  | 4.a.1 Menekan tombol "Login". |   |
|  |   | 4.a.2 Menampilkan pesan "Harap isi semua field yang diperlukan". |
|  |  | 4.a.3 Menandai kolom yang kosong. |
|  | 4.b.1 Menekan tombol "Login". |   |
|  |   | 4.b.2 Menampilkan pesan "NIK/NIP atau kata sandi salah". |
|  |  | 4.b.3 Menambah hitungan percobaan gagal. |
|  | 4.c.1 Menekan tombol "Login". |   |
|  |  | 4.c.2 Mengunci akun selama 15 menit, mencatat kejadian ke log keamanan |
|  |   | 4.c.3 Menampilkan pesan "Terlalu banyak percobaan gagal. Akun Anda dikunci sementara. Coba lagi dalam 15 menit”. |
|  |  | 4.c.4 Menambah hitungan percobaan gagal. |
|  | 4.c.5 Menekan tombol "Login". |   |
|  |  | 4.c.6 Menampilkan pesan "Akun Anda dikunci sementara”. |
|  | 4.d.1 Menginputkan kredensial (NIK/NIP dan kata sandi). |  |
|  | 4.d.2 Menekan tombol "Login". |  |
|  |  | 4.d.3 Menampilkan pesan "NIK/NIP tidak ditemukan".  |
|  | 4.d.1 Menginputkan kredensial (NIK/NIP dan kata sandi). |  |
|  | 4.d.2 Menekan tombol "Login". |  |
|  |  | 4.d.3 Menampilkan pesan "Kata sandi tidak cocok. |

### ***5.2.3 Mengajukan Laporan Kematian***

#### Tabel 5.3 Use Case Specification Mengajukan Laporan Kematian {#tabel-5.3-use-case-specification-mengajukan-laporan-kematian}

| Nama Use Case | Mengajukan Laporan Kematian |  |
| :---- | ----- | ----- |
| **Aktor** | Pemohon |  |
| **Deskripsi** | Memungkinkan pemohon mengisi data pelapor dan data almarhum, melampirkan berkas pendukung, lalu mengirimkan pengajuan laporan kematian ke sistem. |  |
| **Kondisi Awal** | Pemohon telah login ke sistem dan memiliki data diri serta data almarhum yang valid. |  |
| **Kondisi Akhir** | Laporan tersimpan dengan status "Menunggu Verifikasi", sistem menerbitkan nomor pengajuan unik, dan notifikasi konfirmasi terkirim ke pemohon. |  |
| **Skenario Normal** | **Aktor** | **Sistem** |
|  | 1\. Memilih menu "Ajukan Laporan Baru". |  |
|  |  | 2\. Menampilkan formulir pengajuan multi-langkah. |
|  | 3\. Mengisi data pelapor (nama, NIK, hubungan dengan almarhum, kontak). |  |
|  | 4\. Mengisi data almarhum (nama, NIK, tanggal dan tempat kematian, sebab kematian). |  |
|  |  | 5\. Menjalankan include "Mengunggah Berkas Pendukung". |
|  |  | 6\. Menampilkan ringkasan data untuk dikonfirmasi pemohon. |
|  | 7\. Menekan tombol "Kirim Pengajuan". |  |
|  |  | 8\. Memvalidasi kelengkapan data dan berkas. |
|  |  | 9\. Menyimpan pengajuan, membuat nomor pengajuan unik, dan mengubah status menjadi "Menunggu Verifikasi". |
|  |  | 10\. Mengirimkan notifikasi konfirmasi pengajuan kepada pemohon. |
| **Skenario Alternatif** | **Aktor** | **Sistem** |
|  | 3a.1 Menekan "Simpan sebagai Draft" sebelum seluruh data lengkap. |  |
|  |  | 3a.2 Menyimpan pengajuan dengan status "Draft" sehingga dapat dilanjutkan di lain waktu. |
|  | 8a.1 Menekan "Kirim Pengajuan" dengan data/berkas tidak lengkap atau tidak valid. |  |
|  |  | 8a.2 Menampilkan pesan error dan menyorot field yang bermasalah. |
|  | 8a.3 Memperbaiki data, lalu menekan kembali "Kirim Pengajuan". |  |
|  |  | 8a.4 Memvalidasi ulang, menyimpan pengajuan, membuat nomor pengajuan unik, dan mengirim notifikasi konfirmasi. |

### ***5.2.4 Mengelola User Profile***

#### Tabel 5.4 Use Case Specification Mengelola User Profile {#tabel-5.4-use-case-specification-mengelola-user-profile}

| Nama Use Case | Mengelola User Profile |  |
| :---- | ----- | ----- |
| **Aktor** | Pemohon |  |
| **Deskripsi** | Memungkinkan pemohon melihat dan memperbarui data pribadi serta mengubah kata sandi akun. |  |
| **Kondisi Awal** | Pemohon telah login ke sistem dan berada pada halaman profil. |  |
| **Kondisi Akhir** | Data profil berhasil diperbarui dan tersimpan di database. |  |
| **Skenario Normal** | **Aktor** | **Sistem** |
|  | 1\. Memilih menu "Kelola Profil". |  |
|  |  | 2\. Menampilkan data profil pemohon saat ini (nama, NIK, email, telepon, alamat, foto). |
|  | 3\. Mengubah satu/beberapa data (nama, email, telepon, alamat, atau foto). |  |
|  | 4\. Menekan tombol "Simpan Perubahan". |  |
|  |  | 5\. Memvalidasi format data (mis. format email, nomor telepon). |
|  |  | 6\. Menyimpan perubahan ke database. |
|  |  | 7\. Menampilkan notifikasi sukses: "Profil berhasil diperbarui." |
| **Skenario Alternatif** | **Aktor** | **Sistem** |
|  | 4a.1 Menekan "Simpan Perubahan" dengan format email/telepon tidak valid. |  |
|  |  | 4a.2 Menampilkan pesan error di bawah field terkait. |
|  | 4a.3 Memperbaiki data, lalu menekan kembali "Simpan Perubahan". |  |
|  |  | 4a.4 Menampilkan notifikasi sukses. |
| **Skenario Exceptional**  | **Aktor** | **Sistem** |
|  | 4b.1. Menekan tombol *"Simpan Perubahan"*.  |   |
|  |   | 4b.2. Mendeteksi bahwa email/nomor telepon baru sudah terdaftar pada basis data akun lain.   |
|  |  | 4b.3. Menampilkan pesan kesalahan: *"Email / Nomor Telepon sudah digunakan oleh pengguna lain."*  |
|  |  |  4b.4. Membatalkan proses penyimpanan data profil.  |
|  |  4b.5. Mengganti data email/nomor telepon dengan nilai lain atau membatalkan perubahan.  |  |
|  | 3a.1. Mengunggah berkas foto profil baru.  |  |
|  |  | 3a.2. Memeriksa ukuran dan eksistensi ekstensi berkas.  |
|  |  | 3a.3. Menampilkan pesan kesalahan: *"Ukuran berkas melebihi batas (maks. 2MB) atau format tidak didukung (gunakan JPG/PNG)."*  |
|  |  |  3a.4. Menolak berkas dan mengembalikan foto profil ke status sebelum diubah.  |
|  |   | 3a.5. Memilih kembali berkas foto yang memenuhi spesifikasi sistem.  |
|  | 4c.1. Menekan tombol *"Simpan Perubahan"*.  |  |
|  |  |  4c.2. Mendeteksi bahwa sesi otentikasi pemohon telah habis/kadaluwarsa.  |
|  |  | 4c.3. Membatalkan proses pembaruan data.  |
|  |   | 4c.4. Menampilkan pesan peringatan: *"Sesi Anda telah berakhir. Silakan login kembali."*  |
|  |  | 4c.5. Mengarahkan (*redirect*) pemohon secara otomatis ke halaman Login.  |
|  | 4d.1. Menekan tombol *"Simpan Perubahan"*.  |  |
|  |  | 4d.2. Gagal menghubungi API server / database akibat gangguan jaringan.  |
|  |  | 4d.3. Menampilkan pesan kesalahan: *"Gagal terhubung ke server. Periksa koneksi internet Anda dan coba lagi."*  |
|  |  | 4d.4. Mempertahankan data perubahan di formulir agar tidak hilang.  |
|  | 4d.5. Menekan kembali tombol *"Simpan Perubahan"* setelah koneksi stabil.  |  |

### ***5.2.5 Mengelola Status Pengajuan***

#### Tabel 5.5 Use Case Specification Mengelola Status Pengajuan {#tabel-5.5-use-case-specification-mengelola-status-pengajuan}

| Nama Use Case | Mengelola Status Pengajuan |  |
| :---- | ----- | ----- |
| **Aktor** | Pemohon |  |
| **Deskripsi** | Memungkinkan pemohon memantau posisi/status pengajuan laporan kematian secara real-time, mulai dari pengajuan hingga penerbitan dokumen resmi, serta mengunduh dokumen tersebut. |  |
| **Kondisi Awal** | Pemohon sudah login dan memiliki nomor pengajuan yang valid (mis. "SDK-2026-00231"). |  |
| **Kondisi Akhir** | Status pengajuan terkini dan riwayat tahapan proses ditampilkan kepada pemohon, dan dokumen dapat diunduh. |  |
| **Skenario Normal** | **Aktor** | **Sistem** |
|  | 1\. Memilih menu "Lacak Status Pengajuan". |  |
|  |  | 2\. Menampilkan daftar pengajuan milik pemohon atau kolom pencarian nomor pengajuan. |
|  | 3\. Memasukkan nomor pengajuan (mis. "SDK-2026-00231"). |  |
|  | 4\. Menekan tombol "Cari". |  |
|  |  | 5\. Mencari data pengajuan pada basis data berdasarkan nomor yang dimasukkan. |
|  |  | 6\. Menampilkan ringkasan pengajuan: nomor pengajuan, nama almarhum, tanggal pengajuan, dan badge status terkini. |
|  |  | 7\. Menampilkan lini masa vertikal lima tahap proses beserta tanggal/waktu tiap tahap yang telah selesai. |
|  |  | 8\. Setelah status "Diterbitkan/Selesai", mengaktifkan tombol "Unduh Dokumen". |
|  | 9\. Mengklik tombol "Unduh Dokumen". |  |
| **Skenario Alternatif** | **Aktor** | **Sistem** |
|  |  | 5a.1 Nomor pengajuan tidak ditemukan: menampilkan pesan "Data tidak ditemukan". |
|  | 5a.2 Mengetik ulang nomor pada kolom pencarian, lalu menekan "Cari". |  |
|  |  | 5a.3 Mencari data, lalu menampilkan ringkasan pengajuan dan lini masa lima tahap. |
|  |  | 5a.4 Setelah status "Diterbitkan/Selesai", mengaktifkan tombol "Unduh Dokumen". |

### ***5.2.6 Memverifikasi Berkas Pengajuan***

#### Tabel 5.6 Use Case Specification Memverifikasi Berkas Pengajuan {#tabel-5.6-use-case-specification-memverifikasi-berkas-pengajuan}

| Nama Use Case | Memverifikasi Berkas Pengajuan |  |
| :---- | ----- | ----- |
| **Aktor** | Verifikator / Petugas Dukcapil |  |
| **Deskripsi** | Memfasilitasi Verifikator Dukcapil memeriksa kelengkapan administrasi, memvalidasi berkas pendukung digital (Surat Kematian Faskes/RS, KTP Almarhum, KTP Pelapor, KK), melakukan verifikasi silang terhadap basis data kependudukan, serta memutuskan apakah berkas disetujui, ditolak, atau perlu perbaikan. |  |
| **Kondisi Awal** | Verifikator telah berhasil masuk (login) ke sistem SATUDUKA. |  |
| **Kondisi Akhir** | Sistem menampilkan notifikasi perubahan status kepada pemohon. |  |
| **Skenario Normal** | **Aktor** | **Sistem** |
|  | 1\. Membuka menu "Daftar Antrean Pengajuan" pada dasbor verifikator. |  |
|  |  | 2\. Menampilkan daftar permohonan kematian berstatus "Menunggu Verifikasi". |
|  | 3\. Memilih salah satu berkas pengajuan untuk diperiksa. |  |
|  |  | 4\. Menampilkan data lengkap pelapor, almarhum, serta dokumen pendukung pada document viewer. |
|  | 5\. Mencocokkan isian formulir dengan dokumen digital dan data kependudukan SIAK. |  |
|  | 6\. Menekan tombol "Setujui Berkas". |  |
|  |  | 7\. Memperbarui status menjadi "Disetujui / Menunggu TTE", meneruskan berkas ke antrean Pejabat Penanda Tangan, dan mencatat log verifikasi. |
| **Skenario Alternatif** | **Aktor** | **Sistem** |
|  | 6a. Memilih "Tolak / Butuh Revisi" dan menginput alasan penolakan atau catatan dokumen yang harus diperbaiki. |  |
|  |  | 7a. Memperbarui status menjadi "Ditolak / Butuh Revisi" dan mengirim notifikasi otomatis beserta catatan verifikator ke akun pemohon. |
|  | 5b. Jika ditemukan inkonsistensi data (mis. NIK ganda atau perbedaan identitas ekstrem), menandai berkas untuk diproses pada use case Merekonsiliasi Anomali. |  |
|  |  | 6b. Mengubah status berkas menjadi "Dalam Rekonsiliasi Anomali" dan memindahkannya ke daftar kerja anomali. |

### ***5.2.7 Merekonsiliasi Anomali Data***

#### Tabel 5.7 Use Case Specification Merekonsiliasi Anomali Data {#tabel-5.7-use-case-specification-merekonsiliasi-anomali-data}

| Nama Use Case | Merekonsiliasi Anomali Data |  |
| :---- | ----- | ----- |
| **Aktor** | Verifikator / Petugas Dukcapil |  |
| **Deskripsi** | Memungkinkan Verifikator menelusuri, menganalisis, dan menyelesaikan ketidaksesuaian/anomali data kependudukan sebelum berkas disahkan. |  |
| **Kondisi Awal** | Sistem mendeteksi inkonsistensi data secara otomatis saat verifikasi silang, atau berkas ditandai mengalami anomali oleh verifikator. |  |
| **Kondisi Akhir** | Anomali data berhasil diidentifikasi solusinya, data diperbaiki/disinkronkan sesuai bukti otentik, dan status anomali dinyatakan "Resolved". |  |
| **Skenario Normal** | **Aktor** | **Sistem** |
|  | 1\. Membuka menu "Rekonsiliasi Anomali Data". |  |
|  |  | 2\. Menyajikan daftar berkas yang mengalami anomali beserta parameter pemicu (flag ketidakcocokan). |
|  | 3\. Memilih kasus anomali yang akan ditangani. |  |
|  |  | 4\. Menampilkan komparasi data berdampingan antara data input pelapor, data master SIAK, dan surat keterangan faskes. |
|  | 5\. Menelusuri data induk dan menentukan data acuan yang sah berdasarkan bukti pendukung resmi. |  |
|  | 6\. Memilih opsi "Selesaikan Rekonsiliasi", memilih penyesuaian data yang valid, dan menginput berita acara/catatan rekonsiliasi. |  |
|  |  | 7\. Memperbarui catatan data, menandai status anomali "Terselesaikan (Resolved)", dan mengembalikan berkas ke antrean persetujuan verifikator. |
| **Skenario Alternatif** | **Aktor** | **Sistem** |
|  | 6a. Jika data fiktif atau sengketa identitas tidak dapat diverifikasi, memilih "Tolak Permanen & Eskalasi". |  |
|  |  | 7a. Mengunci berkas, memberi label "Ditolak \- Anomali Data Fatal", mencatat alasan eskalasi, dan mengirim notifikasi penolakan resmi kepada pemohon. |
| **Skenario Exceptional**  | **Aktor** | **Sistem** |
|  | 4a.1. Mengklik kasus anomali untuk melihat detail komparasi data.  |   |
|  |   | 4a.2. Gagal memuat data master SIAK dan menampilkan pesan kesalahan: *"Gagal terhubung ke Database Master SIAK. Silakan coba beberapa saat lagi."*  |
|  |  | 4a.3. Mengunci tombol tindakan penyelesaian dan mempertahankan status berkas dalam keadaan *"In Progress \- Pending System Response"*.  |
|  | 4a.4. Memilih opsi *"Muat Ulang Data"* atau menyegarkan halaman.  |   |
|  |   | 4a.5. Jika koneksi pulih, sistem menampilkan kembali halaman komparasi data secara utuh dan melanjutkan ke langkah 5 pada Skenario Normal.  |
|  | 5a.1. Membuka berkas lampiran pendukung resmi untuk menentukan data acuan.  |  |
|  |  |  5a.2. Menampilkan indikator kegagalan pratinjau file: *"Dokumen tidak dapat dibaca / file terkorupsi."*  |
|  |  5a.3. Memilih opsi *"Minta Unggah Ulang Dokumen Pendukung"*.  |  |
|  | 5a.4. Memasukkan catatan alasan penundaan (misal: "Dokumen Faskes tidak terbaca/blur"). |   |
|  |   | 5a.5. Mengubah status berkas menjadi *"Pending \- Menunggu Perbaikan Berkas Pelapor"*.  |
|  |  | 5a.6. Mengirim notifikasi otomatis ke akun pemohon untuk mengunggah ulang dokumen yang valid dalam rentang waktu tertentu.  |
|  | 6b.1. Memilih opsi *"Selesaikan Rekonsiliasi"* dan mengklik tombol *"Simpan Penyesuaian"*.  |   |
|  |  | 6b.2. Mendeteksi bahwa data master SIAK telah diperbarui oleh sesi lain selama proses analisis berlangsung.  |
|  |   | 6b.3. Membatalkan transaksi penyimpanan dan menampilkan pesan peringatan: *"Data master telah berubah saat proses verifikasi berlangsung."*  |
|  |  | 6b.4. Menampilkan dialog konfirmasi dengan opsi *"Muat Data Terbaru"* atau *"Batal"*.  |
|  | 6b.5. Mengklik *"Muat Data Terbaru"*.  |  |
|  |  | 6b.6. Mengembalikan halaman ke langkah 4 (Skenario Normal) dengan memuat nilai data master yang paling mutakhir.  |

### ***5.2.8 Menandatangani Dokumen (TTE)***

#### Tabel 5.8 Use Case Specification Menandatangani Dokumen (TTE) {#tabel-5.8-use-case-specification-menandatangani-dokumen-(tte)}

#### 

| Nama Use Case | Menandatangani Dokumen ( TTE) |  |
| :---- | ----- | ----- |
| Aktor | Pejabat Penanda Tangan (Kepala Dinas / Pejabat Berwenang Dukcapil Kota Yogyakarta) |  |
| Deskripsi | Memfasilitasi Pejabat Penanda Tangan untuk membubuhkan Tanda Tangan Elektronik pada dokumen yang telah disetujui, serta secara otomatis memperbarui status pengajuan. |  |
| Kondisi Awal | Pejabat Penanda Tangan telah login dan berada pada halaman "Tanda Tangan Dokumen". |  |
| Kondisi Akhir | Dokumen berhasil ditandatangani secara digital (TTE) dan berstatus SELESAI. |  |
| Skenario Normal | Aktor | Sistem |
|  | 1\. Menekan tombol "LIHAT" pada dokumen berstatus MENUNGGU. |   |
|  |   | 2\. Menampilkan halaman detail dokumen berisi nama pemohon, nomor dokumen, status MENUNGGU, pratinjau surat lengkap dengan Area Tanda Tangan, serta panel Informasi Dokumen (Nama Pemohon, Nomor Dokumen, Tanggal Dokumen). |
|  | 3\. Menekan tombol "Tanda Tangani". |   |
|  |   | 4\. Menampilkan dialog konfirmasi dan input PIN/OTP. |
|  | 5\. Memasukkan kredensial TTE (PIN/OTP) dan mengonfirmasi. |   |
|  |   | 6\. Memvalidasi kredensial TTE melalui layanan TTE. |
|  |  | 7\. Membubuhkan tanda tangan digital pada Area Tanda Tangan. |
|  |   | 8\. Mengubah status dokumen menjadi SELESAI dan memperbarui status pengajuan. |
|  |   | 9\. Menampilkan notifikasi sukses. |
|  |   | 10\. Mengembalikan pengguna ke halaman "Tanda Tangan Dokumen"; dokumen tampil berstatus SELESAI dengan tombol "DETAIL". |
| **Skenario Alternatif** | **Aktor** | **Sistem** |
|  | 3.a.1 Menekan tombol "Kembalikan". |   |
|  |   | 3.a.2 Menampilkan formulir alasan pengembalian. |
|  | 3.a.3 Mengisi alasan pengembalian, lalu mengonfirmasi. |   |
|  |   | 3.a.4 Mengubah status dokumen menjadi DIKEMBALIKAN. |
|  |  | 3.a.5 Mengirim notifikasi beserta alasan kepada Pemohon. |
|  |   | 3.a.6 Mengembalikan pengguna ke halaman "Tanda Tangan Dokumen"; dokumen tampil berstatus DIKEMBALIKAN dan jumlah "Dikembalikan" bertambah. |
|  | 3.b.1 Menekan tautan "Kembali ke Daftar Dokumen" pada halaman detail dokumen. |   |
|  |   | 3.b.2 Menampilkan halaman "Tanda Tangan Dokumen"; status dokumen tetap MENUNGGU. |
|  | 1.a.1 Mengisi kolom "Cari nomor pengajuan atau NIK" dan/atau memilih status pada filter "Semua Status". |   |
|  | 1.a.2 Menekan tombol "Cari". |  |
|  |   | 1.a.3 Menampilkan daftar dokumen yang sesuai dengan kata kunci dan status. |
|  | 1.b.1 Menekan tombol "DETAIL" pada dokumen berstatus SELESAI atau DIKEMBALIKAN. |   |
|  |   | 1.b.2 Menampilkan halaman detail dokumen tanpa tombol "Kembalikan" dan "Tanda Tangani". |
|  | 5.a.1 Menutup dialog PIN/OTP. |   |
|  |   | 5.a2 Menutup dialog dan tetap menampilkan halaman detail dokumen berstatus MENUNGGU. |
| **Skenario Exceptional**  | **Aktor** | **Sistem** |
|  | 5.a.1 Memasukkan kredensial TTE (PIN/OTP) dan mengonfirmasi. |   |
|  |   | 5.a.2 Menampilkan pesan "Kredensial tidak valid. Silakan coba lagi." dan  |
|  |  | 5.a.3 Menambah hitungan percobaan gagal. |
|  | 5.b.1 Memasukkan kredensial TTE (PIN/OTP) dan mengonfirmasi. |   |
|  |   | 5.b.2 Mengunci akses TTE selama 15 menit, mencatat percobaan gagal ke log keamanan. |
|  |  | 5.b.3 Menampilkan pesan "Akses TTE dikunci selama 15 menit. Silakan coba lagi nanti." |
|  | 5.b.4 Menekan tombol "Tanda Tangani". |   |
|  |   | 5.b.5 Menampilkan pesan akses TTE terkunci dan tidak membuka dialog PIN/OTP. |
|  | 3.a.1 Menekan konfirmasi pengembalian dengan alasan yang masih kosong. |   |
|  |   | 3.a.2 Menampilkan pesan "Alasan pengembalian wajib diisi". |
|  |  | 3.a.3 Tidak mengubah status dokumen. |
|  | 5.c.1 Memasukkan kredensial TTE yang valid. |   |
|  | 5.c.2 Mengonfirmasi saat layanan TTE mengalami gangguan. |  |
|  |   | 5.c.3 Menampilkan pesan "Penandatanganan gagal. Silakan coba lagi nanti." |
|  |  | 5.c.4 Mempertahankan status dokumen MENUNGGU, dan mencatat kegagalan ke log. |

# 

# **BAB VI**  

# **USER STORY DAN USER STORY SCENARIO**

## **6.1 Behat in Two Words**

Behat mengotomasi acceptance testing untuk fitur-fitur SATUDUKA. Setiap pengujian ditulis dalam bahasa natural menggunakan sintaks Gherkin sehingga dapat dipahami oleh tim pengembang maupun pemangku kepentingan non-teknis seperti pegawai Dukcapil Kota Yogyakarta. Setiap Scenario terdiri atas tiga bagian utama: Context (Given) sebagai kondisi awal, Triggering Events (When) sebagai aksi aktor, dan Expected Result (Then) sebagai hasil yang diharapkan.

## **6.2 Feature**

### ***6.2.1 Login***

#### Tabel 6.1 User Story Proses Login {#tabel-6.1-user-story-proses-login}

#### 

| Login |
| ----- |
| **Feature:** Login **In order to** access the system with proper authentication and authorization **As a** Petugas (Verifikator Dukcapil and Pejabat) or Pemohon **I want to** login using my credentials |
| **Scenario:** Navigate to the Registration page from Login **Given** I see the "Login" page **When** I click the link "Belum punya akun? Daftar" **Then** I see the "Registrasi" page |
| **Scenario:** Successfully reset the password  **Given** I see the "Login" page **When** I click the link "Lupa Kata Sandi" **Then** I see the password reset page with the "NIK/NIP" field  **When** I input "1234567890123456" into the "NIK/NIP" field  **And** I press the "Kirim" button  **Then** I see the new password form  **When** I input "Baru@12345" into the "Kata Sandi Baru" field  **And** I input "Baru@12345" into the "Konfirmasi Kata Sandi" field  **And** I press the "Simpan" button  **Then** I see a notification "Kata sandi berhasil direset. Silakan login."  **And** I see the "Login" page  |
| **Scenario:** Successfully login with the Remember Me option  **Given** I see the "Login" page  **When** I input "1234567890123456" into the "NIK/NIP" field  **And** I input "Pemohon@123" into the "Kata Sandi" field  **And** I check the "Ingat Saya" option  **And** I press the "Login" button  **Then** I see the "Pemohon" dashboard  |
| **Scenario:** Show the password **Given** I see the "Login" page  **When** I input "1234567890123456" into the "NIK/NIP" field  **And** I input "Pemohon@123" into the "Kata Sandi" field  **And** I click the "tampilkan kata sandi" icon **Then** I see the password "Pemohon@123" as plain text |
| **Scenario:** Successfully login with valid Pemohon credentials **Given** I see the "Login" page **When** I input "1234567890123456" into the "NIK" field **And** I input "Pemohon@123" into the "Kata Sandi field **And** I press the "Masuk" button **Then** I am redirected to the dashboard pemohon |
| **Scenario:** Successfully login with valid Verifikator credentials **Given** I see the "Login" page **When** I input "9876543210987654" into the "NIK" field **And** I input "Verif@2024" into the "Kata Sandi field **And** I press the "Masuk" button **Then** I am redirected to the dashboard Verifikator |
| **Scenario:** Successfully login with valid Pejabat credentials **Given** I see the "Login" page **When** I input "1122334455667788" into the "NIK" field **And** I input "Pejabat@2024" into the "Kata Sandi field **And** I press the "Masuk" button **Then** I am redirected to the dashboard Pejabat |
| **Scenario:** Failed login due to incorrect Password **Given** I see the "Login" page **When** I input "1234567890123456" into the "NIK" field **And** I input "Pemohon@122" into the "Kata Sandi field **And** I press the "Masuk" button **Then** I see an error message "NIK/NIP atau kata sandi salah" |
| **Scenario:** Failed login due to invalid NIK/NIP **Given** I see the "Login" page **When** I input "123456789012" into the "NIK" field **And** I input "Pemohon@123" into the "Kata Sandi field **And** I press the "Masuk" button **Then** I see an error message "NIK/NIP atau kata sandi salah" |
| **Scenario:** Failed login due to empty Password **Given** I see the "Login" page **When** I input "1234567890123456" into the "NIK" field **And** I leave the "Kata Sandi field empty **And** I press the "Masuk" button **Then** I see an error message "Harap isi semua field yang diperlukan" **And** I see the empty field highlighted  |
| **Scenario:** Failed login due to empty NIK **Given** I see the "Login" page **When** I leave the "NIK" field empty **And** I input "Pemohon@123" into the "Kata Sandi field **And** I press the "Masuk" button **Then** I see an error message "Harap isi semua field yang diperlukan" **And** I see the empty field highlighted  |
| **Scenario:** Account is locked after too many failed attempts  **Given** I see the "Login" page  **And** I have reached the maximum number of failed login attempts  **When** I press the "Login" button  **Then** I see an error message "Terlalu banyak percobaan gagal. Akun Anda dikunci sementara. Coba lagi dalam 15 menit"  |
| **Scenario:** Failed login while the account is locked **Given** I see the "Login" page  **When** I input "1234567890123456" into the "NIK/NIP" field **And** I input "Pemohon@123" into the "Kata Sandi" field  **And** I press the "Login" button  **Then** I see an error message "Akun Anda dikunci sementara"  |

### 

### ***6.2.2 Register***

#### Tabel 6.2 User Story Proses Register {#tabel-6.2-user-story-proses-register}

#### 

| Register |
| ----- |
| **Feature:** Register **In order to** submit a request for death registration and administration As a calon pemohon (masyarakat) **I want to** register and create a new account |
| **Scenario:** Successfully register with valid NIK 1234567890123456 **Given** I see the "Registrasi" page  **When** I input "1234567890123456" into the "NIK" field  **And** I input "Pemohon@123" into the "Kata Sandi" field  **And** I input "Pemohon@123" into the "Konfirmasi Kata Sandi" field  **And** I press the "Daftar" button  **Then** I see a notification "Registrasi berhasil\!"  **And** I see the "Login" page  |
| **Scenario:** Navigate to the Login page from Registration **Given** I see the "Registrasi" page **When** I click the link "Sudah punya akun? Login" **Then** I see the "Login" page |
| **Scenario:** Show the password **Given** I see the "Registrasi" page **When** I input "1122334455667788" into the "NIK" field **And** I input "Pejabat@2024" into the "Kata Sandi" field **And** I input "Pejabat@2024" into the "Konfirmasi Kata Sandi" field **And** I click the "tampilkan kata sandi" icon **Then** I see the password "Pemohon@123" as plain text |
| **Scenario:** Registration fails due to incorrect password **Given** I see the "Registrasi" page **When** I input "1234567890123456" into the "NIK" field **And** I input "Pemohon@122" into the "Kata Sandi" field **And** I input "Pemohon@123" into the "Konfirmasi Kata Sandi" field **And** I press the "Daftar" button **Then** I see an error message "Kata sandi dan konfirmasi kata sandi tidak sama" |
| **Scenario:** Registration fails due to weak password  **Given** I see the "Registrasi" page **When** I input "1234567890123456" into the "NIK" field **And** I input "abc123" into the "Kata Sandi" field **And** I input "abc123" into the "Konfirmasi Kata Sandi" field **Then** I see an error message below the "Kata Sandi" field: "Kata sandi minimal 8 karakter dan mengandung huruf serta angka" |
| **Scenario:** Registration fails due to password confirmation mismatch **Given** I see the "Registrasi" page **When** I input "1234567890123456" into the "NIK" field **And** I input "Pemohon@122" into the "Kata Sandi" field  **And** I input "Pemohon@122" into the "Konfirmasi Kata Sandi" field  **Then** I see an error message "Kata sandi tidak cocok" below the "Konfirmasi Kata Sandi" field |
| **Scenario:** Registration fails due to invalid NIK format **Given** I see the "Registrasi" page **When** I input "123456789012" into the "NIK" field **And** I input "Pemohon@123" into the "Kata Sandi" field **And** I input "Pemohon@123" into the "Konfirmasi Kata Sandi" field **And** I press the "Daftar" button **Then** I see an error message “NIK tidak valid” below the "NIK" field |
| **Scenario:** Registration fails because NIK is already registered **Given** an account with NIK "123456789012" already exists **And** I see the "Registrasi" page **When** I input "123456789012" into the "NIK" field **And** I input "Pemohon@123" into the "Kata Sandi" field **And** I input "Pemohon@123" into the "Konfirmasi Kata Sandi" field **And** I press the "Daftar" button **Then** I see an error message “NIK Sudah Terdaftar” |
| **Scenario:** Registration fails because NIK is not found  **And** I see the "Registrasi" page **When** I input "000000000000" into the "NIK" field **And** I input "Pemohon@123" into the "Kata Sandi" field **And** I input "Pemohon@123" into the "Konfirmasi Kata Sandi" field **And** I press the "Daftar" button **Then** I see an error message “NIK tidak ditemukan pada data kependudukan” |
| **Scenario:** Registration fails due to empty Password **Given** I see the "Registrasi" page **When** I input "1234567890123456" into the "NIK" field **And** I leave the "Kata Sandi" field empty **And** I leave the "Konfirmasi Kata Sandi" field empty **And** I press the "Daftar" button **Then** I see an error message "Harap isi semua field yang diperlukan" **And** I see the empty fields highlighted  |
| **Scenario:** Registration fails due to empty NIK **Given** I see the "Registrasi" page **When** I leave the "NIK" field empty **And** I input "Pemohon@123" into the "Kata Sandi" field **And** I input "Pemohon@123" into the "Konfirmasi Kata Sandi" field **And** I press the "Daftar" button **Then** I see an error message "Harap isi semua field yang diperlukan" **And** I see the empty field highlighted  |
| **Scenario:** Navigate to the Login page from Registration **Given** I see the "Registrasi" page **When** I click the link "Sudah punya akun? Masuk di sini" **Then** I am redirected to the "Login" page |

### 

### ***6.2.3 Mengajukan Laporan Kematian***

#### Tabel 6.3 User Story Proses Mengajukan Laporan Kematian {#tabel-6.3-user-story-proses-mengajukan-laporan-kematian}

| Mengajukan Laporan Kematian |
| ----- |
| **Feature:** Mengajukan Laporan Kematian In order to melaporkan kematian anggota keluarga secara resmi dan sah As a Pemohon/Masyarakat I want to mengajukan laporan kematian beserta dokumen pendukung secara online |
| **Scenario:** Berhasil mengajukan laporan kematian dengan data lengkap **Given** I am logged in as "Pemohon" **And** I am on the "Ajukan Laporan Baru" page **When** I fill in data pelapor (nama, NIK, hubungan dengan almarhum, kontak) **And** I fill in data almarhum (nama, NIK, tanggal \&amp; tempat kematian, sebab kematian) **And** I upload seluruh berkas pendukung yang wajib (Surat Keterangan Kematian, KTP Almarhum, KTP Pelapor, Kartu Keluarga) **And** I review the ringkasan data pengajuan **And** I press the "Kirim Pengajuan" button **Then** I see a notification "Pengajuan berhasil dikirim\!" **And** I see a unique generated nomor pengajuan **And** I see updates the status to "Menunggu Verifikasi" **And** I see that system sends a confirmation notification to the pemohon |
| **Scenario:** Gagal mengajukan karena data tidak lengkap atau tidak valid **Given** I am on the “Ajukan Laporan Baru” page **When** I fill in the form with incomplete or invalid data **And** I press the "Kirim Pengajuan" button **Then** I see an error message and highlights the problematic fields **When** I correct the invalid data **And** I press the "Kirim Pengajuan" button again **Then** I see a notification “Pengajuan berhasil dikirim” |
| **Scenario:** Menyimpan pengajuan sebagai draft **Given** I am on the "Ajukan Laporan Baru" page **When** I fill in sebagian data pelapor dan data almarhum **And** I press the "Simpan Draft" button **Then** I see system saves the pengajuan with status “Draft” **And** I can resume filling the form at a later time |
| **Scenario:** Gagal mengunggah berkas karena format tidak sesuai **Given** I am on the "Unggah Berkas Pendukung" step **When** I upload a file with an unsupported format or size exceeding the limit **Then** I see an error message "Format file tidak didukung / ukuran file melebihi batas" **When** I upload the file in the correct format and size **Then** I see marked document Terunggah |

### 

### ***6.2.4 Mengelola User Profile***

#### Tabel 6.4 User Story Proses Mengelola User Profile {#tabel-6.4-user-story-proses-mengelola-user-profile}

| Mengelola User Profile |
| ----- |
| **Feature**: Mengelola Kata Sandi In order to menjaga keamanan akun saya As a Pengguna I want to mengubah kata sandi akun saya |
| **Scenario**: Berhasil mengubah kata sandi **Given** I am on the "Kelola Profil" page **When** I open the "Ubah Kata Sandi" section **And** I input a valid "Kata Sandi Lama" **And** I input a valid "Kata Sandi Baru" **And** I input a valid "Konfirmasi Kata Sandi" **And** I press the "Simpan Perubahan" button **Then** I see  "Kata Sandi Lama" verified against the database **And** i see the new hashed password **And** i see a notification "Kata sandi berhasil diperbarui" |
| **Given** I am on the "Ubah Kata Sandi" section **When** I input an incorrect "Kata Sandi Lama" **And** I press the "Simpan Perubahan" button **Then** I see an error message "Kata sandi lama tidak sesuai" below the "Kata Sandi Lama" field **When** I re-enter the correct "Kata Sandi Lama" **And** I press the "Simpan Perubahan" button **Then** I see a notification "Kata sandi berhasil diperbarui" |
| **Scenario**: Gagal mengubah kata sandi karena "Kata Sandi Baru" tidak sesuai ketentuan **Given** I am on the "Ubah Kata Sandi" section **When** I input a "Kata Sandi Baru" with fewer than 8 characters **And** I press the "Simpan Perubahan" button **Then** I see an error message "Kata sandi minimal 8 karakter dan harus mengandung huruf serta angka" below the "Kata Sandi Baru" field **When** I fix the "Kata Sandi Baru" to contain at least 8 characters **And** I fix the "Kata Sandi Baru" to contain at least one letter **And** I fix the "Kata Sandi Baru" to contain at least one number **And** I input a matching "Konfirmasi Kata Sandi" **And** I press the "Simpan Perubahan" button **Then** I see a notification "Kata sandi berhasil diperbarui" |
| **Scenario**: Gagal mengubah kata sandi karena "Konfirmasi Kata Sandi" tidak cocok **Given** I am on the "Ubah Kata Sandi" section **When** I input a "Kata Sandi Baru" **And** I input a "Konfirmasi Kata Sandi" that does not match the "Kata Sandi Baru" **And** I press the "Simpan Perubahan" button **Then** I see an error message "Konfirmasi kata sandi tidak cocok" below the "Konfirmasi Kata Sandi" field **When** I re-enter a "Konfirmasi Kata Sandi" that matches the "Kata Sandi Baru" **And** I press the "Simpan Perubahan" button **Then** I see a notification "Kata sandi berhasil diperbarui" |

### 

### ***6.2.5 Mengelola Status Pengajuan***

#### Tabel 6.5 User Story Proses Mengelola Status Pengajuan {#tabel-6.5-user-story-proses-mengelola-status-pengajuan}

| Mengelola Status Pengajuan |
| ----- |
| **Feature:** Mengelola Status Pengajuan **In order to** monitor the progress of the death report I have submitted **As a** Pemohon/Masyarakat **I want to** track my submission status and download the official document |
| **Scenario:** Successfully see my list of pengajuan **Given** I am logged in as "Budi Santoso" (Pemohon Masyarakat) **And** I have a pengajuan with nomor "SDK-2026-00231" and status "Menunggu Verifikasi" **When** I am on the "/pengajuan/status" page **Then** I see my list of pengajuan along with their current status **And** I see pengajuan "SDK-2026-00231" with status "Menunggu Verifikasi" |
| **Scenario:** Successfully track a pengajuan by nomor pengajuan **Given** I am on the "Lacak Status Pengajuan" page **When** I input "SDK-2026-00231" into the "Nomor Pengajuan" field **And** I press the "Cari" button **Then** I see nomor pengajuan "SDK-2026-00231" **And** I see nama almarhum "Suparno Wijaya" **And** I see tanggal pengajuan "10 September 2026" **And** I see status terkini "Verifikasi Dokumen" **And** I see lini masa lima tahap proses beserta tanggal/waktu penyelesaian tiap tahap |
| **Scenario:** Successfully see the status of a pengajuan from my list **Given** I am on the "Lacak Status Pengajuan" page **And** I see my list of pengajuan **When** I select pengajuan "SDK-2026-00231" from the list **Then** I see the ringkasan pengajuan **And** I see lini masa lima tahap proses |
| **Scenario:** Failed to track a pengajuan because the nomor pengajuan is not found **Given** I am on the "Lacak Status Pengajuan" page **When** I input "SDK-2026-99999" into the "Nomor Pengajuan" field **And** I press the "Cari" button **Then** I see an error message "Data tidak ditemukan" **When** I input "SDK-2026-00231" into the "Nomor Pengajuan" field **And** I press the "Cari" button **Then** I see the status of pengajuan "SDK-2026-00231" |
| **Scenario:** Failed to track a pengajuan because the Nomor Pengajuan is empty **Given** I am on the "Lacak Status Pengajuan" page **When** I leave the "Nomor Pengajuan" field empty **And** I press the "Cari" button **Then** I see an error message "Harap isi nomor pengajuan" **And** I see the empty field highlighted |
| **Scenario:** Successfully download the official document after the pengajuan is completed **Given** I am viewing pengajuan "SDK-2026-00214" with status "Diterbitkan/Selesai" **And** I see the "Unduh Dokumen" button is active **When** I press the "Unduh Dokumen" button **Then** I receive the file "Akta\_Kematian\_SDK-2026-00214.pdf" on my device |
| **Scenario:** Unable to download the document before the pengajuan is completed **Given** I am viewing pengajuan "SDK-2026-00231" with status "Menunggu Verifikasi" **Then** I see the "Unduh Dokumen" button is disabled **And** I see the status terkini of my pengajuan |
| **Scenario:** See an empty list when I have no pengajuan **Given** I am logged in as "Dewi Lestari" (Pemohon Masyarakat) **And** I have no submitted pengajuan **When** I am on the "/pengajuan/status" page **Then** I see "Belum ada pengajuan yang diajukan" |

### ***6.2.6 Memverifikasi Berkas Pengajuan***

#### Tabel 6.6 User Story Proses Memverifikasi Berkas Pengajuan {#tabel-6.6-user-story-proses-memverifikasi-berkas-pengajuan}

#### 

| Memverifikasi Berkas Pengajuan |
| ----- |
| **Feature:** Memverifikasi Berkas Pengajuan In order to ensure the validity and completeness of death registration documents As a Verifikator Dukcapil I want to review, validate, and verify submitted death reports and supporting documents |
| **Scenario:** Successfully approve valid submission documents **Given** I am on the "Daftar Antrean Pengajuan" page **When** I select a submission with status "Menunggu Verifikasi" **And** I review the applicant data, deceased data, and all attached supporting documents **And** I verify that all documents are valid and match the population database **And** I press the "Setujui Berkas" button **Then** I see a notification "Berkas berhasil disetujui\!" **And** I see updates the submission status to "Disetujui / Menunggu TTE" **And** I see the submission forwards to the Pejabat Penanda Tangan queue |
| **Scenario:** Reject submission due to incomplete or invalid documents **Given** I am on the "Detail Verifikasi Berkas" page **When** I find illegible or missing supporting documents **And** I press the "Tolak / Butuh Revisi" button **And** I enter the reason for rejection in the revision notes field **And** I press the "Kirim Catatan Revisi" button **Then** I see a notification "Catatan revisi berhasil dikirim\!" **And** I see updates the submission status to "Ditolak / Butuh Revisi" **And** I see an automated revision notification to the applicant |
| **Scenario:** Flag submission due to suspected data discrepancy **Given** I am on the "Detail Verifikasi Berkas" page **When** I detect inconsistencies between the death certificate and the population database **And** I press the "Tandai Anomali" button **And** I select the anomaly category and add an internal note **And** I press the "Simpan ke Rekonsiliasi" button **Then** I see a notification "Berkas dialihkan ke daftar anomali\!" **And** I see the system updates the status to "Dalam Rekonsiliasi Anomali" **And** I can see the submission to the "Rekonsiliasi Anomali Data" list |

### ***6.2.7 Merekonsiliasi Anomali***

#### Tabel 6.7 User Story Proses Merekonsiliasi Anomali {#tabel-6.7-user-story-proses-merekonsiliasi-anomali}

#### 

| Merekonsiliasi Anomali |
| ----- |
| **Feature**: Merekonsiliasi Anomali In order to resolve data discrepancies and prevent flawed civil registration records As a Verifikator Dukcapil I want to compare, trace, and reconcile conflicting citizen death data |
| **Scenario**: Successfully reconcile and resolve data discrepancy **Given** I am on the "Rekonsiliasi Anomali Data" page **When** I select an anomaly case from the list **And** I review the submitted data on the comparison panel **And** I review the population master data on the comparison panel **And** I select the verified valid data reference based on authentic proof **And** I fill in the reconciliation note **And** I press the "Selesaikan Rekonsiliasi" button **And** I see a notification "Anomali berhasil diselesaikan\!" **And** I see the anomaly status updated to "Terselesaikan (Resolved)"  **And** I see the submission returned to the main verification approval queue  |
| **Scenario**: Reject submission permanently due to fatal data anomaly **Given** I am on the "Detail Rekonsiliasi Anomali" page **When** I confirm that the submitted identity data is fictitious or critically conflicting **And** I press the "Tolak Permanen & Eskalasi" button **And** I enter the detailed escalation reason **And** I enter the official note **And** I press the "Konfirmasi Penolakan Fatal" button **And** I see displays a notification "Pengajuan berhasil ditolak permanen\!" **And** I see the submission locked with status "Ditolak \- Anomali Data Fatal" **And** I see logs the escalation history for audit trail |

### ***6.2.8 Menandatangani Dokumen (TTE)***

#### Tabel 6.8 User Story Proses Menandatangani Dokumen (TTE) {#tabel-6.8-user-story-proses-menandatangani-dokumen-(tte)}

#### 

| Menandatangani Dokumen (TTE) |
| ----- |
| **Feature:** Menandatangani Dokumen (TTE) **In order to** affix an Electronic Signature to an approved document **As a** Signing Official (Head of Department/Authorized Officer of Dukcapil Yogyakarta City) **I want to** sign the document  |
| **Scenario:** Successfully sign document with TTE **Given** I have logged in as Pejabat Penanda Tangan  **And** I see the "Tanda Tangan Dokumen" page  **When** I press the "LIHAT" button on the document "SKD-2026-000124" with "MENUNGGU" status **Then** I see the document detail page with the applicant name, document number, and status "MENUNGGU"  **And** I see the letter preview with the signature area  **And** I see the document information panel with "Nama Pemohon", "Nomor Dokumen", and "Tanggal Dokumen"  **And** I see the "Kembalikan" and "Tanda Tangani" buttons  **When** I press the "Tanda Tangani" button  **Then** I see a confirmation dialog with a "PIN/OTP" field  **When** I input "123456" into the "PIN/OTP" field  **And** I confirm the signing action  **Then** I see the electronic signature in the signature area  **And** I see a success notification  **And** I see the "Tanda Tangan Dokumen" page  **And** I see the document "SKD-2026-000124" with status "SELESAI" and a "DETAIL" button  |
| **Scenario:** Return a document with a valid reason  **Given** I see the document detail page of a document with "MENUNGGU" status  **When** I press the "Kembalikan" button  **Then** I see the return reason form **When** I input "Tanggal tanda tangan perlu diganti menjadi hari ini" into the "Alasan Pengembalian" field  **And** I confirm the return  **Then** I see the "Tanda Tangan Dokumen" page  **And** I see the document with status "DIKEMBALIKAN"  **And** I see the "Dikembalikan" count increase  |
| **Scenario:** Go back to the document list without action **Given** I see the document detail page of a document with "MENUNGGU" status **When** I click the link "Kembali ke Daftar Dokumen" **Then** I see the "Tanda Tangan Dokumen" page **And** I see the document status remains "MENUNGGU" |
| **Scenario:** Search documents by application number **Given** I see the "Tanda Tangan Dokumen" page **When** I input "SKD-2026-000124" into the "Cari nomor pengajuan atau NIK" field **And** I press the "Cari" button **Then** I see only documents matching "SKD-2026-000124" |
| **Scenario:** Search documents by NIK **Given** I see the "Tanda Tangan Dokumen" page **When** I input "3471012345671234" into the "Cari nomor pengajuan atau NIK" field **And** I press the "Cari" button **Then** I see only documents matching "3471012345671234" |
| **Scenario:** Filter documents by status  **Given** I see the "Tanda Tangan Dokumen" page  **When** I select "MENUNGGU" on the "Semua Status" filter  **And** I press the "Cari" button  **Then** I see only documents with "MENUNGGU" status  **And** I see the "LIHAT" button on each document  |
| **Scenario:** Close the PIN/OTP dialog  **Given** I see the document detail page of a document with "MENUNGGU" status  **When** I press the "Tanda Tangani" button  **And** I close the PIN/OTP dialog  **Then** I see the document detail page  **And** I see the document status remains "MENUNGGU"  |
| **Scenario:** Failed to sign due to invalid TTE credentials **Given** I see the "Tanda Tangan Dokumen" page **When** I press the "LIHAT" button on a document with "MENUNGGU" status **And** I press the "Tanda Tangan" button **Then** I see confirmation dialog or TTE credential form **When** I confirm the signing action **And** I input "1122334455667788" into the "NIK" field **And** I input "Pejabat@2025" into the "Kata Sandi field **And** I submit the TTE credentials **Then** I see an error message "Kredensial tidak valid. Silahkan coba lagi." **And** I see the document status remains unchanged **And** I see no electronic signature affixed to the document |
| **Scenario:** Failed to return document without a reason **Given** I see the document detail page of a document with "MENUNGGU" status **When** I press the "Kembalikan" button **Then** I see rejection reason form **When** I leave the rejection reason empty **And** I submit the rejection form **Then** I see an error message "Alasan pengembalian wajib diisi" **And** I see the document status remains as "MENUNGGU" |
| **Scenario:** View completed document **Given** I see the "Tanda Tangan Dokumen" page **When** I press the "DETAIL" button on a document with "SELESAI" status **Then** I see the document detail page **And** I see the signed document **And** I see the electronic signature **And** I do not see the "Tanda Tangan" button |
| **Scenario:** View returned document **Given** I see the "Tanda Tangan Dokumen" page **When** I press the "DETAIL" button on a document with "DIKEMBALIKAN" status **Then** I see the document detail page **And** I see the document status as "DIKEMBALIKAN" **And** I see the rejection reason **And** I do not see the "Tanda Tangan" button |

## **6.3 Use Example**

Bagian ini memuat Scenario Outline pada fitur Login yang menguji kombinasi input NIK dan password menggunakan tabel Examples, sehingga satu scenario dapat dijalankan berulang dengan data berbeda.

#### Tabel 6.9 Use Example {#tabel-6.9-use-example}

| Feature: Login   In order to mengakses fitur-fitur sistem sesuai peran saya   As a Pemohon Masyarakat, Verifikator/Dukcapil, atau Pejabat Penanda Tangan   I want to melakukan login ke dalam sistem   Scenario Outline: Login dengan kombinasi username dan password     Given I am on the "/login" page     When I fill in "Username/NIK" with "\<username\>"     And I fill in "Password" with "\<password\>"     And I select "Login"     Then I should see "\<hasil\>"             | |
| :---- |

#### 

#### Tabel 6.10 Daftar Akun Percobaan {#tabel-6.10-daftar-akun-percobaan}

| No | NIK | Password | Hasil yang Diharapkan |
| ----- | ----- | ----- | ----- |
| 1 | 1234567890123456 | Pemohon@123 | Login berhasil |
| 2 | 9876543210987654 | Verif@2024 | Login berhasil |
| 3 | 1122334455667788 | Pejabat@2024 | Login berhasil |
| 4 | 1234567890123456 | Pemohon@122 | Username/NIK atau Password salah |
| 5 | 123456789012 | Pemohon@123 | Username/NIK atau Password salah |
| 6 | 1234567890123456 | (kosong) | Username/NIK atau Password salah |
| 7 | (kosong) | Pemohon@123 | Username/NIK atau Password salah |

## **6.4 Web Apps (Mink)**

Tabel berikut merangkum step Gherkin bergaya Mink yang digunakan pada seluruh user story di atas, beserta contoh penggunaannya.

#### Tabel 6.11 Step Definition {#tabel-6.11-step-definition}

| Category | Step | Example Usage |
| ----- | ----- | ----- |
| Navigation | Given I am on the "\<path\>" page | Given I am on the "/login" page |
| Navigation | Then I should remain on the "\<path\>" page | Then I should remain on the "/login" page |
| Navigation | Then I should be redirected to \<destination\> | Then I should be redirected to my role-based dashboard |
| Context/Precondition | Given I am logged in as "\<role\>" | Given I am logged in as "Verivikator/Dukcapil" |
| Context/Precondition | Given there is a pengajuan with status "\<status\>" | Given there is a pengajuan with status "Terverifikasi" |
| Context/Precondition | Given I have an existing pengajuan with status "\<status\>" | Given I have an existing pengajuan with status "Menunggu Verifikasi" |
| Form Interaction | When I fill in the \<name\> form with \<data\> | When I fill in the registration form with valid NIK, email, and password |
| Form Interaction | When I update field "\<field\>" with \<value\> | When I update field "Nomor Telepon" with a valid new value |
| Element Interaction | When I select "\<button/action\>" | When I select "Ajukan Laporan" |
| Element Interaction | When I open the pengajuan detail and select "\<action\>" | When I open the pengajuan detail and select "Verifikasi Berkas" |
| Content Verification | Then I should see "\<message\>" | Then I should see "Pengajuan laporan kematian berhasil dikirim" |
| Status/Data Verification | Then the pengajuan status should be updated to "\<status\>" | Then the pengajuan status should be updated to "Akta Diterbitkan" |
| System Behavior | Then the system should \<automated action\> | Then the system should validate the uploaded documents through the automated pipeline |
| System Behavior (Extend) | Then the system should extend the process to "\<use extend case\>" | Then the system should extend the process to "Merekonsiliasi Anomali Dokumen" |

# 

# **BAB VII**  

# **GUI DESIGN**

Prototipe low-fidelity merepresentasikan awal desain antarmuka dengan menyederhanakan detail visual untuk memvalidasi alur kerja, arsitektur informasi, dan konsep interaksi tingkat tinggi sebelum desain akhir ditetapkan.

## **7.1 Register & Login**

![][image7]

##### Gambar 7.1 High \-Fidelity Register {#gambar-7.1-high--fidelity-register}

Gambar 7.1 menampilkan antarmuka halaman registrasi akun pemohon pada aplikasi Sistem Kependudukan Digital yang berfungsi sebagai gerbang awal pendaftaran pengguna baru. Halaman ini menyediakan form input identitas utama yang mencakup field Nomor Induk Kependudukan (NIK) 16 digit, Kata Sandi, dan Konfirmasi Kata Sandi untuk validasi kompatibilitas kredensial. Sistem melengkapi antarmuka ini dengan mekanisme validasi format data secara *real-time*, tombol aksi utama "Daftar" untuk memproses pembuatan akun ke basis data, serta tautan navigasi ke halaman login bagi pengguna yang sudah memiliki akun terdaftar.   
![][image8]

##### Gambar 7.2 High \-Fidelity Login {#gambar-7.2-high--fidelity-login}

Gambar 7.2 menampilkan antarmuka halaman autentikasi pengguna (login) pada aplikasi Sistem Kependudukan Digital. Halaman ini dirancang untuk memverifikasi identitas pemohon melalui komponen masukan NIK dan Kata Sandi. Antarmuka dilengkapi dengan opsi *checkbox* "Ingat Saya" untuk persistensi sesi pengguna, tautan interaktif "Lupa Kata Sandi?" untuk memicu alur pemulihan akun, tombol eksekusi utama "Masuk" untuk melakukan verifikasi kredensial ke server, serta tautan balik menuju halaman registrasi untuk pendaftar baru. 

## **7.2 Mengajukan Laporan Kematian**

![][image9]

##### Gambar 7.3 High-Fidelity Mengajukan Laporan Kematian: Data Pelapor  {#gambar-7.3-high-fidelity-mengajukan-laporan-kematian:-data-pelapor}

Gambar 7.3 menampilkan antarmuka tahap pertama dari komponen *stepper* empat langkah (Data Pelapor, Data Almarhum, Unggah Berkas, Review) pada modul Pengajuan Laporan Kematian. Tahap ini berfokus pada pengumpulan identitas legal dan kontak aktif pihak pelapor. Elemen antarmuka mencakup kolom isian NIK pelapor, nama lengkap, nomor telepon/WhatsApp, alamat email, serta hubungan keluarga dengan almarhum. Indikator kemajuan pada *stepper* memberikan kejelasan posisi alur prosedur kepada pengguna sebelum melanjutkan ke tahap berikutnya melalui tombol navigasi "Lanjut".   
![][image10]

##### Gambar 7.4 High-Fidelity Mengajukan Laporan Kematian: Data Almarhumah {#gambar-7.4-high-fidelity-mengajukan-laporan-kematian:-data-almarhumah}

	Gambar 7.4 menampilkan antarmuka tahap kedua dari alur pengajuan laporan kematian yang diperuntukkan bagi perekaman data objek pelaporan. Halaman ini memuat formulir terstruktur untuk mencatat rincian peristiwa kematian almarhum/almarhumah, meliputi NIK almarhum, nama lengkap, jenis kelamin, tanggal dan waktu kematian, tempat kejadian (rumah sakit/kediaman/lainnya), serta penyebab kematian secara administratif. Keberadaan komponen penanggalan interaktif (*date picker*) dan menu *dropdown* memastikan konsistensi format data yang diinput oleh pengguna.   
![][image11]

##### Gambar 7.5 High-Fidelity Mengajukan Laporan Kematian: Unggah Berkas  {#gambar-7.5-high-fidelity-mengajukan-laporan-kematian:-unggah-berkas}

Halaman menampilkan antarmuka tahap pertama dari komponen *stepper* empat langkah (Data Pelapor, Data Almarhum, Unggah Berkas, Review) pada modul Pengajuan Laporan Kematian. Tahap ini berfokus pada pengumpulan identitas legal dan kontak aktif pihak pelapor. Elemen antarmuka mencakup kolom isian NIK pelapor, nama lengkap, nomor telepon/WhatsApp, alamat email, serta hubungan keluarga dengan almarhum. Indikator kemajuan pada *stepper* memberikan kejelasan posisi alur prosedur kepada pengguna sebelum melanjutkan ke tahap berikutnya melalui tombol navigasi "Lanjut".   
![][image12]

##### Gambar 7.6 High-Fidelity Mengajukan Laporan Kematian: Review {#gambar-7.6-high-fidelity-mengajukan-laporan-kematian:-review}

Halaman ini menampilkan antarmuka tahap kedua dari alur pengajuan laporan kematian yang diperuntukkan bagi perekaman data objek pelaporan. Halaman ini memuat formulir terstruktur untuk mencatat rincian peristiwa kematian almarhum/almarhumah, meliputi NIK almarhum, nama lengkap, jenis kelamin, tanggal dan waktu kematian, tempat kejadian (rumah sakit/kediaman/lainnya), serta penyebab kematian secara administratif. Keberadaan komponen penanggalan interaktif (*date picker*) dan menu *dropdown* memastikan konsistensi format data yang diinput oleh pengguna. 

## **7.3 Mengelola Status Pengajuan**

![][image13]

##### Gambar 7.7 High \-Fidelity Mengelola Status Pengajuan {#gambar-7.7-high--fidelity-mengelola-status-pengajuan}

Gambar 7.7 menampilkan antarmuka tahap ketiga berupa modul pengunggahan dokumen persyaratan administrasi kematian. Halaman ini menyediakan empat area unggah (*dropzone*) terpisah untuk berkas wajib, meliputi Surat Keterangan Kematian dari Rumah Hospital/Kelurahan, KTP Almarhum, KTP Pelapor, dan Kartu Keluarga. Setiap komponen dilengkapi dengan batasan sistem yang jelas (format yang didukung: PDF, JPG, PNG; ukuran maksimal 5 MB per berkas) serta *badge* status unggahan untuk mengindikasikan validitas, keberhasilan unggah, atau kegagalan transmisi berkas secara visual.   
![][image14]

##### Gambar 7.8 High \-Fidelity Mengelola Status Pengajuan {#gambar-7.8-high--fidelity-mengelola-status-pengajuan}

Gambar 7.8 berisi halaman “Lacak Status Pengajuan” ini menunjukkan transparansi proses administrasi Dukcapil, di mana pengajuan atas nama Almarhum Budi Santoso dengan nomor SDK-2026-00231 sedang diproses secara bertahap. Timeline memperlihatkan alur mulai dari pengajuan, verifikasi silang data, hingga tahap peninjauan verifikator yang masih berlangsung, sementara tahap persetujuan, tanda tangan elektronik, dan penerbitan dokumen masih menunggu. Tampilan ini memberi gambaran jelas bahwa sistem digital SIPD Dukcapil dirancang untuk memudahkan masyarakat memantau perkembangan dokumen secara real-time, sekaligus memastikan akuntabilitas setiap langkah birokrasi.Gambar 7.6 menampilkan antarmuka tahap akhir (Review) sebelum konfirmasi pengajuan dikirimkan ke sistem. Halaman ini menyajikan ringkasan komprehensif atas seluruh entri data yang dimasukkan pada tahap sebelumnya, mencakup data pelapor, data almarhum, dan daftar pratinjau dokumen pendukung yang telah diunggah. Di bagian bawah antarmuka, terdapat komponen checkbox pernyataan kebenaran data berpindat hukum yang wajib dicentang oleh pelapor, disusul tombol aksi final "Kirim Pengajuan" untuk mentransmisikan permohonan ke antrean verifikasi petugas. 

## **7.4 Memverifikasi Berkas dan Merekonsiliasi Anomali**

*![][image15]*

##### Gambar 7.9 High-Fidelity Dashboard Verifikator  {#gambar-7.9-high-fidelity-dashboard-verifikator}

Gambar 7.9 menampilkan antarmuka pelacakan dan pengelolaan status permohonan yang dapat diakses oleh pihak pemohon. Halaman ini menyediakan kolom pencarian (*search bar*) berbasis nomor tiket/pengajuan untuk mempermudah pemantauan berkas tertentu. Bagian utama antarmuka memuat kartu ringkasan informasi permohonan serta komponen lini masa (*timeline visual*) lima tahap yang menggambarkan kemajuan verifikasi secara *real-time* (Pengajuan Dikirim, Verifikasi Berkas, Rekonsiliasi, Tanda Tangan Elektronik, Selesai). 

![][image16]

##### Gambar 7.10 High-Fidelity Mengelola Status Pengajuan   {#gambar-7.10-high-fidelity-mengelola-status-pengajuan}

Gambar 7.10 menampilkan antarmuka rincian status pengajuan yang telah mencapai tahap akhir alur kerja. Halaman ini menampilkan kondisi sistem di mana seluruh tahapan verifikasi dan penandatanganan elektronik oleh pejabat berwenang telah berhasil dilewati dengan status "Selesai". Komponen aksi utama berupa tombol "Unduh Dokumen" (PDF) secara otomatis diaktifkan (*enabled*) oleh sistem, memungkinkan pemohon untuk mengunduh Kutipan Akta Kematian resmi yang telah dibubuhi QR Code sertifikat digital.   
*![][image17]*

##### Gambar 7.11 High-Fidelity Merekonsiliasi Anomali Dokumen: Bagian Kedua {#gambar-7.11-high-fidelity-merekonsiliasi-anomali-dokumen:-bagian-kedua}

Gambar 7.11 menampilkan antarmuka *Dashboard* Verifikator yang dirancang untuk memberikan gambaran umum operasional bagi petugas pemeriksa dokumen. Antarmuka ini didominasi oleh kartu metrik kuantitatif di bagian atas yang menyajikan angka statistik teraktual, meliputi Total Permohonan, Menunggu Verifikasi, Indikasi Anomali, dan Permohonan Selesai/Sah. Pada bagian bawah, terdapat tabel data permohonan terbaru yang dilengkapi fitur filter, status *badge*, dan tombol aksi cepat (*Quick Action*) untuk mempercepat navigasi pemeriksaan berkas. 

## **7.5 Menandatangani Dokumen (TTE)**

![][image18]

##### Gambar 7.12 High-Fidelity Menandatangani Dokumen (TTE): Daftar Berkas {#gambar-7.12-high-fidelity-menandatangani-dokumen-(tte):-daftar-berkas}

Gambar 7.12 menampilkan antarmuka antrean halaman Tanda Tangan Dokumen yang diakses oleh akun Pejabat (Fernanda Dwi) pada aplikasi Sistem Kependudukan Digital. Halaman ini menyajikan kartu statistik di bagian atas yang merangkum status dokumen, mencakup 2 dokumen Menunggu Tindakan (PERLU RESPON), 18 dokumen Selesai (TELAH TERBIT), dan 2 dokumen Dikembalikan (PERLU REVISI). Bagian bawah menampilkan tabel daftar pemohonan yang memuat nomor pengajuan, NIK ter-masking, nama pemohon, waktu pengajuan, status, serta tombol aksi untuk memproses dokumen seperti tombol LIHAT untuk dokumen berstatus MENUNGGU (misalnya atas nama Budi Santoso dan Siti Aminah) atau tombol DETAIL untuk dokumen yang berstatus SELESAI maupun DIKEMBALIKAN.  
![][image19]

##### Gambar 7.13 High-Fidelity Menandatangani Dokumen (TTE): Detail Berkas Menunggu {#gambar-7.13-high-fidelity-menandatangani-dokumen-(tte):-detail-berkas-menunggu}

Gambar 7.13 menampilkan antarmuka detail dokumen resmi berupa Kutipan Akta Kematian dengan status MENUNGGU pada aplikasi Sistem Kependudukan Digital. Lembar pratinjau dokumen memuat data lengkap peristiwa kematian almarhum di mana kolom tanda tangan elektronik (QR Code / Sertifikat Elektronik BSrE) di bawah nama Kepala Dinas Kependudukan dan Pencatatan Sipil masih kosong karena belum dibubuhi TTE. Pada bagian kanan bawah, tersedia dua opsi tombol aksi bagi Pejabat yang bertugas, yaitu Kembalikan jika berkas memerlukan perbaikan atau Tanda Tangani untuk menyetujui dan membubuhkan tanda tangan elektronik setelah memastikan seluruh data pemohonan telah sesuai.

![][image20]

##### Gambar 7.14 High-Fidelity Menandatangani Dokumen (TTE): Daftar Berkas {#gambar-7.14-high-fidelity-menandatangani-dokumen-(tte):-daftar-berkas}

Antarmuka modal high-fidelity Konfirmasi Tanda Tangan Elektronik (TTE) pada Gambar 7.14 berfungsi sebagai pintu keamanan dan pengesahan akhir dokumen resmi sebelum diterbitkan oleh sistem. Komponen ini menampilkan ringkasan data dokumen sasaran serta identitas pejabat penandatangan untuk memastikan validitas subjek sebelum diproses. Fungsi utamanya dijalankan melalui enam kolom input PIN Otorisasi TTE yang terintegrasi dengan enkripsi SHA-256 dan sertifikasi BSrE BSSN guna memverifikasi hak akses pejabat secara legal. Setelah PIN yang benar dimasukkan, tombol Konfirmasi & Tanda Tangani mengeksekusi pembubuhan sertifikat digital pada dokumen, sementara tombol Batal memberikan opsi untuk menghentikan proses pengesahan jika ditemukan kekeliruan.  
![][image21]

##### Gambar 7.15 High-Fidelity Menandatangani Dokumen (TTE): Detail Berkas Selesai {#gambar-7.15-high-fidelity-menandatangani-dokumen-(tte):-detail-berkas-selesai}

Gambar 7.15 menampilkan antarmuka detail dokumen resmi berupa Kutipan Akta Kematian dengan status telah selesai dan terverifikasi resmi pada aplikasi Sistem Kependudukan Digital. Lembar pratinjau dokumen memuat informasi peristiwa kematian almarhum serta mencantumkan keabsahan Tanda Tangan Elektronik (TTE Tersertifikasi). Keaslian sertifikat digital yang sah secara UU ITE ini dapat diverifikasi melalui pemindaian QR Code resmi, serta disertai dengan opsi tombol aksi Unduh Dokumen (PDF) dan Cetak Dokumen bagi pengguna.  
![][image22]

##### Gambar 7.16 High-Fidelity Menandatangani Dokumen (TTE): Detail Berkas Dikembalikan {#gambar-7.16-high-fidelity-menandatangani-dokumen-(tte):-detail-berkas-dikembalikan}

Gambar 7.16 menampilkan antarmuka detail dokumen resmi berupa Kutipan Akta Kematian atas nama Budi Santoso (Nomor Dokumen: SKD-2026-000124) dengan status DIKEMBALIKAN pada aplikasi Sistem Kependudukan Digital. Lembar pratinjau dokumen memuat informasi peristiwa kematian almarhum tetapi status verifikasi pada dokumen ditangguhkan ("BELUM DITANDATANGANI") karena dokumen dikembalikan untuk perbaikan persyaratan administrasi. Pada panel informasi sebelah kanan, tercantum alasan pengembalian dokumen secara spesifik, yaitu "Tanggal meninggal perlu diperbaiki", sehingga dokumen menunggu proses revisi lebih lanjut dari pihak pemohon sebelum dapat ditandatangani.

## **7.6 Mengelola User Profile**

![][image23]

##### Gambar 7.17 High-Fidelity Mengelola User Profile {#gambar-7.17-high-fidelity-mengelola-user-profile}

Gambar 7.17 menampilkan antarmuka halaman Kelola Profil Pemohon yang berfungsi sebagai pusat pengaturan informasi personal pengguna terdaftar. Antarmuka ini terbagi menjadi beberapa komponen utama: bagian manajemen foto profil dengan opsi unggah atau ubah foto, formulir data diri terintegrasi yang memuat entri nama lengkap, email, nomor telepon, dan nomor NIK (yang dikunci atau bersifat *read-only* guna menjaga integritas data kependudukan), serta bagian terpisah untuk Pengaturan Keamanan yang menyediakan alur "Ubah Kata Sandi" melalui verifikasi kata sandi lama dan konfirmasi kata sandi baru. 

# 

# **BAB VIII**  

# **DATABASE**

## **8.1 Conceptual Data Model (CDM)**

![][image24]

##### Gambar 8.1 Conceptual Data Model (CDM) Digitalisasi Pencatatan dan Administrasi Kematian Kota Yogyakarta {#gambar-8.1-conceptual-data-model-(cdm)-digitalisasi-pencatatan-dan-administrasi-kematian-kota-yogyakarta}

CDM pada Gambar 8.1 menggambarkan enam entitas konseptual: Pengguna (menampung tiga peran, yaitu Pemohon/Masyarakat, Verifikator/Dukcapil, dan Pejabat Penandatangan yang dibedakan melalui atribut Peran), Laporan Kematian sebagai entitas inti pengajuan, Hasil Verifikasi dan Anomali Dokumen sebagai hasil pemeriksaan, serta Berkas Pendukung dan Dokumen Resmi sebagai dokumen masukan dan keluaran. Pengguna mengajukan Laporan Kematian dengan melampirkan Berkas Pendukung; laporan diverifikasi hingga menghasilkan Hasil Verifikasi, yang bila menemukan ketidaksesuaian memicu Anomali Dokumen; laporan yang valid menghasilkan Dokumen Resmi yang ditandatangani Pengguna berperan Pejabat Penandatangan.

## **8.2 Physical Data Model (PDM)**

![][image25]

##### Gambar 8.2 Physical Data Model (PDM) Digitalisasi Pencatatan dan Administrasi Kematian Kota Yogyakarta {#gambar-8.2-physical-data-model-(pdm)-digitalisasi-pencatatan-dan-administrasi-kematian-kota-yogyakarta}

PDM pada Gambar 8.2 terdiri atas enam tabel fisik: pengguna, laporan\_kematian, berkas\_pendukung, hasil\_verifikasi, anomali\_dokumen, dan dokumen\_resmi. Tabel pengguna menjadi tabel induk bagi ketiga peran aktor melalui kolom peran bertipe ENUM('pemohon','verifikator','pejabat') dan direferensikan melalui id\_pemohon pada laporan\_kematian, id\_verifikator pada hasil\_verifikasi, dan id\_pejabat pada dokumen\_resmi. Tabel laporan\_kematian menjadi pusat relasi dan dirujuk oleh berkas\_pendukung, hasil\_verifikasi, dan dokumen\_resmi melalui id\_laporan, sedangkan anomali\_dokumen bergantung pada hasil\_verifikasi melalui id\_verifikasi.

## **8.3 Entitas dan Relasi**

#### Tabel 8.1 Tabel Entitas Basis Data {#tabel-8.1-tabel-entitas-basis-data}

| Entitas | Deskripsi | Atribut Utama |
| ----- | ----- | ----- |
| Pengguna | Aktor sistem (Pemohon, Verifikator, Pejabat), dibedakan lewat atribut Peran | NamaLengkap, NIK, Email, KataSandi, NomorTelepon, Peran |
| Laporan Kematian | Pengajuan laporan kematian oleh Pemohon | NomorPengajuan, NamaPelapor, HubunganDenganAlmarhum, NamaAlmarhum, NIKAlmarhum, TanggalKematian, TempatKematian, SebabKematian, StatusPengajuan, TanggalPengajuan |
| BerkasPendukung | Dokumen pendukung yang dilampirkan pada laporan | JenisDokumen, NamaFile, StatusUnggahan, TanggalUnggah |
| HasilVerifikasi | Hasil pemeriksaan berkas oleh Verifikator | StatusVerifikasi, CatatanVerifikator, TanggalVerifikasi |
| AnomaliDokumen | Ketidaksesuaian data yang ditemukan saat verifikasi | JenisAnomali, DeskripsiAnomali, StatusRekonsiliasi, TanggalRekonsiliasi |
| DokumenResmi | Akta kematian resmi hasil terbitan sistem | NomorDokumen, TanggalPenerbitan, StatusTandaTangan, FileDokumen |

#### Tabel 8.2 Relasi Basis Data {#tabel-8.2-relasi-basis-data}

| No | Nama Relasi | Entitas Asal | Entitas Tujuan | Kardinalitas | Penjelasan |
| ----- | ----- | ----- | ----- | ----- | ----- |
| 1 | mengajukan | Pengguna | laporan\_kematian | 1:N | Satu pengguna (pemohon) dapat mengajukan banyak laporan kematian |
| 2 | melampirkan | Laporan\_Kematian | berkas\_pendukung | 1:N | Satu laporan kematian dapat melampirkan banyak berkas pendukung |
| 3 | diverifikasi\_melalui | Laporan\_Kematian | hasil\_verifikasi | 1:N | Satu laporan kematian dapat memiliki banyak catatan hasil verifikasi |
| 4 | melakukan | Pengguna | hasil\_verifikasi | 1:N | Satu pengguna (verifikator) dapat melakukan banyak verifikasi |
| 5 | memicu | hasil\_verifikasi | anomali\_dokumen | 1:N | Satu hasil verifikasi dapat memicu banyak catatan anomali dokumen |
| 6 | menghasilkan | Laporan\_Kematian | dokumen\_resmi | 1:1 | Satu laporan kematian yang valid menghasilkan tepat satu dokumen resmi |
| 7 | menandatangani | pengguna | dokumen\_resmi | 1:N | Satu pengguna (pejabat) dapat menandatangani banyak dokumen resmi |

# 

# **BAB IX**  

# **ACTIVITY DIAGRAM**

Activity Diagram digunakan untuk memodelkan alur kerja antara aktor dan sistem, termasuk titik pengambilan keputusan (decision point) serta kondisi yang memengaruhi jalannya proses.

## **9.1 Login**

![Activity Diagram Login][image26]

##### Gambar 9.1 Activity Diagram Login {#gambar-9.1-activity-diagram-login}

Gambar 9.1 menunjukkan alur aktivitas login yang dapat dilakukan oleh tiga jenis pengguna, yaitu Pemohon, Verifikator Dukcapil, dan Pejabat. Pengguna memulai aktivitas dengan membuka halaman Login, memasukkan NIK dan Password, lalu menekan tombol "Masuk". Sistem terlebih dahulu memeriksa kelengkapan field; jika terdapat field yang kosong, sistem menampilkan pesan "Harap isi semua field yang diperlukan". Selanjutnya sistem memvalidasi kecocokan NIK dan Password; jika tidak sesuai, sistem menampilkan pesan "NIK atau Password salah". Apabila kredensial valid, sistem akan memeriksa role pengguna melalui percabangan (switch) dan mengarahkan pengguna ke dashboard yang sesuai dengan perannya masing-masing, yaitu dashboard Pemohon, dashboard Verifikator, atau dashboard Pejabat.

## **9.2 Registrasi**

![Activity Diagram Registrasi][image27]

##### Gambar 9.2 Activity Diagram Registrasi {#gambar-9.2-activity-diagram-registrasi}

Gambar 9.2 menggambarkan alur aktivitas registrasi akun oleh calon pemohon yang melibatkan dua aktor, yaitu Calon Pemohon dan Sistem. Aktivitas dimulai saat calon pemohon mengisi form registrasi berupa NIK, Kata Sandi, dan Konfirmasi Kata Sandi, kemudian menekan tombol "Daftar". Sistem selanjutnya melakukan tiga pengecekan secara berurutan, yaitu kelengkapan field, validitas format NIK sepanjang 16 digit, dan kesesuaian antara Kata Sandi dengan Konfirmasi Kata Sandi; apabila salah satu pengecekan gagal, sistem menampilkan pesan error yang sesuai dan mengembalikan pengguna untuk mengisi ulang form tersebut. Jika seluruh pengecekan berhasil dilalui, sistem akan menyimpan data akun baru, menampilkan notifikasi "Registrasi berhasil\!", dan mengarahkan calon pemohon menuju halaman Login.

## **9.3 Mengajukan Laporan**

![Activity Diagram Mengajukan Laporan][image28]

##### Gambar 9.3 Activity Diagram Mengajukan Laporan {#gambar-9.3-activity-diagram-mengajukan-laporan}

Gambar 9.3 Alur kerja pada diagram ini diawali oleh aktor Pemohon yang membuka halaman "Ajukan Laporan Baru", mengisi data pelapor serta data almarhum, dan mengunggah seluruh berkas persyaratan wajib ke dalam sistem. Setelah seluruh input dimasukkan, Sistem melakukan validasi awal terhadap kelengkapan data dan format berkas yang telah diunggah. Alur kemudian terbagi menjadi dua cabang sesuai dengan pilihan aksi yang diambil oleh Pemohon.  
Jika Pemohon memilih opsi untuk menyimpan draf, Pemohon menekan tombol "Simpan Draft". Sistem selanjutnya memproses tindakan tersebut dengan menyimpan data pengajuan ke dalam pangkalan data dengan status "Draft" dan menampilkan notifikasi bahwa draf telah berhasil disimpan. Sebaliknya, jika Pemohon memilih opsi untuk mengirim pengajuan, Pemohon terlebih dahulu meninjau ringkasan data pengajuan lalu menekan tombol "Kirim Pengajuan". Sistem kemudian akan menyimpan seluruh data laporan beserta berkas pendukungnya, mengirimkan notifikasi konfirmasi ke pemohon, dan menampilkan notifikasi bahwa pengajuan berhasil dikirim.

## **9.4 Mengelola User Profile**

![Activity Diagram Mengelola User Profile][image29]

##### Gambar 9.4 Activity Diagram Mengelola User Profile {#gambar-9.4-activity-diagram-mengelola-user-profile}

Gambar 9.4  menampilkan Activity Diagram untuk fitur Ubah Kata Sandi dengan dua swimlane, yaitu Pengguna dan Sistem. Proses dimulai dari Pengguna membuka halaman "Kelola Profil" dan section "Ubah Kata Sandi", dilanjutkan dengan pengisian tiga field input. Alur validasi digambarkan melalui tiga blok perulangan (repeat) yang berjalan berurutan: perulangan pertama memeriksa kesesuaian kata sandi lama, di mana Sistem akan menampilkan pesan error dan mengarahkan Pengguna untuk mengulang input apabila kata sandi lama tidak sesuai; perulangan kedua memeriksa apakah kata sandi baru memenuhi ketentuan minimal delapan karakter serta kombinasi huruf dan angka; dan perulangan ketiga memeriksa kecocokan antara konfirmasi kata sandi dengan kata sandi baru. Setiap perulangan hanya akan berlanjut ke tahap berikutnya apabila validasi bernilai "Ya" melalui penanda break, sedangkan nilai "Tidak" akan mengulang tahap input yang bersangkutan. Setelah ketiga validasi terpenuhi, proses diakhiri dengan Sistem menyimpan hashed password baru dan menampilkan notifikasi "Kata sandi berhasil diperbarui" sebelum mencapai node stop. 

## **9.5 Mengelola Status Pengajuan**

![][image30]

##### Gambar 9.5 Activity Diagram Mengelola Status Pengajuan {#gambar-9.5-activity-diagram-mengelola-status-pengajuan}

Gambar 9.5 menampilkan Activity Diagram untuk fitur Mengelola Status Pengajuan pada skenario Lacak dengan Nomor Pengajuan. Alur dimulai ketika Pemohon/Masyarakat membuka halaman "Lacak Status Pengajuan", lalu memasukkan nomor pengajuan dan menekan tombol "Cari". Bagian ini digambarkan menggunakan struktur repeat (perulangan), karena proses pencarian dapat berulang selama nomor pengajuan yang dimasukkan belum ditemukan di tabel laporan\_kematian  sistem akan menampilkan pesan "Data tidak ditemukan" dan mempersilakan pemohon memasukkan ulang nomor pengajuan hingga data ditemukan. Setelah nomor pengajuan valid, sistem mengambil ringkasan pengajuan (nomor pengajuan, nama almarhum, tanggal pengajuan, status terkini) serta lini masa lima tahap proses dari tabel hasil\_verifikasi, anomali\_dokumen, dan dokumen\_resmi, kemudian menampilkannya kepada pemohon.  
Selanjutnya terdapat decision point "Status pengajuan \= Diterbitkan/Selesai?" yang membagi alur menjadi dua kemungkinan. Apabila status pengajuan sudah selesai, pemohon dapat menekan tombol "Unduh Dokumen" sehingga sistem mengambil berkas dari tabel dokumen\_resmi dan mengirimkannya ke perangkat pemohon. Apabila status belum selesai (masih dalam proses verifikasi, rekonsiliasi anomali, atau penandatanganan TTE), pemohon dinyatakan selesai memantau status untuk sesi tersebut, karena proses lanjutan dikerjakan oleh aktor lain (Verifikator/Dukcapil dan Pejabat Penanda Tangan) pada fitur yang berbeda, di luar cakupan fitur Mengelola Status Pengajuan. Kedua kemungkinan ini kemudian bertemu pada satu merge point sebelum mencapai final node, sehingga diagram hanya memiliki satu titik akhir yang jelas

## **9.6 Memverifikasi Berkas**

![Activity Diagram Memverifikasi Berkas][image31]

##### Gambar 9.6 Activity Diagram Memverifikasi Berkas {#gambar-9.6-activity-diagram-memverifikasi-berkas}

Gambar 9.6, proses verifikasi dimulai ketika aktor Verifikator Dukcapil membuka halaman "Daftar Antrean Pengajuan" dan memilih berkas laporan yang berstatus "Menunggu Verifikasi". Sistem kemudian merespons dengan menampilkan detail data laporan beserta dokumen pendukung, sekaligus membandingkan data laporan tersebut secara langsung dengan master data kependudukan yang ada di sistem. Setelah pemeriksaan dilakukan, Verifikator Dukcapil mengambil keputusan berdasarkan validitas berkas.  
Apabila berkas dinyatakan valid dan data almarhum telah sesuai, Verifikator menekan tombol "Setujui Berkas" serta mengonfirmasi persetujuan dokumen. Sistem kemudian memperbarui status pengajuan menjadi "Disetujui / Menunggu TTE", meneruskan berkas ke antrean Pejabat Penanda Tangan untuk proses penandatanganan elektronik, dan menampilkan notifikasi bahwa berkas berhasil disetujui. Namun, jika data ditemukan tidak sesuai atau memerlukan perbaikan, Verifikator menekan tombol "Tolak / Butuh Revisi", mengisi alasan penolakan pada kolom catatan revisi, dan menekan tombol "Kirim Catatan Revisi". Sistem lantas menyimpan status pengajuan sebagai "Ditolak / Butuh Revisi", mengirimkan notifikasi revisi ke akun pemohon, serta menampilkan notifikasi bahwa catatan revisi telah berhasil dikirim.

## **9.7 Merekonsiliasi Anomali**

![Activity Diagram Merekonsiliasi Anomali][image32]

##### Gambar 9.7 Activity Diagram Merekonsiliasi Anomali {#gambar-9.7-activity-diagram-merekonsiliasi-anomali}

Gambar 9.7 menampilkan Activity Diagram untuk fitur Merekonsiliasi Anomali dengan dua swimlane, yaitu Verifikator Dukcapil dan Sistem. Proses dimulai dari Verifikator membuka halaman "Rekonsiliasi Anomali Data" dan memilih kasus anomali dari daftar, kemudian Sistem menampilkan perbandingan submitted data dan population master data. Alur selanjutnya ditentukan oleh sebuah decision yang memeriksa apakah data teridentifikasi fiktif atau konflik kritis (fatal). Jika kondisi bernilai "Ya", alur bercabang ke rangkaian aktivitas penolakan permanen, di mana Verifikator membuka halaman detail, menekan tombol "Tolak Permanen & Eskalasi", mengisi alasan eskalasi dan catatan resmi, hingga Sistem mengunci pengajuan dengan status "Ditolak \- Anomali Data Fatal", mencatat riwayat eskalasi untuk audit trail, dan menampilkan notifikasi keberhasilan. Jika kondisi bernilai "Tidak", alur bercabang ke rangkaian aktivitas rekonsiliasi normal, di mana Verifikator memilih referensi data valid dan mengisi catatan rekonsiliasi, hingga Sistem memperbarui status anomali menjadi "Terselesaikan (Resolved)" dan mengembalikan pengajuan ke antrian approval utama. Kedua cabang tersebut bertemu kembali sebelum proses berakhir pada node stop. 

## **9.8 Menandatangani Dokumen (TTE)**

![Activity Diagram Menandatangani Dokumen (TTE)][image33]

##### Gambar 9.8 Activity Diagram Menandatangani Dokumen (TTE) {#gambar-9.8-activity-diagram-menandatangani-dokumen-(tte)}

Gambar 9.8 memperlihatkan alur aktivitas penandatanganan dan pengembalian dokumen oleh Pejabat Penandatangan. Aktivitas dimulai ketika pejabat membuka halaman Tanda Tangan Dokumen, memilih dokumen berstatus "MENUNGGU", dan masuk ke halaman detail dokumen yang menampilkan tombol "Tanda Tangan" dan "Kembalikan". Pada percabangan aktivitas Tanda Tangan, sistem menampilkan dialog konfirmasi dan form kredensial TTE; jika pejabat membatalkan proses, status dokumen tetap "MENUNGGU", sedangkan jika kredensial yang dimasukkan tidak valid, sistem menampilkan pesan error dan status dokumen tidak berubah. Apabila kredensial valid, sistem membubuhkan tanda tangan elektronik dan mengubah status dokumen menjadi "SELESAI". Pada percabangan aktivitas Kembalikan, sistem mewajibkan pejabat mengisi alasan pengembalian; jika kosong, sistem menampilkan pesan error, namun jika terisi, sistem mengubah status dokumen menjadi "DIKEMBALIKAN", menyimpan alasan tersebut, dan mengirimkan notifikasi kepada pemohon.

# 

# **BAB X**  

# **SEQUENCE DIAGRAM**

Sequence Diagram memodelkan interaksi antarobjek berdasarkan urutan waktu. Diagram menggunakan stereotype boundary (halaman), control (controller), dan entity (data) sesuai Robustness Analysis.

## **10.1 Login**

![Sequence Diagram Login][image34]

##### Gambar 10.1 Sequence Diagram Login {#gambar-10.1-sequence-diagram-login}

Gambar 10.1 menunjukkan proses login pengguna ke dalam sistem, yang dapat diakses oleh tiga jenis aktor yaitu Pemohon, Verifikator Dukcapil, dan Pejabat. Proses dimulai ketika pengguna membuka halaman Login dan memasukkan NIK beserta Password, kemudian sistem melalui LoginController akan memvalidasi kelengkapan field terlebih dahulu, dan jika ada field yang kosong maka sistem akan menampilkan pesan error. Apabila field telah terisi, sistem akan mengecek kecocokan NIK dan Password ke Database; jika kredensial tidak sesuai maka sistem menampilkan pesan "NIK atau Password salah", sedangkan jika kredensial valid maka sistem akan mengecek role pengguna dan mengarahkan pengguna ke dashboard yang sesuai, yaitu dashboard Pemohon, dashboard Verifikator, atau dashboard Pejabat.

## **10.2 Registrasi**

![Sequence Diagram Registrasi][image35]

##### Gambar 10.2 Sequence Diagram Registrasi {#gambar-10.2-sequence-diagram-registrasi}

Gambar 10.2 menggambarkan proses registrasi akun baru oleh calon pemohon. Proses diawali dengan calon pemohon membuka halaman Registrasi dan mengisi field NIK, Kata Sandi, serta Konfirmasi Kata Sandi, lalu menekan tombol "Daftar" yang akan diteruskan oleh sistem melalui RegistrasiController untuk melewati serangkaian validasi bertingkat, yaitu validasi kelengkapan field, validasi format NIK (16 digit), dan validasi kecocokan antara Kata Sandi dan Konfirmasi Kata Sandi; apabila salah satu validasi tersebut gagal, sistem akan menampilkan pesan error yang sesuai pada masing-masing tahap. Jika seluruh validasi berhasil dilalui, sistem akan menyimpan data akun baru ke dalam Database, menampilkan notifikasi "Registrasi berhasil\!", dan mengarahkan pengguna kembali ke halaman Login.

## **10.3 Mengajukan Laporan Kematian**

![Sequence Diagram Mengajukan Laporan Kematian][image36]

##### Gambar 10.3 Sequence Diagram Mengajukan Laporan Kematian {#gambar-10.3-sequence-diagram-mengajukan-laporan-kematian}

Gambar 10.3 menunjukkan sequence diagram alur kerja pemohon masyarakat dalam mengajukan laporan kematian secara mandiri melalui sistem web. Proses diawali pada tahap pembukaan halaman dan pengisian formulir. Pemohon mengakses halaman pengajuan, sistem menampilkan formulir digital, lalu pemohon menginput data pelapor beserta data almarhum. Setelah data diisi, sistem menyediakan dua cabang alur tindakan. Pemohon dapat memilih untuk menyimpan data sebagai draf—di mana controller akan langsung menyimpan data ke basis data dengan status "Draft" dan menampilkan notifikasi sukses—atau melanjutkan ke alur pengunggahan berkas pendukung seperti Surat Kematian, KTP, dan KK. Pada proses unggah berkas, controller melakukan validasi format dan ukuran file. Jika berkas tidak valid, sistem menampilkan pesan error, sedangkan jika berkas valid, status dokumen diperbarui menjadi "Terunggah".

Tahap akhir adalah proses pengiriman pengajuan laporan. Pemohon meninjau kembali ringkasan data pada halaman unggah berkas lalu menekan tombol kirim. Controller akan memvalidasi kelengkapan seluruh data pengajuan. Jika data tidak lengkap atau invalid, sistem menampilkan pesan kesalahan dan menyorot bidang yang perlu diperbaiki. Sebaliknya, jika data valid, controller akan mengenerate nomor pengajuan secara otomatis, menyimpan data laporan ke basis data dengan status "Menunggu Verifikasi", menyimpan data berkas pendukung, serta membuat notifikasi penerimaan pengajuan. Rangkaian alur diakhiri dengan munculnya notifikasi berhasil beserta nomor pengajuan kepada pemohon, dan status pengajuan di halaman resmi diperbarui menjadi "Menunggu Verifikasi".

## **10.4 Mengelola User Profile**

![Sequence Diagram Mengelola User Profile][image37]

##### Gambar 10.4 Sequence Diagram Mengelola User Profile {#gambar-10.4-sequence-diagram-mengelola-user-profile}

Gambar 10.4 menampilkan Sequence Diagram untuk fitur Mengelola Profil, yang melibatkan participant Halaman Kelola Profil, ProfilController, dan entity Pengguna (data akun). Diagram ini terbagi menjadi dua skenario yang dipisahkan menggunakan divider. Pada skenario pertama, "Melihat dan Mengubah Data Profil", proses nomor 1 hingga 1.2 menggambarkan Halaman meminta data profil ke Controller yang kemudian mengambilnya dari entity Pengguna, dilanjutkan proses nomor 1.3 di mana Halaman menampilkan data profil kepada Pengguna. Proses nomor 1.4 hingga 1.6 menggambarkan Pengguna mengubah data (nama, email, no. telepon, foto) dan menekan tombol "Simpan Profil", yang kemudian dikirim ke Controller dan divalidasi melalui validateProfileData() pada proses 1.6.1. Blok alt yang mengikuti membedakan dua kondisi: apabila data tidak valid (proses 1.6.2), Controller mengembalikan error validasi yang ditampilkan Halaman kepada Pengguna (proses 1.6.3–1.6.4); apabila data valid (proses 1.6.5), Controller memperbarui data profil ke entity Pengguna (proses 1.6.6–1.6.7) dan mengirimkan notifikasi sukses "Profil berhasil diperbarui" yang diteruskan Halaman kepada Pengguna (proses 1.6.8–1.6.9).  
Pada skenario kedua, "Mengubah Kata Sandi", proses nomor 2 hingga 2.4 menggambarkan Pengguna membuka section "Ubah Kata Sandi" serta mengisi kata sandi lama, kata sandi baru, dan konfirmasi kata sandi sebelum menekan tombol "Simpan Perubahan". Proses nomor 2.5 menggambarkan pengiriman data ke Controller, yang kemudian memverifikasi kata sandi lama ke entity Pengguna (proses 2.5.1–2.5.2) sebelum masuk ke blok alt pertama. Blok alt bertingkat (nested) tersebut menangani tiga kemungkinan kegagalan secara berurutan: kata sandi lama tidak sesuai (proses 2.5.3), kata sandi baru tidak memenuhi ketentuan minimal delapan karakter serta kombinasi huruf dan angka melalui validatePasswordFormat() (proses 2.5.6–2.5.8), dan konfirmasi kata sandi tidak cocok dengan kata sandi baru melalui compare() (proses 2.5.11–2.5.13). Ketiga kondisi tersebut masing-masing menghasilkan pesan error yang dikembalikan ke Halaman untuk ditampilkan kepada Pengguna, tanpa melanjutkan proses ke tahap berikutnya. Apabila seluruh validasi terpenuhi (proses 2.5.16), Controller melakukan hashing terhadap kata sandi baru melalui hashPassword() (proses 2.5.17), menyimpannya ke entity Pengguna (proses 2.5.18–2.5.19), serta mengirimkan notifikasi sukses "Kata sandi berhasil diperbarui" yang diteruskan Halaman kepada Pengguna pada proses 2.5.20–2.5.21.

## **10.5 Mengelola Status Pengajuan**

![][image38]

##### Gambar 10.5 Sequence Diagram Mengelola Status Pengajuan {#gambar-10.5-sequence-diagram-mengelola-status-pengajuan}

Diagram menggambarkan interaksi antara Pemohon, Halaman Kelola Status Laporan, StatusLaporanController, dan Database. Saat Pemohon memilih menu "Lacak Status Pengajuan" (1), halaman meminta daftar pengajuan melalui controller, yang mengambilnya dari database (1.1–1.4). Ketika Pemohon memasukkan nomor pengajuan dan menekan "Cari" (2), controller mencari data di database dan mengembalikan ringkasan beserta lini masa lima tahap jika data ditemukan, atau pesan "Data tidak ditemukan" jika tidak (2.1–2.4). Bila status sudah "Diterbitkan/Selesai", Pemohon dapat mengklik "Unduh Dokumen" (3), lalu controller mengambil dokumen resmi dari database dan mengirimkannya ke halaman sebagai berkas PDF yang terunduh di perangkat Pemohon (3.1–3.5).

## **10.6 Memverifikasi Berkas Pengajuan**

![Sequence Diagram Memverifikasi Berkas Pengajuan][image39]

##### Gambar 10.6 Sequence Diagram Memverifikasi Berkas Pengajuan {#gambar-10.6-sequence-diagram-memverifikasi-berkas-pengajuan}

Pada Gambar 10.6, Sequence diagram ini menjelaskan alur kerja petugas Verifikator Dukcapil dalam memeriksa dan menentukan status berkas pengajuan laporan kematian. Proses diawali ketika verifikator membuka halaman daftar antrean pengajuan. Sistem melalui controller akan mengambil seluruh data laporan yang berstatus "Menunggu Verifikasi" dari basis data, lalu menyajikannya dalam bentuk daftar antrean. Saat verifikator memilih salah satu pengajuan dari antrean, sistem akan mengarahkan ke halaman detail verifikasi dan memanggil controller untuk mengambil rincian data laporan beserta berkas pendukung dari basis data agar dapat diperiksa.  
Setelah memeriksa berkas, verifikator memiliki tiga opsi tindakan utama. Pada alur persetujuan, verifikator menekan tombol "Setujui Berkas" dan mengonfirmasinya, sehingga controller menyimpan hasil verifikasi "Disetujui", memperbarui status menjadi "Disetujui / Menunggu TTE", serta meneruskan berkas ke antrean pejabat. Apabila konfirmasi dibatalkan, status pengajuan tetap bertahan pada "Menunggu Verifikasi". Pada alur penolakan atau revisi, verifikator wajib mengisi catatan revisi. Jika catatan diisi, controller akan mengubah status menjadi "Ditolak / Butuh Revisi" dan mengirim notifikasi peringatan ke akun pemohon; namun jika catatan kosong, sistem akan menampilkan pesan kesalahan. Terakhir, pada alur tandai anomali, verifikator memilih kategori anomali dan catatan internal. Controller kemudian mengubah status pengajuan menjadi "Dalam Rekonsiliasi Anomali", mencatat data ke tabel anomali dokumen, dan mengalihkan berkas ke antrean tim rekonsiliasi. Seluruh tindakan yang berhasil diproses akan menampilkan notifikasi pemberitahuan dan mengarahkan kembali verifikator ke halaman daftar antrean pengajuan.

## **10.7 Merekonsiliasi Anomali**

![Sequence Diagram Merekonsiliasi Anomali][image40]

##### Gambar 10.7 Sequence Diagram Merekonsiliasi Anomali {#gambar-10.7-sequence-diagram-merekonsiliasi-anomali}

Gambar 10.7 menampilkan Sequence Diagram untuk fitur Merekonsiliasi Anomali, dengan aktor Verifikator Dukcapil serta participant Frontend, Backend, dan Database. Diagram ini terbagi menjadi dua skenario yang dipisahkan menggunakan divider. Pada skenario berhasil, proses nomor 1 hingga 1.1 menggambarkan Verifikator membuka halaman "Rekonsiliasi Anomali Data" dan memilih kasus anomali dari daftar, dilanjutkan proses nomor 1.2 di mana Frontend meminta detail data ke Backend melalui endpoint GET /anomali/{id}/detail. Backend kemudian mengambil data submitted dan data master kependudukan dari Database (proses 1.2.1–1.2.2) sebelum mengembalikannya ke Frontend (proses 1.3) untuk ditampilkan kepada Verifikator sebagai perbandingan submitted data dan population master data (proses 1.4). Proses nomor 1.5 hingga 1.7 menggambarkan Verifikator memilih referensi data valid berdasarkan bukti otentik, mengisi catatan rekonsiliasi, dan menekan tombol "Selesaikan Rekonsiliasi", yang kemudian dikirim ke Backend melalui endpoint POST /anomali/{id}/resolve (proses 1.8) dan divalidasi melalui validateResolution() (proses 1.8.1). Proses nomor 1.8.2 hingga 1.8.5 menggambarkan Backend memperbarui status anomali menjadi "Terselesaikan (Resolved)" serta mengembalikan pengajuan ke antrian approval utama di Database, yang diakhiri dengan notifikasi sukses "Anomali berhasil diselesaikan\!" yang diteruskan Frontend kepada Verifikator (proses 1.9–1.10).  
Pada skenario penolakan fatal, proses nomor 2 hingga 2.5 menggambarkan interaksi Verifikator saat membuka halaman "Detail Rekonsiliasi Anomali", mengonfirmasi adanya data identitas fiktif atau konflik kritis, menekan tombol "Tolak Permanen & Eskalasi", serta mengisi alasan eskalasi dan catatan resmi, sebelum menekan tombol "Konfirmasi Penolakan Fatal". Proses nomor 2.6 menggambarkan pengiriman data ke Backend melalui endpoint POST /anomali/{id}/reject-permanent, yang divalidasi melalui validateFatalRejection() (proses 2.6.1). Proses nomor 2.6.2 hingga 2.6.5 menggambarkan Backend mengunci pengajuan dengan status "Ditolak \- Anomali Data Fatal" serta mencatat riwayat eskalasi untuk audit trail ke Database, yang diakhiri dengan notifikasi "Pengajuan berhasil ditolak permanen\!" yang diteruskan Frontend kepada Verifikator (proses 2.7–2.8).

## **10.8 Menandatangani Dokumen (TTE)**

![Sequence Diagram Menandatangani Dokumen (TTE)][image41]

##### Gambar 10.8 Sequence Diagram Menandatangani Dokumen (TTE) {#gambar-10.8-sequence-diagram-menandatangani-dokumen-(tte)}

Gambar 10.8 memperlihatkan proses penandatanganan dan pengembalian dokumen oleh Pejabat Penandatangan melalui dua halaman, yaitu Halaman Tanda Tangan Dokumen dan Halaman Detail Dokumen. Setelah pejabat memilih dokumen berstatus "MENUNGGU" dan masuk ke halaman detail, pejabat dapat memilih dua aksi, yaitu menandatangani atau mengembalikan dokumen. Pada alur tanda tangan, sistem menampilkan form kredensial TTE yang kemudian diverifikasi oleh TandaTanganController ke Database; jika kredensial tidak valid maka status dokumen tetap "MENUNGGU" disertai pesan error, sedangkan jika valid maka sistem akan membubuhkan tanda tangan elektronik dan mengubah status dokumen menjadi "SELESAI". Pada alur pengembalian, sistem mewajibkan pejabat mengisi alasan pengembalian; jika alasan kosong maka sistem menampilkan pesan error, namun jika alasan telah diisi maka sistem akan mengubah status dokumen menjadi "DIKEMBALIKAN", menyimpan alasan tersebut, serta mengirimkan notifikasi kepada pemohon.

# 

# **BAB XI**  

# **CLASS DIAGRAM**

*![][image42]*

##### Gambar 11.1 Class Diagram SATUDUKA {#gambar-11.1-class-diagram-satuduka}

Class diagram SATUDUKA menggambarkan struktur statis sistem pelaporan dan penerbitan dokumen kematian dengan mengimplementasikan pola arsitektur Boundary-Control-Entity (BCE). Pada Gambar 11.1 Class Diagram SATUDUKA, sistem dirancang menggunakan pola arsitektur antarmuka (Boundary), logika bisnis (Control), dan entitas data (Entity) yang memastikan perubahan tampilan tidak memengaruhi logika proses maupun struktur data utama. Interaksi pengguna diawali dari delapan kelas Boundary utama, meliputi LoginScreen, RegistrasiScreen, ProfilScreen, LaporanScreen, StatusPengajuanScreen, VerifikasiScreen, RekonsiliasiScreen, dan TandaTanganScreen yang terhubung langsung ke pengontrolnya masing-masing.

1. Lapisan Boundary (Antarmuka Pengguna)  
   Berada di baris paling atas yang mencakup *LoginScreen*, *RegistrasiScreen*, *ProfilScreen*, *LaporanScreen*, *StatusPengajuanScreen*, *VerifikasiScreen*, *RekonsiliasiScreen*, dan *TandaTanganScreen*. Lapisan ini berfungsi sebagai media interaksi langsung antara pengguna dengan sistem.  
2. Lapisan Control (Logika Pemrosesan)  
   Berada di baris tengah yang berisi pengontrol utama seperti *LoginController*, *RegistrasiController*, *ProfilController*, *LaporanController*, *StatusPengajuanController*, *VerifikasiController*, *RekonsiliasiController*, dan *TandaTanganController*, serta layanan eksternal (*SIAKService*, *TTEService*, dan *NotifikasiService*). Lapisan ini mengolah alur kerja sistem, memvalidasi masukan data, dan menghubungkan antarmuka dengan entitas data.  
3. Lapisan Entity (Struktur Data Usaha):   
   Berada di baris paling bawah yang terdiri dari entitas utama seperti *Pengguna* (dengan spesialisasi peran *Pemohon*, *Verifikator*, dan *Pejabat*), *LaporanKematian*, *BerkasPendukung*, *HasilVerifikasi*, *AnomaliDokumen*, dan *DokumenResmi*. Lapisan ini menyimpan atribut serta operasi manipulasi data inti pencatatan dan administrasi kematian.

Seluruh aliran komunikasi antar-kelas terhubung secara linier dan tegak lurus vertikal dari *Boundary* menuju *Control*, lalu diteruskan ke *Entity*, sehingga menjaga keterbacaan struktur sistem serta kemudahan pemeliharaan kode program.

# 

# **BAB XII**  

# **KEBUTUHAN NONFUNGSIONAL**

Bagian ini menjelaskan atribut kualitas aplikasi. Kebutuhan berikut diturunkan dari spesifikasi use case dan user story; kolom prioritas dilengkapi kemudian.

## **12.1 Performance Requirements**

Bagian ini menetapkan kebutuhan kinerja SATUDUKA yang terukur dan dapat diuji. Kebutuhan diturunkan dari spesifikasi use case, sistem eksternal, dan beban kerja layanan pencatatan kematian di Kota Yogyakarta. Kondisi pengukuran, kecuali dinyatakan lain, adalah koneksi internet normal (minimal 4G atau Wi-Fi) dan beban sistem dalam batas kapasitas pada PERF-09.  
Berdasarkan data Dinas Kependudukan dan Pencatatan Sipil Kota Yogyakarta (Pemerintah Kota Yogyakarta, 2025), angka kematian di Kota Yogyakarta sekitar 3.200 jiwa per tahun. Jumlah pengajuan surat keterangan atau akta kematian rata-rata 8–10 per hari atau sekitar 240–300 per bulan. Kebutuhan kinerja dirancang agar sistem tetap stabil pada beban sekitar lima kali lipat dari rata-rata tersebut untuk mengantisipasi lonjakan, misalnya pada hari kerja setelah akhir pekan atau libur. Tabel 12.1 merangkum asumsi beban kerja yang menjadi dasar seluruh kebutuhan kinerja pada bagian ini.

#### Tabel 12.1 Asumsi Beban Kerja {#tabel-12.1-asumsi-beban-kerja}

| Parameter | Nilai |
| ----- | ----- |
| Pengajuan rata-rata per hari | 8–10 |
| Pengajuan rata-rata per bulan | 240–300 |
| Beban rancangan (puncak) | 50 pengajuan per hari |
| Pengguna bersamaan (rancangan) | 50 pengguna |
| Berkas per pengajuan | maks. 5 berkas, masing-masing ≤ 5 MB |
| Jam layanan Pemohon | 24 jam, setiap hari |
| Jam kerja Verifikator dan Pejabat | 09.00–17.00 WIB, hari kerja |

Tabel 12.1 menunjukkan bahwa beban rata-rata SATUDUKA tergolong kecil, yaitu kurang dari sepuluh pengajuan per hari. Karena itu, sistem tidak dirancang untuk beban rata-rata saja, melainkan untuk beban puncak sebesar 50 pengajuan per hari atau sekitar lima kali lipat rata-rata. Cadangan ini mengantisipasi lonjakan pengajuan, misalnya pada hari kerja pertama setelah akhir pekan atau libur panjang. Tabel ini juga membedakan jam layanan: Pemohon dapat mengakses sistem sepanjang hari, sedangkan Verifikator dan Pejabat bekerja pada jam kerja 09.00–17.00 WIB sehingga target proses layanan dihitung berdasarkan jam kerja tersebut.  
Kecepatan respons berpengaruh langsung pada kenyamanan pengguna, terutama Pemohon yang umumnya mengajukan laporan dalam kondisi berduka dan tidak terbiasa dengan sistem digital. Tabel 12.2 menetapkan batas waktu respons untuk tindakan utama pengguna, mulai dari memuat halaman dan login, mengunggah berkas, mengirim formulir pengajuan, hingga memantau status dan mengunduh dokumen. 

#### Tabel 12.2 Waktu Respon Sistem {#tabel-12.2-waktu-respon-sistem}

| ID | Kebutuhan | Use Case Terkait | Prioritas |
| ----- | ----- | ----- | ----- |
| PERF-01 | Halaman Login, Register, dan dashboard setiap peran dimuat dalam ≤ 3 detik. | Register, Login | Tinggi |
| PERF-02 | Validasi kredensial saat Login selesai dan pengguna diarahkan ke dashboard dalam ≤ 3 detik setelah tombol "Login" ditekan. | Login | Tinggi |
| PERF-03 | Unggah satu berkas (maks. 5 MB) selesai dalam ≤ 10 detik. | Mengajukan Laporan Kematian | Sedang |
| PERF-04 | Penyimpanan atau pengiriman formulir pengajuan (di luar waktu unggah berkas) selesai dalam ≤ 5 detik. | Mengajukan Laporan Kematian | Tinggi |
| PERF-05 | Perubahan status pengajuan tampil di halaman pemohon dalam ≤ 5 detik setelah petugas memprosesnya. | Mengelola Status Pengajuan | Sedang |
| PERF-06 | Daftar pengajuan, daftar anomali, dan daftar dokumen TTE (termasuk pencarian dan filter) tampil dalam ≤ 3 detik. | Memverifikasi Berkas, Merekonsiliasi Anomali, Menandatangani Dokumen | Sedang |
| PERF-07 | Unduh dokumen resmi (PDF) dimulai dalam ≤ 5 detik setelah tombol unduh ditekan. | Mengelola Status Pengajuan | Sedang |

Batas waktu pada Tabel 12.2 dibedakan menurut jenis tindakan. Tindakan ringan seperti memuat halaman, login, dan menampilkan daftar dibatasi sampai 3 detik. Tindakan yang membawa data lebih besar diberi batas lebih longgar, misalnya unggah satu berkas sampai 10 detik karena ukuran berkas dapat mencapai 5 MB. Pengiriman formulir (PERF-04) dihitung terpisah dari unggah berkas agar waktu pengukuran tidak bercampur dengan kecepatan koneksi pengguna. Kebutuhan dengan prioritas Tinggi menyangkut gerbang masuk sistem dan pengiriman pengajuan, karena kegagalannya langsung menghalangi Pemohon memperoleh layanan.  
SATUDUKA bergantung pada tiga sistem eksternal, yaitu SIAK, basis data Faskes/RS, dan layanan TTE. Kecepatan layanan tersebut berada di luar kendali SATUDUKA, sehingga kebutuhan pada Tabel 12.3 dirumuskan sebagai batas waktu tunggu yang ditetapkan sistem, bukan jaminan kecepatan layanan pihak lain.

#### Tabel 12.3 Kinerja Integrasi dengan Sistem Eksternal {#tabel-12.3-kinerja-integrasi-dengan-sistem-eksternal}

| ID | Kebutuhan | Use Case Terkait | Prioritas |
| ----- | ----- | ----- | ----- |
| PERF-08 | Pengambilan data dari SIAK dan Faskes/RS pada halaman verifikasi ditampilkan dalam ≤ 10 detik. Bila melewati batas, sistem menandai berkas untuk pemeriksaan ulang atau verifikasi manual dan mencatat kegagalan ke log, tanpa menolak pengajuan otomatis. | Memverifikasi Berkas, Merekonsiliasi Anomali | Tinggi |
| PERF-09 | Proses TTE selesai dalam ≤ 10 detik setelah kredensial TTE valid, sampai dokumen berstatus SELESAI. Pembaruan status kematian pada SIAK dilakukan dalam ≤ 1 menit setelahnya. | Menandatangani Dokumen (TTE) | Tinggi |

Pada PERF-08, bila SIAK atau Faskes/RS tidak merespons dalam 10 detik, sistem tidak menolak pengajuan secara otomatis. Berkas ditandai untuk diperiksa ulang atau diverifikasi manual, dan kegagalan dicatat ke log, sesuai prinsip ketahanan terhadap gangguan pada Bab 2.2.2. Dengan demikian, gangguan layanan eksternal tidak merugikan Pemohon. PERF-09 memberi batas pada proses TTE dan pembaruan status kematian di SIAK agar status dokumen yang dilihat Pemohon cepat berubah menjadi SELESAI setelah dokumen ditandatangani.   
Selain kecepatan, sistem harus mampu menampung jumlah pengguna, volume pengajuan, dan data yang terus bertambah. Tabel 12.4 menetapkan batas kapasitas berdasarkan beban rancangan pada Tabel 12.1. 

#### Tabel 12.4 Kapasitas dan Beba {#tabel-12.4-kapasitas-dan-beba}

| ID | Kebutuhan | Prioritas |
| ----- | ----- | ----- |
| PERF-10 | Sistem melayani minimal 50 pengguna bersamaan (pemohon, verifikator, dan pejabat) tanpa penurunan waktu respons melebihi batas pada Tabel 12.2. | Tinggi |
| PERF-11 | Sistem mampu memproses sampai 50 pengajuan per hari (± 5 kali beban rata-rata) tanpa kegagalan. | Tinggi |
| PERF-12 | Penyimpanan berkas mampu menampung minimal 100 GB untuk kebutuhan satu tahun pertama, dengan perhitungan 300 pengajuan per bulan dan maks. 25 MB berkas per pengajuan. | Sedang |
| PERF-13 | Penambahan jumlah data pengajuan hingga 3 tahun tidak membuat waktu respons daftar dan pencarian (PERF-06) melampaui batas. | Sedang |

	Tabel 12.4 memastikan target waktu respons pada Tabel 12.2 tetap terpenuhi saat beban naik. Batas 50 pengguna bersamaan dan 50 pengajuan per hari jauh di atas beban rata-rata, sehingga sistem tetap responsif pada kondisi puncak. Kebutuhan penyimpanan (PERF-12) dihitung dari 300 pengajuan per bulan dengan maksimal 25 MB berkas per pengajuan, yaitu sekitar 90 GB per tahun pada skenario terburuk, lalu dibulatkan menjadi 100 GB. PERF-13 memastikan pertambahan data selama tiga tahun tidak memperlambat pencarian dan daftar pengajuan.  
	Berbeda dari kebutuhan sebelumnya yang bersifat teknis, Tabel 12.5 memuat target waktu proses layanan yang harus dipenuhi oleh sistem bersama petugas. Target ini mencerminkan komitmen layanan kepada masyarakat, yaitu dokumen kematian selesai paling lama tiga hari kerja. 

#### Tabel 12.5 Target Waktu Proses Layanan {#tabel-12.5-target-waktu-proses-layanan}

| ID | Kebutuhan | Use Case Terkait | Prioritas |
| ----- | ----- | ----- | ----- |
| PERF-14 | Verifikator memproses setiap berkas (menyetujui, menolak atau meminta revisi, atau menandai anomali) dalam ≤ 8 jam kerja sejak berkas berstatus "Menunggu Verifikasi". | Memverifikasi Berkas | Tinggi |
| PERF-15 | Anomali data diselesaikan oleh Verifikator dalam ≤ 8 jam kerja sejak ditandai. | Merekonsiliasi Anomali | Sedang |
| PERF-16 | Pejabat menandatangani dokumen dalam ≤ 8 jam kerja sejak berstatus "Disetujui / Menunggu TTE". | Menandatangani Dokumen (TTE) | Tinggi |
| PERF-17 | Seluruh proses, dari pengajuan lengkap hingga dokumen terbit dan dapat diunduh, selesai dalam ≤ 3 hari kerja. Waktu menunggu pemohon memperbaiki berkas tidak dihitung. | Seluruh alur layanan | Tinggi |

Batas tiga hari kerja pada PERF-17 dibagi menjadi tiga tahap, masing-masing maksimal satu hari kerja (8 jam kerja): verifikasi berkas (PERF-14), rekonsiliasi anomali bila ditemukan (PERF-15), dan penandatanganan TTE (PERF-16). Waktu menunggu Pemohon memperbaiki berkas tidak dihitung, karena berada di luar kendali petugas. SATUDUKA mendukung target ini dengan menampilkan status dan data pembanding secara terpusat sehingga petugas tidak perlu mencari berkas secara manual. Target di tabel ini berlaku pada jam kerja 09.00–17.00 WIB.

## **12.2 Security Requirements**

#### Tabel 12.6 Kebutuhan Keamanan {#tabel-12.6-kebutuhan-keamanan}

| ID | Kebutuhan | Prioritas |
| ----- | ----- | ----- |
| NFR-01 | Kata sandi baru minimal 8 karakter dan mengandung huruf serta angka; kata sandi disimpan dalam bentuk hash. | Tinggi |
| NFR-02 | Sistem mengunci akun sementara (15 menit) setelah terlalu banyak percobaan login gagal. | Tinggi |
| NFR-03 | Sistem mengunci akses TTE selama 15 menit dan mencatat ke log keamanan setelah 3 kali kredensial TTE gagal. | Tinggi |
| NFR-04 | NIK bersifat permanen dan tidak dapat diubah sendiri oleh pengguna. | Sedang |
| NFR-05 | Berkas unggahan dibatasi format PDF, JPG, PNG dengan ukuran maksimal 5 MB per berkas. | Sedang |
| NFR-06 | Setiap tindakan verifikasi dan eskalasi anomali dicatat sebagai log/audit trail. | Tinggi |

Keterangan prioritas: Tinggi berarti kebutuhan wajib terpenuhi pada rilis pertama karena kegagalannya dapat membuka akses tidak sah atau merusak keabsahan dokumen resmi; Sedang berarti kebutuhan harus dipenuhi tetapi dampak kegagalannya lebih terbatas atau dapat dimitigasi; Rendah berarti kebutuhan bersifat pelengkap dan dapat ditunda.

## **12.3 Software Quality Attributes**

Terdapat tribut kualitas perangkat lunak (software quality attributes) tambahan yang harus dipenuhi oleh sistem SATUDUKA di luar kinerja (performance) dan keamanan (security). Atribut ini mencakup aspek ketersediaan (availability), keandalan (reliability), keterlacakan (auditability), keterpeliharaan (maintainability), serta keusapan dan portabilitas (usability & portability). Pemenuhan atribut ini bertujuan untuk memastikan sistem berjalan secara konsisten, relevan dengan regulasi pelindungan data, serta dapat digunakan dengan mudah oleh seluruh pemangku kepentingan. Rincian kebutuhan atribut kualitas dirangkum pada Tabel 12.7.

#### Tabel 12.7 Kebutuhan Atribut Kualitas Perangkat Lunak  {#tabel-12.7-kebutuhan-atribut-kualitas-perangkat-lunak}

| ID | Kebutuhan | Prioritas |
| ----- | ----- | ----- |
| NFR-QA-01 | Ketersediaan Layanan (Availability): Sistem SATUDUKA dapat diakses oleh Pemohon selama 24 jam sehari, 7 hari seminggu (24/7) dengan target uptime minimal 99,5% per bulan, di luar jadwal pemeliharaan terencana (planned maintenance). | Tinggi |
| NFR-QA-02 | Keandalan dan Penanganan Gangguan Eksternal (Reliability & Fallback Policy): Apabila terjadi gangguan (downtime) atau kegagalan koneksi pada sistem eksternal (SIAK, Faskes/RS, atau BSrE), SATUDUKA tidak akan menolak pengajuan secara otomatis. Berkas pengajuan tetap diterima, dialihkan ke antrean pemeriksaan manual, dan kegagalan integrasi dicatat ke dalam log sistem. | Tinggi |
| NFR-QA-03 | Keterlacakan dan Audit Trail (Auditability): Seluruh aktivitas krusial meliputi pembuatan pengajuan, verifikasi berkas, pemrosesan anomali, penandatanganan TTE, dan pemanggilan API eksternal harus dicatat ke dalam log audit yang bersifat immutable (tidak dapat diubah) untuk menjamin akuntabilitas. | Tinggi |
| NFR-QA-04 | Pelindungan Data Pribadi (Data Privacy & Compliance): Sistem menerapkan prinsip data minimization sesuai Undang-Undang No. 27 Tahun 2022 tentang Pelindungan Data Pribadi (PDP) dengan hanya mengumpulkan dan mengolah data kependudukan serta medis yang relevan dengan penerbitan dokumen kematian. | Tinggi |
| NFR-QA-05 | Portabilitas Peramban (Cross-Browser Portability): Aplikasi web SATUDUKA dapat diakses dan berfungsi optimal pada berbagai peramban web modern (Google Chrome, Mozilla Firefox, Microsoft Edge, Safari) tanpa memerlukan instalasi perangkat lunak atau plug-in tambahan (zero-client installation). | Sedang |
| NFR-QA-06 | Kemudahan Penggunaan dan Desain Responsif (Usability): Antarmuka pengguna (User Interface) dirancang responsif agar dapat menyesuaikan dengan layar PC, laptop, maupun perangkat mobile (smartphone/tablet), serta dilengkapi pesan kesalahan (error message) yang informatif dan mudah dipahami oleh Pemohon. | Sedang |
| NFR-QA-07 | Keterpeliharaan (Maintainability): Arsitektur sistem dirancang secara modular dengan dokumentasi kode dan basis data yang terstruktur untuk memfasilitasi pemeliharaan berkala, perbaikan bug, serta pengembangan fitur integrasi di masa mendatang. | Sedang |

Prioritas Tinggi berarti kebutuhan wajib terpenuhi pada rilis pertama karena kegagalan pemenuhannya dapat mengganggu kelangsungan layanan publik, melanggar regulasi, atau menghilangkan akuntabilitas proses; Sedang berarti kebutuhan harus dipenuhi untuk menjamin kenyamanan penggunaan dan kemudahan pengelolaan jangka panjang.  
Tabel 12.7 menegaskan komitmen keberlanjutan layanan SATUDUKA. Pada NFR-QA-01 dan NFR-QA-02, ketersediaan dan keandalan sistem dijaga agar Pemohon tidak kehilangan akses layanan meskipun terjadi gangguan pada pihak ketiga. Atribut keterlacakan pada NFR-QA-03 dan pelindungan data pada NFR-QA-04 memberikan jaminan kepatuhan hukum dan transparansi atas setiap tindakan yang dilakukan oleh Verifikator maupun Pejabat. Terakhir, NFR-QA-05 hingga NFR-QA-07 memastikan bahwa sistem mudah diakses dari perangkat mana pun oleh masyarakat serta mudah dipelihara oleh pengelola sistem di masa depan.

# 

# **DAFTAR PUSTAKA**

Chinosi, M., & Trombetta, A. (2012). BPMN: An introduction to the standard. Computer Standards & Interfaces, 34(1), 124–134. [https\://doi.org/10.1016/j.csi.2011.06.002](https://doi.org/10.1016/j.csi.2011.06.002)   
Dumas, M., La Rosa, M., Mendling, J., & Reijers, H. A. (2018). Fundamentals of business process management (2nd ed.). Springer. [https\://doi.org/10.1007/978-3-662-56509-4](https://doi.org/10.1007/978-3-662-56509-4)   
Freund, J., & Rücker, B. (2019). Real-life BPMN: Using BPMN and DMN to analyze, improve, and automate processes in your company (4th ed.). Camunda.  
White, S. A. (2004). Introduction to BPMN. IBM Corporation.  
Ambler, S. W. (2010). UML use-case diagrams. In The elements of UML 2.0 style. Cambridge University Press.  
Dennis, A., Wixom, B. H., & Roth, R. M. (2015). Systems analysis and design (6th ed.). Wiley.  
Microsoft. (2013a). UML use case diagrams: Reference. Microsoft Learn. [https\://learn.microsoft.com/id-id/previous-versions/visualstudio/visual-studio-2010/dd409427(v=vs.100)](https://learn.microsoft.com/id-id/previous-versions/visualstudio/visual-studio-2010/dd409427\(v=vs.100\))   
Microsoft. (2013b). UML use case diagrams: Guidelines. Microsoft Learn. [https\://learn.microsoft.com/nl-nl/previous-versions/visualstudio/visual-studio-2012/dd409432(v=vs.110)](https://learn.microsoft.com/nl-nl/previous-versions/visualstudio/visual-studio-2012/dd409432\(v=vs.110\))   
Scott, K. (2011). UML use case diagrams. In Scientific software design (Appendix B). Cambridge University Press.  
Cockburn, A. (2001). Writing effective use cases. Addison-Wesley.  
Dias, F. G., Schmitz, E. A., Campos, M. L. M., Correa, A. L., & Alencar, A. J. (2008). Elaboration of use case specifications: An approach based on use case fragments. Proceedings of the ACM Symposium on Applied Computing, 614–618. [https\://doi.org/10.1145/1363686.1363834](https://doi.org/10.1145/1363686.1363834)   
Friedenthal, S., Moore, A., & Steiner, R. (2015). A practical guide to SysML: The systems modeling language (3rd ed.). Morgan Kaufmann.  
Tiwari, S., & Gupta, A. (2015). A systematic literature review of use case specifications research. Information and Software Technology, 67, 128–158. [https\://doi.org/10.1016/j.infsof.2015.06.004](https://doi.org/10.1016/j.infsof.2015.06.004)   
Yue, T., Briand, L. C., & Labiche, Y. (2015). A use case modeling approach to facilitate the transition towards analysis models: Concepts and empirical evaluation. Proceedings of the 12th International Conference on Model Driven Engineering Languages and Systems, 522–536.  
MEASURE Evaluation. (2019). Implementing Nigeria’s Health Facility Registry: Software Requirements Specifications. Chapel Hill, NC, USA: MEASURE Evaluation, University of North Carolina.  
Institute of Electrical and Electronics Engineers (IEEE). (1998). IEEE Recommended Practice for Software Requirements Specifications. New York, NY, USA: IEEE, Inc.

# 

# **LAMPIRAN**

# 

Lampiran 1 Kontribusi

| Foto | Nama (NIM) | Kontribusi |
| ----- | ----- | ----- |
| ![][image43] | Zahra Mufida (187241087) | Memoderasi pemilihan ide proyek (pemungutan suara dan diskusi), serta merumuskan kerangka dokumen pengumpulan ide (judul, fitur, tujuan, target pengguna, dan output). Mengonsultasikan ide, studi kasus, dan kelayakan dataset kepada asisten praktikum, serta menyampaikan hasil arahan tersebut kepada anggota kelompok. Merumuskan kebutuhan dataset pengujian dan menyusun alur proses bisnis sebagai dasar pembuatan BPMN, termasuk pembagian As-Is dan To-Be untuk dua proses bisnis. Menyusun BPMN As-Is Proses 1\. Menyusun pembagian tugas Use Case Diagram, Use Case Specification, dan wireframe Lo-Fi, serta mengerjakan bagian Use Case Diagram keseluruhan, spesifikasi use case aktor Pemohon, dan wireframe sisi pemohon. Menyusun Use Case Specification, User Story, serta Activity Diagram dan Sequence Diagram untuk fitur Mengelola Status Pengajuan. Menyusun CDM dan PDM, kemudian menyesuaikannya menggunakan PowerDesigner. Menyusun templat laporan pada Google Docs dan menyesuaikan urutan laporan dengan ketentuan yang diminta. Memeriksa dan mengoreksi hasil kerja anggota (format User Story, konsistensi penulisan elemen, activation bar, dan penomoran skenario), serta meneruskan umpan balik asisten kepada kelompok. Menyusun template data mock-up dan mengkoordinasikan penentuan palet warna GUI high-fidelity. Mengerjakan bagian Detail Product Perspective dan kolom prioritas pada tabel Kebutuhan Keamanan dalam dokumen SRS, serta melakukan koreksi dokumen. Mengoordinasikan jadwal, pengingat tenggat, dan pengumpulan tugas. |
| ![][image44] | Florecita Zulfa Nasifa (187241088) | Mengusulkan sejumlah ide proyek serta rumusan judul bagi ide yang dipilih (SatuDuka, SadarLingkungan, dan BeaReal). Merumuskan alur pemrosesan dokumen (OCR, NLP, validasi data, hingga notifikasi) yang menjadi dasar pengembangan sistem. Menyusun BPMN To-Be Proses 1 beserta slide presentasinya. Menyediakan templat Use Case Specification dan mengerjakan spesifikasi use case serta wireframe Lo-Fi untuk aktor Verifikator Dukcapil. Mengerjakan fitur Register, Login, dan Menandatangani Dokumen (TTE), mencakup Use Case Specification, User Story, Activity Diagram, dan Sequence Diagram. Mencatat dan menyampaikan masukan dari sesi responsi dan kelompok lain sebagai bahan revisi. Menyusun pembagian fitur per anggota pada tahap pengerjaan User Story. Memformat laporan akhir (spasi, jenis dan ukuran huruf, format judul pada bagian BPMN, serta pembaruan daftar isi, daftar gambar, dan daftar tabel). Mengerjakan bagian Penjelasan Class Diagram, Performance Requirements, dan Software Quality Attributes dalam dokumen SRS. |
| ![][image45] | Mohammad Farhan Yanuar Akbar(187241082) | Mengusulkan ide Sistem Validasi Kesejahteraan Masyarakat Berbasis AI (SocioVerify AI) dan menyusun dokumen ide secara lengkap, serta merumuskan nama SatuDuka. Mengidentifikasi celah (gap) layanan yang sudah ada dan menjelaskan nilai tambah sistem berupa validasi berbasis NLP. Menyusun BPMN As-Is Proses 2\. Mengerjakan Use Case Specification untuk proses sistem otomatis (validasi awal, pemetaan metadata dan draf dokumen, pembaruan basis data, dan autentikasi) beserta desain antarmukanya. Mengerjakan fitur Mengajukan Laporan Kematian dan Mengelola User Profile, termasuk desain dashboard pemohon, pengelolaan profil, serta tampilan data pelapor dan review. Mengerjakan Activity Diagram dan Sequence Diagram untuk fitur Merekonsiliasi Anomali dan Mengelola User Profile, serta memeriksa kelengkapan activation bar pada diagram anggota lain. Menyusun Class Diagram dan Environment Diagram menggunakan PlantUML, serta menyusun bagian Operating Environment dalam dokumen SRS. Merevisi BPMN sesuai catatan asisten, serta merapikan deskripsi dan keterangan gambar pada laporan. |
| ![][image46] | Farhan Zuso Putra Jaya | Mengusulkan ide digitalisasi layanan Dinas Lingkungan Hidup (kemudian dikembangkan menjadi SadarLingkungan) dan ide pendeteksi berita palsu berbasis AI. Menyusun BPMN To-Be Proses 2 dan memasukkannya ke dalam slide presentasi. Mengerjakan Use Case Specification dan wireframe Lo-Fi untuk aktor Pejabat Berwenang (Tanda Tangan Elektronik). Melakukan kompilasi akhir tahap awal, yaitu menyatukan diagram, spesifikasi, dan wireframe agar konsisten dengan BPMN. Mengerjakan fitur Memverifikasi Berkas Pengajuan dan Mengajukan Laporan Kematian, mencakup Use Case Specification, User Story, Sequence Diagram, dan Activity Diagram. Memperbaiki User Story, Sequence Diagram, dan activation bar sesuai catatan revisi. Mengerjakan bagian Antarmuka Eksternal dan Design & Implementation Constraints dalam dokumen SRS. Mengusulkan pemanfaatan Stitch AI untuk mempercepat pembuatan desain mock-up. |

Lampiran 2 Glosarium

| Istilah | Definisi |
| ----- | ----- |
| SATUDUKA | Sistem Administrasi Terpadu Urusan Duka: sistem informasi berbasis web untuk digitalisasi pencatatan dan administrasi kematian Kota Yogyakarta. |
| Disdukcapil | Dinas Kependudukan dan Pencatatan Sipil. |
| SIAK | Sistem Informasi Administrasi Kependudukan; basis data kependudukan yang menjadi pembanding verifikasi silang. |
| NIK | Nomor Induk Kependudukan (16 digit). |
| TTE | Tanda Tangan Elektronik. |
| Faskes / RS | Fasilitas Kesehatan / Rumah Sakit, penerbit surat keterangan kematian. |
| Anomali data | Ketidaksesuaian data pengajuan dengan data master kependudukan atau dokumen pendukung. |
| Rekonsiliasi | Proses menelusuri dan menyelesaikan anomali data berdasarkan bukti otentik. |
| BPMN | Business Process Model and Notation. |
| CDM / PDM | Conceptual Data Model / Physical Data Model. |
| Gherkin / Behat / Mink | Sintaks skenario berbahasa natural, kerangka acceptance testing, dan ekstensi untuk menguji aplikasi web. |

[image1]: <IMG>

[image2]: <IMG> "BPMN As-Is Proses Penerimaan dan Validasi Awal Berkas Kematian"

[image3]: <IMG> "BPMN To-Be Proses Penerimaan dan Validasi Awal Berkas Kematian"

[image4]: <IMG>

[image5]: <IMG> "BPMN To-Be Proses Verifikasi Silang, Pemetaan, dan Penerbitan"

[image6]: <IMG> "Use Case Diagram Digitalisasi Pencatatan dan Administrasi Kematian Kota Yogyakarta"

[image7]: <IMG>

[image8]: <IMG>

[image9]: <IMG>

[image10]: <IMG>

[image11]: <IMG>

[image12]: <IMG>

[image13]: <IMG>

[image14]: <IMG>

[image15]: <IMG>

[image16]: <IMG>

[image17]: <IMG>

[image18]: <IMG>

[image19]: <IMG>

[image20]: <IMG>

[image21]: <IMG>

[image22]: <IMG>

[image23]: <IMG>

[image24]: <IMG>

[image25]: <IMG>

[image26]: <IMG> "Activity Diagram Login"

[image27]: <IMG> "Activity Diagram Registrasi"

[image28]: <IMG> "Activity Diagram Mengajukan Laporan"

[image29]: <IMG> "Activity Diagram Mengelola User Profile"

[image30]: <IMG>

[image31]: <IMG> "Activity Diagram Memverifikasi Berkas"

[image32]: <IMG> "Activity Diagram Merekonsiliasi Anomali"

[image33]: <IMG> "Activity Diagram Menandatangani Dokumen (TTE)"

[image34]: <IMG> "Sequence Diagram Login"

[image35]: <IMG> "Sequence Diagram Registrasi"

[image36]: <IMG> "Sequence Diagram Mengajukan Laporan Kematian"

[image37]: <IMG> "Sequence Diagram Mengelola User Profile"

[image38]: <IMG>

[image39]: <IMG> "Sequence Diagram Memverifikasi Berkas Pengajuan"

[image40]: <IMG> "Sequence Diagram Merekonsiliasi Anomali"

[image41]: <IMG> "Sequence Diagram Menandatangani Dokumen (TTE)"

[image42]: <IMG>

[image43]: <IMG>

[image44]: <IMG>

[image45]: <IMG>

[image46]: <IMG>