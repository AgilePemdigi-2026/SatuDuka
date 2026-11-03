namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PusatNotifikasiController extends Controller
{
    public function indeks(Request $request)
    {
        $pengguna = $request->user();
        $tab = $request->query('tab', 'semua');
        $cari = trim((string) $request->query('cari', ''));

        $query = Notifikasi::where('id_pengguna', $pengguna->id_pengguna)
            ->with('laporan');

        // Filter Pencarian (Nomor Pengajuan atau Isi Notifikasi)
        if ($cari !== '') {
            $query->where(function ($q) use ($cari) {
                $q->where('pesan', 'LIKE', "%{$cari}%")
                  ->orWhereHas('laporan', function ($qLaporan) use ($cari) {
                      $qLaporan->where('no_pengajuan', 'LIKE', "%{$cari}%");
                  });
            });
        }

        // Filter Tab Notifikasi
        if ($tab === 'belum_dibaca') {
            $query->where('sudah_dibaca', false);
        } elseif ($tab === 'perlu_tindakan') {
            $query->where('jenis', 'perlu_diperbaiki');
        } elseif ($tab === 'selesai') {
            $query->where('jenis', 'dokumen_terbit');
        }

        $notifikasis = $query->latest()->paginate(15);
        $jumlahBelumDibaca = Notifikasi::where('id_pengguna', $pengguna->id_pengguna)
            ->where('sudah_dibaca', false)->count();

        return view('notifikasi.index', compact('pengguna', 'notifikasis', 'jumlahBelumDibaca', 'tab', 'cari'));
    }

    public function tandaiSemuaDibaca(Request $request)
    {
        Notifikasi::where('id_pengguna', $request->user()->id_pengguna)
            ->where('sudah_dibaca', false)
            ->update(['sudah_dibaca' => true]);

        return back()->with('sukses', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }
}