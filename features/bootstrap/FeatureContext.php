
<?php

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert;

class FeatureContext implements Context
{
    private Application $app;

    private ?Pengguna $pemohon = null;

    private ?LaporanKematian $laporan = null;

    private ?LaporanKematian $laporanOrangLain = null;

    private mixed $response = null;

    private bool $berkasLengkap = false;

    private bool $pengajuanDitolak = false;

    private bool $pengajuanBerhasilDikirim = false;

    private bool $aksesDitolak = false;

    public function __construct()
    {
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_DATABASE'] = ':memory:';

        $this->app = require dirname(__DIR__, 2)
            . '/bootstrap/app.php';

        $this->app->make(ConsoleKernel::class)->bootstrap();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::connection('sqlite')->getPdo();

        $this->app->make(ConsoleKernel::class)->call('migrate', [
            '--database' => 'sqlite',
            '--force' => true,
        ]);

        if (! DB::connection('sqlite')
            ->getSchemaBuilder()
            ->hasTable('pengguna')) {
            throw new RuntimeException(
                'Tabel pengguna belum terbentuk pada database pengujian.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    private function buatLaporan(array $atribut = []): LaporanKematian
    {
        Assert::assertNotNull(
            $this->pemohon,
            'Pemohon harus login sebelum membuat laporan.'
        );

        return LaporanKematian::factory()->create(array_merge([
            'id_pengguna' => $this->pemohon->getKey(),
            'nama_pelapor' => 'Pemohon BDD',
            'hubungan_pelapor' => 'Anak Kandung',
            'nama_almarhum' => 'Almarhum BDD',
            'nik_almarhum' => '3171234567890123',
            'tanggal_kematian' => now()->subDay()->toDateString(),
            'tempat_kematian' => 'Surabaya',
            'sebab_kematian' => 'Sakit',
            'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        ], $atribut));
    }

    private function buatBerkas(
        LaporanKematian $laporan,
        string $jenis
    ): void {
        BerkasPendukung::create([
            'id_laporan' => $laporan->getKey(),
            'jenis_dokumen' => $jenis,
            'nama_file' => 'dokumen-bdd.pdf',
            'path_file' => 'bdd/dokumen-bdd.pdf',
            'status_unggahan' => BerkasPendukung::STATUS_BERHASIL,
            'tanggal_unggah' => now(),
        ]);
    }

    private function kirimRequest(
        string $method,
        string $url,
        array $data = []
    ): void {
        $request = Request::create($url, $method, $data);

        $request->setLaravelSession($this->app['session.store']);

        if ($this->pemohon !== null) {
            Auth::login($this->pemohon);
        }

        $this->response = $this->app
            ->make(HttpKernel::class)
            ->handle($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Login dan formulir
    |--------------------------------------------------------------------------
    */

    #[Given('pemohon sudah login')]
    public function pemohonSudahLogin(): void
    {
        $this->pemohon = Pengguna::factory()
            ->pemohon()
            ->create([
                'email' => 'bdd-pemohon@example.test',
                'kata_sandi' => 'password123',
            ]);

        Auth::login($this->pemohon);
    }

    #[When('pemohon membuka halaman pengajuan laporan')]
    public function pemohonMembukaHalamanPengajuanLaporan(): void
    {
        $this->kirimRequest('GET', route('laporan.baru'));
    }

    #[Then('halaman pengajuan berhasil ditampilkan')]
    public function halamanPengajuanBerhasilDitampilkan(): void
    {
        Assert::assertNotNull($this->response);
        Assert::assertSame(200, $this->response->getStatusCode());
    }

    #[Then('bagian Data Pelapor ditampilkan')]
    public function bagianDataPelaporDitampilkan(): void
    {
        Assert::assertNotNull($this->response);

        Assert::assertStringContainsString(
            'Data Pelapor',
            $this->response->getContent()
        );
    }

    #[Then('tombol Lanjut tersedia')]
    public function tombolLanjutTersedia(): void
    {
        Assert::assertNotNull($this->response);

        Assert::assertStringContainsString(
            'Lanjut',
            $this->response->getContent()
        );
    }

    #[Then('bagian Data Almarhum belum ditampilkan')]
    public function bagianDataAlmarhumBelumDitampilkan(): void
    {
        Assert::assertNotNull($this->response);

        Assert::assertStringNotContainsString(
            'Data Almarhum/Almarhumah',
            $this->response->getContent()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Skenario draf
    |--------------------------------------------------------------------------
    */

    #[Given('pemohon berada pada formulir pengajuan')]
    public function pemohonBeradaPadaFormulirPengajuan(): void
    {
        $this->pemohonMembukaHalamanPengajuanLaporan();

        Assert::assertNotNull($this->response);
        Assert::assertSame(200, $this->response->getStatusCode());
    }

    #[When('pemohon menyimpan data pelapor dan almarhum yang lengkap')]
    public function pemohonMenyimpanDataLengkap(): void
    {
        Assert::assertNotNull($this->pemohon);

        $this->kirimRequest(
            'POST',
            route('laporan.draf'),
            [
                'nama_pelapor' => 'Pemohon BDD',
                'hubungan_pelapor' => 'Anak Kandung',
                'nama_almarhum' => 'Almarhum BDD',
            ]
        );

        Assert::assertNotNull($this->response);

        Assert::assertContains(
            $this->response->getStatusCode(),
            [302, 303],
            'Penyimpanan draf seharusnya mengarahkan pemohon ke halaman berikutnya.'
        );

        $this->laporan = LaporanKematian::query()
            ->where('id_pengguna', $this->pemohon->getKey())
            ->latest('id_laporan')
            ->first();

        Assert::assertNotNull(
            $this->laporan,
            'Laporan tidak ditemukan setelah request penyimpanan draf.'
        );
    }

    #[Then('data pengajuan tersimpan sebagai draf')]
    public function dataPengajuanTersimpanSebagaiDraf(): void
    {
        Assert::assertNotNull($this->laporan);

        $this->laporan->refresh();

        Assert::assertSame(
            LaporanKematian::STATUS_DRAF,
            $this->laporan->status_pengajuan
        );

        Assert::assertSame('Pemohon BDD', $this->laporan->nama_pelapor);
        Assert::assertSame('Almarhum BDD', $this->laporan->nama_almarhum);
        Assert::assertNull($this->laporan->no_pengajuan);
    }

    #[Then('pemohon dapat melanjutkan pengajuan')]
    public function pemohonDapatMelanjutkanPengajuan(): void
    {
        Assert::assertNotNull($this->laporan);

        Assert::assertTrue(
            $this->laporan->bolehDiubah(),
            'Laporan draf seharusnya masih dapat diubah.'
        );

        $this->kirimRequest(
            'GET',
            route('laporan.ubah', $this->laporan->getKey())
        );

        Assert::assertNotNull($this->response);

        Assert::assertSame(
            200,
            $this->response->getStatusCode(),
            'Halaman edit laporan seharusnya bisa dibuka.'
        );

        Assert::assertStringContainsString(
            'Pemohon BDD',
            $this->response->getContent()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Skenario pengiriman pengajuan
    |--------------------------------------------------------------------------
    */

    #[Given('data pelapor dan almarhum sudah lengkap')]
    public function dataPelaporDanAlmarhumSudahLengkap(): void
    {
        $this->laporan = $this->buatLaporan();
    }

    #[Given('seluruh berkas wajib sudah diunggah')]
    public function seluruhBerkasWajibSudahDiunggah(): void
    {
        Assert::assertNotNull($this->laporan);

        $this->buatBerkas(
            $this->laporan,
            BerkasPendukung::JENIS_SURAT_KETERANGAN_KEMATIAN
        );

        $this->buatBerkas(
            $this->laporan,
            BerkasPendukung::JENIS_KTP_ALMARHUM
        );

        $this->buatBerkas(
            $this->laporan,
            BerkasPendukung::JENIS_KARTU_KELUARGA
        );

        $this->buatBerkas(
            $this->laporan,
            BerkasPendukung::JENIS_KTP_PELAPOR
        );

        $this->berkasLengkap = true;
    }

    #[Given('pemohon berada pada halaman review')]
    public function pemohonBeradaPadaHalamanReview(): void
    {
        Assert::assertNotNull($this->laporan);
        Assert::assertTrue($this->berkasLengkap);
    }

    #[When('pemohon menyetujui pernyataan dan mengirim pengajuan')]
    public function pemohonMenyetujuiDanMengirimPengajuan(): void
    {
        Assert::assertNotNull($this->laporan);
        Assert::assertTrue($this->berkasLengkap);

        $this->kirimRequest(
            'POST',
            route('laporan.kirim', $this->laporan->getKey()),
            [
                'nama_pelapor' => $this->laporan->nama_pelapor,
                'hubungan_pelapor' => $this->laporan->hubungan_pelapor,
                'nama_almarhum' => $this->laporan->nama_almarhum,
                'nik_almarhum' => $this->laporan->nik_almarhum,
                'tanggal_kematian' => $this->laporan->tanggal_kematian,
                'tempat_kematian' => $this->laporan->tempat_kematian,
                'sebab_kematian' => $this->laporan->sebab_kematian,
                'pernyataan' => '1',
            ]
        );

        Assert::assertNotNull($this->response);

        $this->laporan->refresh();

        $this->pengajuanBerhasilDikirim =
            $this->response->getStatusCode() >= 300
            && $this->response->getStatusCode() < 400
            && $this->laporan->status_pengajuan
                === LaporanKematian::STATUS_MENUNGGU_VERIFIKASI;
    }

    #[Then('pengajuan berhasil dikirim')]
    public function pengajuanBerhasilDikirim(): void
    {
        Assert::assertTrue(
            $this->pengajuanBerhasilDikirim,
            'Pengajuan harus berhasil diproses oleh controller.'
        );

        Assert::assertNotNull($this->laporan);
        $this->laporan->refresh();

        Assert::assertSame(
            LaporanKematian::STATUS_MENUNGGU_VERIFIKASI,
            $this->laporan->status_pengajuan
        );
    }

    #[Then('pemohon diarahkan ke halaman detail pengajuan')]
    public function pemohonDiarahkanKeHalamanDetailPengajuan(): void
    {
        Assert::assertNotNull($this->laporan);
        Assert::assertNotNull($this->response);

        Assert::assertSame(
            route('laporan.lihat', $this->laporan->getKey()),
            $this->response->headers->get('Location'),
            'Setelah mengirim laporan, pemohon seharusnya diarahkan ke detail laporan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Skenario tanpa bukti kematian
    |--------------------------------------------------------------------------
    */

    #[Given('data pengajuan sudah lengkap')]
    public function dataPengajuanSudahLengkap(): void
    {
        $this->laporan = $this->buatLaporan();
    }

    #[Given('tidak ada bukti kematian yang diunggah')]
    public function tidakAdaBuktiKematianYangDiunggah(): void
    {
        Assert::assertNotNull($this->laporan);

        $this->berkasLengkap = false;
    }

    #[When('pemohon mengirim pengajuan')]
    public function pemohonMengirimPengajuan(): void
    {
        Assert::assertNotNull($this->laporan);

        $this->kirimRequest(
            'POST',
            route('laporan.kirim', $this->laporan->getKey()),
            [
                'nama_pelapor' => $this->laporan->nama_pelapor,
                'hubungan_pelapor' => $this->laporan->hubungan_pelapor,
                'nama_almarhum' => $this->laporan->nama_almarhum,
                'nik_almarhum' => $this->laporan->nik_almarhum,
                'tanggal_kematian' => $this->laporan->tanggal_kematian,
                'tempat_kematian' => $this->laporan->tempat_kematian,
                'sebab_kematian' => $this->laporan->sebab_kematian,
                'pernyataan' => '1',
            ]
        );

        Assert::assertNotNull($this->response);

        $this->pengajuanDitolak =
            $this->response->getStatusCode() >= 300
            && $this->response->getStatusCode() < 400;

        $this->laporan->refresh();

        $this->pengajuanDitolak = $this->pengajuanDitolak
            && $this->laporan->status_pengajuan
                === LaporanKematian::STATUS_DRAF;
    }

    #[Then('pengajuan ditolak karena bukti kematian tidak tersedia')]
    public function pengajuanDitolakKarenaBuktiKematianTidakTersedia(): void
    {
        Assert::assertTrue(
            $this->pengajuanDitolak,
            'Pengajuan harus tetap menjadi draf jika bukti kematian tidak tersedia.'
        );

        Assert::assertNotNull($this->laporan);

        $this->laporan->refresh();

        Assert::assertSame(
            LaporanKematian::STATUS_DRAF,
            $this->laporan->status_pengajuan
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Skenario akses laporan milik pemohon lain
    |--------------------------------------------------------------------------
    */

    #[Given('terdapat laporan milik pemohon lain')]
    public function terdapatLaporanMilikPemohonLain(): void
    {
        $pemohonLain = Pengguna::factory()
            ->pemohon()
            ->create([
                'email' => 'bdd-pemohon-lain@example.test',
            ]);

        $idPemohonSaatIni = $this->pemohon?->getKey();

        $this->laporanOrangLain = LaporanKematian::factory()->create([
            'id_pengguna' => $pemohonLain->getKey(),
            'nama_pelapor' => 'Pemohon Lain',
            'nama_almarhum' => 'Laporan Milik Orang Lain',
            'status_pengajuan' => LaporanKematian::STATUS_DRAF,
        ]);

        Assert::assertNotSame(
            $idPemohonSaatIni,
            $this->laporanOrangLain->id_pengguna
        );
    }

    #[When('pemohon mencoba melihat atau mengubah laporan tersebut')]
    public function pemohonMencobaMelihatAtauMengubahLaporan(): void
    {
        Assert::assertNotNull($this->pemohon);
        Assert::assertNotNull($this->laporanOrangLain);

        $this->kirimRequest(
            'GET',
            route('laporan.lihat', $this->laporanOrangLain->getKey())
        );

        Assert::assertNotNull($this->response);

        $this->aksesDitolak =
            $this->response->getStatusCode() === 404;
    }

    #[Then('akses ke laporan milik pemohon lain ditolak')]
    public function aksesKeLaporanMilikPemohonLainDitolak(): void
    {
        Assert::assertTrue(
            $this->aksesDitolak,
            'Controller seharusnya menolak akses ke laporan milik pemohon lain.'
        );
    }
}
