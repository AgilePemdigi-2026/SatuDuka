<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kutipan Akta Kematian {{ $nomorDokumen }}</title>
    <style>
        @page { margin: 32px 40px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #0f172a; }
        .banner { border: 1px solid #f59e0b; background: #fef3c7; color: #b45309; text-align: center;
                  font-weight: bold; font-size: 10px; padding: 6px; margin-bottom: 14px; letter-spacing: 0.5px; }
        .kepala { text-align: center; border-bottom: 1px solid #cbd5e1; padding-bottom: 10px; }
        .kepala .instansi { font-weight: bold; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .kepala .dinas { font-size: 10px; color: #475569; text-transform: uppercase; }
        .kepala h1 { font-size: 16px; text-transform: uppercase; letter-spacing: 2px; margin: 12px 0 2px; }
        .nomor { font-size: 10px; color: #475569; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.data td { padding: 4px 6px; vertical-align: top; }
        table.data td.label { width: 34%; color: #475569; }
        table.data td.nilai { font-weight: bold; }
        .ttd { margin-top: 28px; width: 100%; }
        .ttd td { vertical-align: top; }
        .ttd .kanan { text-align: right; }
        .mock { margin-top: 6px; border: 1px dashed #b91c1c; color: #b91c1c; font-size: 9px;
                font-weight: bold; padding: 4px 6px; display: inline-block; text-transform: uppercase; }
        .catatan { margin-top: 22px; border-top: 1px solid #cbd5e1; padding-top: 8px;
                   font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <div class="banner">DOKUMEN SIMULASI - DATA FIKTIF - BUKAN DOKUMEN RESMI</div>

    <div class="kepala">
        <div class="instansi">{{ config('satuduka.instansi') }}</div>
        <div class="dinas">{{ config('satuduka.dinas') }}</div>
        <div class="dinas">{{ config('satuduka.daerah') }}</div>
        <h1>Kutipan Akta Kematian</h1>
        <div class="nomor">Nomor: {{ $nomorDokumen }}</div>
    </div>

    <table class="data">
        <tr>
            <td class="label">Nama Almarhum/Almarhumah</td>
            <td class="nilai">{{ $laporan->nama_almarhum ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIK</td>
            <td class="nilai">{{ $laporan->nik_almarhum ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Lahir</td>
            <td class="nilai">{{ $tanggalLahir ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Umur</td>
            <td class="nilai">{{ $umur !== null ? $umur.' tahun' : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Kematian</td>
            <td class="nilai">{{ $tanggalKematian ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tempat Kematian</td>
            <td class="nilai">{{ $laporan->tempat_kematian ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Sebab Kematian</td>
            <td class="nilai">{{ $laporan->sebab_kematian ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td class="nilai">{{ $alamat ?? '-' }}</td>
        </tr>
    </table>

    <table class="ttd">
        <tr>
            <td>
                <div>Nomor Pengajuan: {{ $laporan->no_pengajuan ?: '-' }}</div>
                <div>Tanggal Terbit: {{ $tanggalTerbit ?? '-' }}</div>
            </td>
            <td class="kanan">
                <div>{{ config('satuduka.daerah') }}, {{ $tanggalTerbit ?? '-' }}</div>
                <div>Pejabat Penanda Tangan</div>
                <div style="margin-top: 28px; font-weight: bold;">{{ $pejabat?->nama_lengkap ?: '[ BELUM DITANDATANGANI ]' }}</div>
                <div style="font-size: 10px; color: #475569;">NIP: {{ $pejabat?->nip ?: '-' }}</div>

                @if ($mock)
                    <div class="mock">TTE SIMULASI/MOCK - BUKAN TTE TERSERTIFIKASI</div>
                @else
                    <div style="font-size: 10px; color: #475569;">Ditandatangani secara elektronik.</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="catatan">
        Dokumen ini dihasilkan oleh SATUDUKA sebagai simulasi akademik. Data berasal dari pengajuan
        pemohon dan data kependudukan contoh. Dokumen tidak memiliki kekuatan hukum.
    </div>
</body>
</html>
