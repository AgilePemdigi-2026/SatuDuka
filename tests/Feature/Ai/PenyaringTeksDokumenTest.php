<?php

use App\Services\Dokumen\PembuatPdf;
use App\Support\PenyaringTeksDokumen;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

/**
 * PDF bertext-layer untuk memastikan fallback OCR tidak dipanggil.
 */
function pdfBertext(): string
{
    return app(PembuatPdf::class)->hasil('dokumen.berkas_contoh_pdf', [
        'judul' => 'SURAT KETERANGAN KEMATIAN',
        'instansi' => 'RSUD Kota Yogyakarta',
        'alamatInstansi' => 'Jl. Marhaen No. 4',
        'nomor' => 'SKK-2026-0099',
        'baris' => [
            ['label' => 'Nama Almarhum/Almarhumah', 'nilai' => 'Siti Aminah'],
            ['label' => 'NIK', 'nilai' => '3471036407609002'],
            ['label' => 'Tanggal Kematian', 'nilai' => '24 Juli 2026'],
            ['label' => 'Tempat Kematian', 'nilai' => 'RSUD Kota Yogyakarta'],
        ],
        'paragraf' => ['Dokumen simulasi.'],
        'ttd' => ['kota' => 'Yogyakarta', 'tanggal' => '24 Juli 2026', 'jabatan' => 'Dokter', 'nama' => 'dr. Contoh', 'nip' => '-'],
    ]);
}

it('memakai text-layer pdf tanpa memanggil pdftoppm', function () {
    Process::fake();

    $teks = PenyaringTeksDokumen::teks('surat.pdf', pdfBertext());

    expect($teks)->toContain('Siti Aminah')
        ->and($teks)->toContain('3471036407609002');

    Process::assertNothingRan();
});

it('membaca pdf hasil pindaian lewat pdftoppm dan tesseract', function () {
    $awalan = null;

    Process::fake(function (PendingProcess $proses) use (&$awalan) {
        $perintah = (array) $proses->command;

        if (in_array('pdftoppm', $perintah, true) || str_contains(implode(' ', $perintah), 'pdftoppm')) {
            $awalan = (string) end($perintah);
            file_put_contents($awalan.'-1.png', 'PNG-HALAMAN-1');
            file_put_contents($awalan.'-2.png', 'PNG-HALAMAN-2');

            return Process::result(exitCode: 0);
        }

        $isiHalaman = (string) @file_get_contents((string) ($perintah[1] ?? ''));
        $teks = $isiHalaman === 'PNG-HALAMAN-2'
            ? 'Baris halaman dua'
            : 'Baris halaman satu';

        return Process::result(output: $teks, exitCode: 0);
    });

    $teks = PenyaringTeksDokumen::teks('hasil-pindai.pdf', "%PDF-1.4\nbiner tanpa text-layer");

    expect($teks)->toBe('Baris halaman satu'.PHP_EOL.PHP_EOL.'Baris halaman dua');

    Process::assertRan(function (PendingProcess $proses): bool {
        $perintah = (array) $proses->command;

        return in_array('pdftoppm', $perintah, true)
            && in_array('-png', $perintah, true)
            && in_array('200', $perintah, true);
    });

    expect($awalan)->not->toBeNull()
        ->and(is_dir(dirname((string) $awalan)))->toBeFalse();
});

it('mengembalikan null saat pdftoppm gagal dan tetap membersihkan direktori', function () {
    $awalan = null;

    Process::fake(function (PendingProcess $proses) use (&$awalan) {
        $perintah = (array) $proses->command;
        $awalan = (string) end($perintah);

        return Process::result(exitCode: 1);
    });

    $teks = PenyaringTeksDokumen::teks('rusak.pdf', "%PDF-1.4\nbiner rusak");

    expect($teks)->toBeNull()
        ->and($awalan)->not->toBeNull()
        ->and(is_dir(dirname((string) $awalan)))->toBeFalse();
});

it('mengembalikan null saat tesseract tidak terbaca dan direktori tetap bersih', function () {
    $awalan = null;

    Process::fake(function (PendingProcess $proses) use (&$awalan) {
        $perintah = (array) $proses->command;

        if (in_array('pdftoppm', $perintah, true)) {
            $awalan = (string) end($perintah);
            file_put_contents($awalan.'-1.png', 'PNG-KOSONG');

            return Process::result(exitCode: 0);
        }

        return Process::result(output: '', exitCode: 0);
    });

    $teks = PenyaringTeksDokumen::teks('pindai-kosong.pdf', "%PDF-1.4\nbiner tanpa text-layer");

    expect($teks)->toBeNull()
        ->and($awalan)->not->toBeNull()
        ->and(is_dir(dirname((string) $awalan)))->toBeFalse();
});

it('menolak berkas yang bukan pdf tanpa menjalankan proses apa pun', function () {
    Process::fake();

    expect(PenyaringTeksDokumen::teks('dokumen.docx', 'bukan pdf'))->toBeNull();

    Process::assertNothingRan();
});

it('menghormati batas halaman dari konfigurasi', function () {
    config(['ai.pdf_ocr.maks_halaman' => 2]);

    $argumen = null;

    Process::fake(function (PendingProcess $proses) use (&$argumen) {
        $perintah = (array) $proses->command;

        if (in_array('pdftoppm', $perintah, true)) {
            $argumen = $perintah;

            return Process::result(exitCode: 1);
        }

        return Process::result(output: '', exitCode: 0);
    });

    PenyaringTeksDokumen::teksPdfOcr("%PDF-1.4\nbiner");

    $posisi = array_search('-l', (array) $argumen, true);

    expect($posisi)->not->toBeFalse()
        ->and($argumen[$posisi + 1])->toBe('2');
});
