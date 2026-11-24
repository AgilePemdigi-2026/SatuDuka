<?php

use App\Support\PenguraiSuratKematian;
use Tests\TestCase;

uses(TestCase::class);

it('mengurai surat keterangan kematian berlabel Indonesia', function () {
    $teks = <<<'TEKS'
        RSUD Kota Yogyakarta
        SURAT KETERANGAN KEMATIAN
        Nomor: SKK-2026-0001

        Yang bertanda tangan di bawah ini menerangkan bahwa:

        Nama Almarhum/Almarhumah : Suharto Wiryo
        NIK : 3471031203559001
        Tanggal Kematian : 1 September 2026
        Tempat Kematian : RSUD Kota Yogyakarta
        Sebab Kematian : Gagal jantung akut

        Dokter Pemeriksa
        dr. Contoh Dokter
        TEKS;

    $hasil = PenguraiSuratKematian::urai($teks);

    expect($hasil['kolom']['nama_almarhum']['nilai'])->toBe('Suharto Wiryo');
    expect($hasil['kolom']['nik']['nilai'])->toBe('3471031203559001');
    expect($hasil['kolom']['tanggal_kematian']['nilai'])->toBe('2026-09-01');
    expect($hasil['kolom']['tempat_kematian']['nilai'])->toBe('RSUD Kota Yogyakarta');
    expect($hasil['skor'])->toBeGreaterThan(0.5);
    expect($hasil['pesan'])->toBeNull();
});

it('mengurai tanggal numerik dan NIK berspasi hasil OCR', function () {
    $teks = "Nama : Siti Aminah\n"
        ."NIK : 3471 0364 0760 9002\n"
        ."Tanggal Kematian : 24-07-2026\n"
        .'Meninggal di Rumah Sakit Umum';

    $hasil = PenguraiSuratKematian::urai($teks);

    expect($hasil['kolom']['nama_almarhum']['nilai'])->toBe('Siti Aminah');
    expect($hasil['kolom']['nik']['nilai'])->toBe('3471036407609002');
    expect($hasil['kolom']['tanggal_kematian']['nilai'])->toBe('2026-07-24');
    expect($hasil['kolom']['tempat_kematian']['nilai'])->toBe('Rumah Sakit Umum');
});

it('menghasilkan kolom kosong pada teks tanpa label yang dikenal', function () {
    $hasil = PenguraiSuratKematian::urai('Halaman pemberitahuan acara kerja bakti.');

    foreach ($hasil['kolom'] as $baris) {
        expect($baris['nilai'])->toBeNull();
    }

    expect($hasil['skor'])->toBe(0.0);
    expect($hasil['pesan'])->not->toBeNull();
});

it('mengabaikan baris Tempat/Tgl Lahir pada KTP sebagai tempat kematian', function () {
    $teks = "PROVINSI D.I. YOGYAKARTA\n"
        ."NIK : 9999990101900001\n"
        ."Nama : Pemohon Contoh\n"
        ."Tempat/Tgl Lahir : Yogyakarta, 01-01-1990\n"
        ."Jenis Kelamin : LAKI-LAKI\n"
        .'Alamat : Jl. Contoh No. 8';

    $hasil = PenguraiSuratKematian::urai($teks);

    expect($hasil['kolom']['nik']['nilai'])->toBe('9999990101900001');
    expect($hasil['kolom']['nama_almarhum']['nilai'])->toBe('Pemohon Contoh');
    expect($hasil['kolom']['tempat_kematian']['nilai'])->toBeNull();
    expect($hasil['kolom']['tanggal_kematian']['nilai'])->toBeNull();
});
