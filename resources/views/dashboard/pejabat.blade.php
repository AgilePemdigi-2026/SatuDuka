@extends('tata_letak.utama')

@section('judul', 'Dashboard Pejabat Penanda Tangan')

@section('navigasi')
    @include('komponen.menu_avatar', ['pengguna' => $pengguna])
@endsection

@section('konten')
    @php
        $totalDokumen = $statistik['menunggu'] + $statistik['selesai'] + $statistik['dikembalikan'];
    @endphp

    <x-ui.page-header title="Dashboard Pejabat Penanda Tangan"
        subtitle="Selamat datang, {{ $pengguna->nama_lengkap }}. Anda masuk sebagai {{ $pengguna->labelPeran() }}. Antrean dokumen untuk ditandatangani elektronik.">
        <x-slot:aksi>
            <x-ui.button :href="route('tanda_tangan.index')" variant="secondary">Buka Semua Dokumen</x-ui.button>
        </x-slot:aksi>
    </x-ui.page-header>

    <section class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat label="Menunggu Tindakan" :value="$statistik['menunggu']" variant="warning" />
        <x-ui.stat label="Selesai" :value="$statistik['selesai']" variant="success" />
        <x-ui.stat label="Dikembalikan" :value="$statistik['dikembalikan']" variant="danger" />
        <x-ui.stat label="Total Dokumen" :value="$totalDokumen" variant="brand" />
    </section>

    <section class="mt-8">
        <x-ui.card>
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-ink">Antrean Tanda Tangan</h2>
                <p class="mt-1 text-sm text-muted">Dokumen yang menunggu tanda tangan elektronik Anda.</p>
            </div>

            @if ($dokumenMenunggu->isEmpty())
                <x-ui.empty class="mt-4" ikon="✓" judul="Tidak Ada Antrean">
                    Belum ada dokumen yang menunggu tanda tangan.
                </x-ui.empty>
            @else
                <x-ui.table class="mt-4 hidden md:block">
                    <x-slot:head>
                        <th>No.</th>
                        <th>Nomor Pengajuan</th>
                        <th>Nama Almarhum/Almarhumah</th>
                        <th>Tanggal Pengajuan</th>
                        <th class="text-center">Aksi</th>
                    </x-slot:head>

                    @foreach ($dokumenMenunggu as $index => $dokumen)
                        @php($laporan = $dokumen->laporanKematian)
                        <tr>
                            <td class="text-faint">{{ $index + 1 }}.</td>
                            <td class="whitespace-nowrap font-mono text-xs font-semibold text-ink">
                                {{ $laporan?->no_pengajuan ?? '-' }}
                            </td>
                            <td class="font-semibold text-ink">{{ $laporan?->nama_almarhum ?? '-' }}</td>
                            <td class="whitespace-nowrap text-muted">
                                {{ $laporan?->tanggal_pengajuan?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="text-center">
                                @if ($laporan !== null)
                                    <x-ui.button :href="route('tanda_tangan.show', $laporan)" size="sm">PERIKSA</x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="mt-4 space-y-3 md:hidden">
                    @foreach ($dokumenMenunggu as $dokumen)
                        @php($laporan = $dokumen->laporanKematian)
                        <x-ui.card padding="p-4">
                            <span class="font-mono text-xs font-semibold text-ink">
                                {{ $laporan?->no_pengajuan ?? '-' }}
                            </span>

                            <p class="mt-2 font-semibold text-ink">{{ $laporan?->nama_almarhum ?? '-' }}</p>
                            <p class="mt-1 text-xs text-muted">
                                Tanggal pengajuan: {{ $laporan?->tanggal_pengajuan?->format('d/m/Y') ?? '-' }}
                            </p>

                            @if ($laporan !== null)
                                <div class="mt-3">
                                    <x-ui.button :href="route('tanda_tangan.show', $laporan)" size="sm">PERIKSA</x-ui.button>
                                </div>
                            @endif
                        </x-ui.card>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </section>
@endsection
