@php
    $berhasil = $analisis['berhasil'] ?? false;
    $perbandingan = $analisis['perbandingan'] ?? null;
    $pesan = $analisis['pesan'] ?? null;
    $warnaStatus = fn (string $status) => match ($status) {
        'cocok' => 'bg-green-100 text-green-800',
        'beda' => 'bg-rose-100 text-rose-800',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp

<div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
    <p class="text-sm font-semibold text-amber-900">Hasil bantuan AI, hanya saran</p>
    <p class="mt-1 text-xs text-amber-800">
        AI tidak menyetujui, menolak, atau mengubah status pengajuan. Periksa kembali data dan dokumen asli.
    </p>

    @if (! $berhasil)
        <p class="mt-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            Analisis AI tidak tersedia, lanjutkan secara manual
        </p>
    @elseif ($pesan)
        <p class="mt-3 rounded-md border border-gray-200 bg-white p-3 text-sm text-gray-700">{{ $pesan }}</p>
    @endif

    @if ($berhasil && $perbandingan !== null)
        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="px-3 py-2 font-medium">Kolom</th>
                        <th class="px-3 py-2 font-medium">Isian Pemohon</th>
                        <th class="px-3 py-2 font-medium">Hasil Ekstraksi AI</th>
                        <th class="px-3 py-2 font-medium">Data SIAK</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Keyakinan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($perbandingan['kolom'] as $kolom)
                        <tr>
                            <td class="px-3 py-2 font-medium text-slate-700">{{ $kolom['label'] }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $kolom['nilai_pemohon'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $kolom['nilai_ekstraksi'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $kolom['nilai_siak'] ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $warnaStatus($kolom['status']) }}">
                                    {{ $kolom['status'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-slate-700">{{ number_format(((float) $kolom['keyakinan']) * 100, 0) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($perbandingan['ada_beda'])
            <div class="mt-3 rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                <p class="font-semibold">Ada kolom yang perlu diperiksa:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($perbandingan['kolom'] as $kolom)
                        @if ($kolom['status'] === 'beda')
                            <li>
                                {{ $kolom['label'] }}:
                                @foreach ($kolom['catatan'] as $catatan)
                                    {{ $catatan }}
                                @endforeach
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="mt-2 text-xs text-gray-500">
            Model: {{ $analisis['model'] ?? '-' }} · Skor keseluruhan: {{ number_format(((float) ($perbandingan['skor'] ?? 0)) * 100, 0) }}%
        </p>
    @endif
</div>
