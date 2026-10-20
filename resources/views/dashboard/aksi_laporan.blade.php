@if ($laporan->adalahDraf())
    <a href="{{ route('laporan.ubah', $laporan) }}"
        class="inline-flex rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-900">Lanjutkan</a>
@elseif ($laporan->bolehDiubah())
    <a href="{{ route('laporan.ubah', $laporan) }}"
        class="inline-flex rounded-md bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700">Perbaiki</a>
@else
    <a href="{{ route('pengajuan.detail', $laporan) }}"
        class="inline-flex rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Lihat</a>
@endif
