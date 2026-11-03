@extends('tata_letak.utama')

@section('judul', 'Dashboard Pejabat')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    <x-ui.page-header title="Dashboard Pejabat Penanda Tangan"
        :subtitle="'Selamat datang, '.$pengguna->nama_lengkap.'. Anda masuk sebagai '.$pengguna->labelPeran().'. Antrean dokumen untuk ditandatangani elektronik.'">
        <x-slot:aksi>
            <x-ui.button :href="route('tanda_tangan.index')">Buka Tanda Tangan Dokumen</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat label="Menunggu Tindakan" :value="$statistik['menunggu']" variant="warning" />
        <x-ui.stat label="Selesai" :value="$statistik['selesai']" variant="success" />
        <x-ui.stat label="Dikembalikan" :value="$statistik['dikembalikan']" variant="danger" />
    </section>

    <section class="mt-8">
        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-ink">Antrean Tanda Tangan</h2>
                    <p class="mt-1 text-sm text-muted">
                        Dokumen yang sudah disetujui verifikator dan menunggu tanda tangan elektronik.
                    </p>
                </div>

                <x-ui.button :href="route('tanda_tangan.index')" variant="secondary">
                    Buka Semua Dokumen
                </x-ui.button>
            </div>

            @if ($dokumenMenunggu->isEmpty())
                <x-ui.notice icon="check" class="mt-4">
                    <p class="font-semibold text-ink">Tidak ada dokumen menunggu</p>
                    <p class="mt-1">Dokumen yang perlu ditandatangani akan muncul di sini.</p>
                </x-ui.notice>
            @else
                <x-ui.table class="mt-4">
                    <x-slot:head>
                        <th>Nomor Pengajuan</th>
                        <th>Nama Almarhum/Almarhumah</th>
                        <th>Tanggal Pengajuan</th>
                        <th class="text-center">Aksi</th>
                    </x-slot:head>

                    @foreach ($dokumenMenunggu as $dokumen)
                        @php($laporan = $dokumen->laporanKematian)
                        <tr>
                            <td class="font-mono text-xs font-semibold text-ink-soft">
                                {{ $laporan?->no_pengajuan ?? '-' }}
                            </td>
                            <td class="font-medium text-ink">
                                {{ $laporan?->nama_almarhum ?? '-' }}
                            </td>
                            <td class="text-xs text-muted">
                                {{ $laporan?->tanggal_pengajuan?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="text-center">
                                @if ($laporan !== null)
                                    <x-ui.button :href="route('tanda_tangan.show', $laporan)" size="sm">
                                        Periksa
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>
    </section>
@endsection
