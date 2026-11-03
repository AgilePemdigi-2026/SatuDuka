@if ($laporan->adalahDraf())
    <x-ui.button :href="route('laporan.ubah', $laporan)" size="sm">Lanjutkan</x-ui.button>
@elseif ($laporan->bolehDiubah())
    <x-ui.button :href="route('laporan.ubah', $laporan)" size="sm">Perbaiki</x-ui.button>
@else
    <x-ui.button :href="route('pengajuan.detail', $laporan)" size="sm" variant="secondary">Lihat</x-ui.button>
@endif
