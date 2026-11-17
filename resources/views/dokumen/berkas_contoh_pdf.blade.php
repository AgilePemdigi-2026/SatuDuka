<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #111111; }
        .kop { text-align: center; border-bottom: 2px solid #111111; padding-bottom: 8px; margin-bottom: 18px; }
        .instansi { font-size: 13pt; font-weight: bold; }
        .judul { text-align: center; text-decoration: underline; font-weight: bold; margin: 14px 0 6px; }
        .nomor { text-align: center; margin-bottom: 16px; }
        table.baris { width: 100%; border-collapse: collapse; }
        table.baris td { padding: 3px 0; vertical-align: top; }
        td.label { width: 210px; }
        .paragraf { margin: 10px 0; text-align: justify; line-height: 1.5; }
        .ttd { margin-top: 30px; width: 340px; }
        .garis { margin-top: 60px; border-top: 1px solid #111111; padding-top: 4px; font-weight: bold; }
        .catatan { font-size: 8.5pt; color: #555555; margin-top: 26px; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="instansi">{{ $instansi }}</div>
        <div>{{ $alamatInstansi }}</div>
    </div>

    <div class="judul">{{ $judul }}</div>
    <div class="nomor">Nomor: {{ $nomor }}</div>

    <p>Yang bertanda tangan di bawah ini menerangkan dengan sesungguhnya bahwa:</p>

    <table class="baris">
        @foreach ($baris as $barisSatu)
            <tr>
                <td class="label">{{ $barisSatu['label'] }}</td>
                <td>: {{ $barisSatu['nilai'] }}</td>
            </tr>
        @endforeach
    </table>

    @foreach ($paragraf as $paragrafSatu)
        <p class="paragraf">{{ $paragrafSatu }}</p>
    @endforeach

    <div class="ttd">
        <div>{{ $ttd['kota'] }}, {{ $ttd['tanggal'] }}</div>
        <div>{{ $ttd['jabatan'] }}</div>
        <div class="garis">{{ $ttd['nama'] }}</div>
        <div>NIP. {{ $ttd['nip'] }}</div>
    </div>

    <p class="catatan">Dokumen simulasi SATUDUKA berisi data dummy untuk pengujian analisis AI. Bukan dokumen resmi.</p>
</body>
</html>
