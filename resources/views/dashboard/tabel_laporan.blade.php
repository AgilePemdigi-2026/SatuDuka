@if ($daftar->isEmpty())
    <p class="mt-3 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
        {{ $kosong ?? 'Belum ada pengajuan yang diajukan' }}
    </p>
@else
    <div class="mt-3 hidden overflow-hidden rounded-lg border border-gray-200 sm:block">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                    <th class="px-4 py-3 font-medium">Nomor Pengajuan</th>
                    <th class="px-4 py-3 font-medium">Almarhum/Almarhumah</th>
                    <th class="px-4 py-3 font-medium">Tanggal Pengajuan</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @foreach ($daftar as $laporan)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-slate-700">
                            {{ $laporan->no_pengajuan ?? 'Belum diterbitkan' }}
                        </td>
                        <td class="px-4 py-3 text-slate-800">{{ $laporan->labelNamaAlmarhum() }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $laporan->labelTanggalPengajuan() }}</td>
                        <td class="px-4 py-3">
                            @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                        </td>
                        <td class="px-4 py-3">
                            @include('dashboard.aksi_laporan', ['laporan' => $laporan])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 space-y-3 sm:hidden">
        @foreach ($daftar as $laporan)
            <div class="rounded-lg border border-gray-200 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-mono text-xs text-slate-600">
                        {{ $laporan->no_pengajuan ?? 'Belum diterbitkan' }}
                    </span>
                    @include('komponen.badge_status', ['status' => $laporan->status_pengajuan])
                </div>

                <p class="mt-2 font-medium text-slate-800">{{ $laporan->labelNamaAlmarhum() }}</p>
                <p class="mt-1 text-xs text-gray-500">
                    Tanggal Pengajuan: {{ $laporan->labelTanggalPengajuan() }}
                </p>

                <div class="mt-3">
                    @include('dashboard.aksi_laporan', ['laporan' => $laporan])
                </div>
            </div>
        @endforeach
    </div>
@endif
