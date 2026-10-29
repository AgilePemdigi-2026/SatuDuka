
<?php

use App\Models\BerkasPendukung;
use App\Models\LaporanKematian;
use App\Models\Pengguna;
use App\Notifications\TautanAturUlangKataSandi;
use App\Services\LayananPenguncian;
use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Cookie;

final class Assert
{
    public static function assertTrue(bool $condition, string $message = ''): void
    {
        if (! $condition) {
            throw new RuntimeException($message !== '' ? $message : 'Expected condition to be true.');
        }
    }

    public static function assertFalse(bool $condition, string $message = ''): void
    {
        self::assertTrue(! $condition, $message !== '' ? $message : 'Expected condition to be false.');
    }

    public static function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message !== '' ? $message : sprintf(
                'Expected %s, got %s.',
                var_export($expected, true),
                var_export($actual, true),
            ));
        }
    }

    public static function assertNotSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected === $actual) {
            throw new RuntimeException($message !== '' ? $message : 'Expected values to differ.');
        }
    }

    public static function assertNotNull(mixed $actual, string $message = ''): void
    {
        if ($actual === null) {
            throw new RuntimeException($message !== '' ? $message : 'Expected value not to be null.');
        }
    }

    public static function assertNull(mixed $actual, string $message = ''): void
    {
        if ($actual !== null) {
            throw new RuntimeException($message !== '' ? $message : 'Expected value to be null.');
        }
    }

    public static function assertNotEmpty(mixed $actual, string $message = ''): void
    {
        if (empty($actual)) {
            throw new RuntimeException($message !== '' ? $message : 'Expected value not to be empty.');
        }
    }

    public static function assertContains(mixed $needle, iterable $haystack, string $message = ''): void
    {
        if (! in_array($needle, is_array($haystack) ? $haystack : iterator_to_array($haystack), true)) {
            throw new RuntimeException($message !== '' ? $message : 'Expected collection to contain value.');
        }
    }

    public static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        if (! str_contains($haystack, $needle)) {
            throw new RuntimeException($message !== '' ? $message : sprintf(
                'Expected text to contain "%s"; received: %s',
                $needle,
                substr(strip_tags($haystack), 0, 500),
            ));
        }
    }

    public static function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
    {
        if (str_contains($haystack, $needle)) {
            throw new RuntimeException($message !== '' ? $message : sprintf('Expected text not to contain "%s".', $needle));
        }
    }
}

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

    private string $halaman = '';

    /**
     * @var array<string, mixed>
     */
    private array $isianForm = [];

    private ?Pengguna $penggunaLain = null;

    private ?string $emailAwal = null;

    private ?string $teleponAwal = null;

    private ?string $kataSandiAwal = null;

    private ?string $fotoLama = null;

    private string $isiValidasi = '';

    private string $urlTerakhir = '';

    private ?string $tokenAturUlang = null;

    private bool $kirimDenganAutentikasi = false;

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
            .'/bootstrap/app.php';

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

    #[BeforeScenario]
    public function mengisolasiSkenario(): void
    {
        Auth::logout();
        Auth::forgetGuards();
        Cache::flush();
        $this->app['session.store']->flush();
        $this->app->make(ConsoleKernel::class)->call('migrate:fresh', [
            '--database' => 'sqlite',
            '--force' => true,
        ]);

        $this->pemohon = null;
        $this->laporan = null;
        $this->laporanOrangLain = null;
        $this->penggunaLain = null;
        $this->response = null;
        $this->halaman = '';
        $this->isianForm = [];
        $this->berkasLengkap = false;
        $this->pengajuanDitolak = false;
        $this->pengajuanBerhasilDikirim = false;
        $this->aksesDitolak = false;
        $this->emailAwal = null;
        $this->teleponAwal = null;
        $this->kataSandiAwal = null;
        $this->fotoLama = null;
        $this->isiValidasi = '';
        $this->urlTerakhir = '';
        $this->tokenAturUlang = null;
        $this->kirimDenganAutentikasi = false;
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
        $files = [];

        if (($data['foto'] ?? null) instanceof UploadedFile) {
            $files['foto'] = $data['foto'];
            unset($data['foto']);
        }

        $server = $this->urlTerakhir === '' ? [] : ['HTTP_REFERER' => $this->urlTerakhir];
        $request = Request::create($url, $method, $data, [], $files, $server);
        $this->urlTerakhir = $request->fullUrl();

        $request->setLaravelSession($this->app['session.store']);

        if ($this->kirimDenganAutentikasi && $this->pemohon !== null) {
            Auth::login($this->pemohon);
        }

        $this->response = $this->app
            ->make(HttpKernel::class)
            ->handle($request);
    }

    private function buatAkunDemo(): array
    {
        $pemohon = Pengguna::factory()->pemohon()->create([
            'nama_lengkap' => 'Pemohon Demo',
            'nik' => '9999990101900001',
            'email' => 'pemohon@demo.satuduka.id',
            'kata_sandi' => 'password123',
        ]);
        $verifikator = Pengguna::factory()->verifikator()->create([
            'nama_lengkap' => 'Verifikator Demo',
            'nip' => '999999991234567890',
            'email' => 'verifikator@demo.satuduka.id',
            'kata_sandi' => 'password123',
        ]);
        $pejabat = Pengguna::factory()->pejabat()->create([
            'nama_lengkap' => 'Pejabat Demo',
            'nip' => '999999992345678901',
            'email' => 'pejabat@demo.satuduka.id',
            'kata_sandi' => 'password123',
        ]);

        return [$pemohon, $verifikator, $pejabat];
    }

    private function namaKolom(string $label): string
    {
        return match (mb_strtolower($label)) {
            'nik', 'nik/nip' => in_array($this->halaman, ['masuk', 'lupa_kata_sandi'], true)
                ? 'identitas'
                : 'nik',
            'alamat surel' => 'email',
            'kata sandi' => 'kata_sandi',
            'kata sandi lama' => 'kata_sandi_lama',
            'kata sandi baru' => 'kata_sandi_baru',
            'konfirmasi kata sandi' => 'konfirmasi_kata_sandi',
            'nomor telepon' => 'no_telepon',
            'nama lengkap' => 'nama_lengkap',
            default => throw new InvalidArgumentException("Kolom BDD tidak dikenal: {$label}"),
        };
    }

    private function bagGalat(): mixed
    {
        if ($this->response !== null && method_exists($this->response, 'getSession')) {
            $bag = $this->response->getSession()->get('errors');

            if ($bag !== null) {
                return $bag;
            }
        }

        return $this->app['session.store']->get('errors');
    }

    private function pesanGalat(): string
    {
        $bag = $this->bagGalat();
        $pesan = is_object($bag) && method_exists($bag, 'getBag')
            ? implode(' ', $bag->getBag('default')->all())
            : '';

        if ($pesan !== '') {
            return $pesan;
        }

        if ($this->isiValidasi === '' && $this->response !== null && $this->response->headers->has('Location')) {
            $this->kirimRequest('GET', $this->response->headers->get('Location'));
            $this->isiValidasi = $this->response->getContent();
        }

        return $this->isiValidasi;
    }

    private function bukaHalaman(string $nama): void
    {
        $nama = mb_strtolower($nama);
        $tujuan = match ($nama) {
            'masuk' => 'masuk',
            'registrasi' => 'registrasi',
            'lupa kata sandi' => 'lupa_kata_sandi',
            'kata sandi baru' => 'atur_ulang_kata_sandi',
            default => throw new InvalidArgumentException("Halaman BDD tidak dikenal: {$nama}"),
        };
        $this->halaman = $tujuan;
        $this->isianForm = [];

        if ($tujuan === 'atur_ulang_kata_sandi') {
            Assert::assertNotNull($this->tokenAturUlang, 'Token reset kata sandi harus berasal dari notifikasi surel.');
            Assert::assertNotNull($this->pemohon);
            $url = route($tujuan, [
                'token' => $this->tokenAturUlang,
                'email' => $this->pemohon->email,
            ]);
            $this->isianForm = [
                'token' => $this->tokenAturUlang,
                'email' => $this->pemohon->email,
            ];
        } else {
            $url = route($tujuan);
        }

        $this->kirimRequest('GET', $url);
    }

    private function kirimForm(string $tombol): void
    {
        $tombol = mb_strtolower($tombol);

        if ($this->halaman === 'masuk' || $tombol === 'masuk') {
            $pengguna = Pengguna::cariDenganNikAtauNip((string) ($this->isianForm['identitas'] ?? ''));
            $this->kirimRequest('POST', route('masuk.coba'), $this->isianForm);

            if ($this->response->headers->get('Location') === route('dashboard')) {
                $this->pemohon = $pengguna;
                $this->kirimDenganAutentikasi = true;
            }

            return;
        }

        if ($this->halaman === 'registrasi' || $tombol === 'daftar') {
            $this->kirimRequest('POST', route('registrasi.simpan'), $this->isianForm);

            return;
        }

        if ($this->halaman === 'lupa_kata_sandi' || $tombol === 'kirim') {
            $this->kirimRequest('POST', route('lupa_kata_sandi.kirim'), $this->isianForm);

            return;
        }

        if ($this->halaman === 'atur_ulang_kata_sandi' || $tombol === 'simpan') {
            $this->kirimRequest('POST', route('atur_ulang_kata_sandi.simpan'), $this->isianForm);

            return;
        }

        if (array_key_exists('kata_sandi_baru', $this->isianForm)
            || array_key_exists('kata_sandi_lama', $this->isianForm)) {
            $this->kirimRequest('PUT', route('profil.kata_sandi'), $this->isianForm);

            return;
        }

        $this->kirimRequest('PUT', route('profil.simpan'), $this->isianForm);
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

        $this->kirimDenganAutentikasi = true;
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

    /*
    |--------------------------------------------------------------------------
    | Login dan registrasi
    |--------------------------------------------------------------------------
    */

    #[Given('/^saya melihat halaman "([^"]+)"$/')]
    public function sayaMelihatHalaman(string $nama): void
    {
        $halaman = mb_strtolower($nama);
        $route = match ($halaman) {
            'masuk' => 'masuk',
            'registrasi' => 'registrasi',
            'lupa kata sandi' => 'lupa_kata_sandi',
            'kata sandi baru' => 'atur_ulang_kata_sandi',
            default => throw new InvalidArgumentException("Halaman BDD tidak dikenal: {$nama}"),
        };

        if ($this->response !== null && $this->response->headers->has('Location')) {
            if ($route === 'atur_ulang_kata_sandi') {
                Assert::assertStringContainsString(
                    '/atur-ulang-kata-sandi/',
                    $this->response->headers->get('Location')
                );
            } else {
                Assert::assertSame(route($route), $this->response->headers->get('Location'));
            }
        }

        if (mb_strtolower($nama) === 'masuk') {
            if (Pengguna::cariByNik('9999990101900001') === null) {
                [$this->pemohon] = $this->buatAkunDemo();
            } else {
                $this->pemohon = Pengguna::cariByNik('9999990101900001');
            }
        }

        $this->bukaHalaman($nama);
        Assert::assertSame(200, $this->response->getStatusCode());

        $judul = match ($route) {
            'masuk' => 'Masuk ke Akun Anda',
            'registrasi' => 'Registrasi ke Akun Pemohon',
            'lupa_kata_sandi' => 'Lupa Kata Sandi',
            'atur_ulang_kata_sandi' => 'Kata Sandi Baru',
        };
        Assert::assertStringContainsString($judul, $this->response->getContent());
    }

    #[When('/^saya mengisi "([^"]*)" pada kolom "([^"]+)"$/')]
    public function sayaMengisiKolom(string $nilai, string $kolom): void
    {
        $this->isianForm[$this->namaKolom($kolom)] = $nilai;
    }

    #[When('/^saya membiarkan kolom "([^"]+)" kosong$/')]
    public function sayaMembiarkanKolomKosong(string $kolom): void
    {
        $this->isianForm[$this->namaKolom($kolom)] = '';
    }

    #[When('/^saya mencentang opsi "Ingat Saya"$/')]
    public function sayaMencentangIngatSaya(): void
    {
        $this->isianForm['ingat_saya'] = '1';
    }

    #[When('/^saya menekan tombol "([^"]+)"$/')]
    public function sayaMenekanTombol(string $tombol): void
    {
        $this->kirimForm($tombol);
    }

    #[When('/^saya menekan tautan "Belum punya akun\\? Daftar di sini"$/')]
    public function sayaMenekanTautanRegistrasi(): void
    {
        $this->bukaHalaman('Registrasi');
    }

    #[When('/^saya menekan tautan "Sudah punya akun\\? Masuk di sini"$/')]
    public function sayaMenekanTautanMasuk(): void
    {
        $this->bukaHalaman('Masuk');
    }

    #[When('/^saya menekan tautan "Lupa Kata Sandi\\?"$/')]
    public function sayaMenekanTautanLupaKataSandi(): void
    {
        Assert::assertNotNull($this->pemohon);
        Notification::fake();
        $this->bukaHalaman('Lupa Kata Sandi');
    }

    #[When('/^saya membuka tautan pengaturan ulang dari surel$/')]
    public function sayaMembukaTautanAturUlangDariSurel(): void
    {
        Assert::assertNotNull($this->pemohon);
        Assert::assertNotNull($this->tokenAturUlang);
        $this->bukaHalaman('Kata Sandi Baru');
    }

    #[Then('/^tautan pengaturan ulang dikirim ke surel akun$/')]
    public function tautanPengaturanUlangDikirim(): void
    {
        Assert::assertNotNull($this->pemohon);
        Notification::assertSentTo($this->pemohon, TautanAturUlangKataSandi::class);
        $notifikasi = Notification::sent($this->pemohon, TautanAturUlangKataSandi::class)->first();
        Assert::assertNotNull($notifikasi);
        $this->tokenAturUlang = $notifikasi->token;
    }

    #[Then('/^kata sandi baru dapat digunakan untuk masuk$/')]
    public function kataSandiBaruDapatDigunakan(): void
    {
        Assert::assertNotNull($this->pemohon);
        $this->isianForm = [
            'identitas' => $this->pemohon->nik,
            'kata_sandi' => 'Baru12345',
        ];
        $this->kirimForm('Masuk');
        Assert::assertSame(route('dashboard'), $this->response->headers->get('Location'));
    }

    #[When('/^saya menekan ikon "tampilkan kata sandi"$/')]
    public function sayaMenekanIkonTampilkanKataSandi(): void
    {
        Assert::assertNotNull($this->response);
        $isi = $this->response->getContent();
        Assert::assertStringContainsString('data-tampilkan-sandi="kata_sandi"', $isi);
        Assert::assertStringContainsString('id="kata_sandi" name="kata_sandi" type="password"', $isi);
        Assert::assertStringContainsString(
            "kolom.type === 'password' ? 'text' : 'password'",
            $isi
        );
    }

    #[Then('/^kontrol tampilkan kata sandi terhubung ke kolom kata sandi$/')]
    public function kontrolTampilkanKataSandiTerhubung(): void
    {
        Assert::assertNotNull($this->response);
        Assert::assertStringContainsString('data-tampilkan-sandi="kata_sandi"', $this->response->getContent());
    }

    #[Given('/^akun dengan NIK "([^"]+)" sudah ada$/')]
    public function akunDenganNikSudahAda(string $nik): void
    {
        Pengguna::factory()->pemohon()->create([
            'nik' => $nik,
            'email' => 'akun-'.substr($nik, -6).'@example.test',
            'kata_sandi' => 'password123',
        ]);
    }

    #[Given('/^akun dengan surel "([^"]+)" sudah ada$/')]
    public function akunDenganSurelSudahAda(string $email): void
    {
        Pengguna::factory()->pemohon()->create([
            'nik' => '9999990101900001',
            'email' => $email,
            'kata_sandi' => 'password123',
        ]);
    }

    #[Then('/^saya diarahkan ke dashboard (Pemohon|Verifikator|Pejabat)$/')]
    public function sayaDiarahkanKeDashboard(string $peran): void
    {
        Assert::assertSame(route('dashboard'), $this->response->headers->get('Location'));
        $this->kirimRequest('GET', route('dashboard'));

        if ($this->response->headers->has('Location')) {
            $this->kirimRequest('GET', $this->response->headers->get('Location'));
        }

        $label = match ($peran) {
            'Pemohon' => 'Dashboard Pemohon',
            'Verifikator' => 'Dashboard Verifikator',
            'Pejabat' => 'Dashboard Pejabat Penanda Tangan',
        };
        Assert::assertSame(200, $this->response->getStatusCode());
        Assert::assertStringContainsString($label, $this->response->getContent());
    }

    #[Then('/^sesi saya diingat pada perangkat tersebut$/')]
    public function sesiSayaDiingat(): void
    {
        $adaKukiIngat = collect($this->response->headers->getCookies())
            ->contains(fn (Cookie $kuki) => str_starts_with($kuki->getName(), 'remember_web_'));
        Assert::assertTrue($adaKukiIngat, 'Login dengan Ingat Saya harus mengirim cookie remember-me.');
    }

    #[Then('/^saya melihat notifikasi "([^"]+)"$/')]
    public function sayaMelihatNotifikasi(string $pesan): void
    {
        Assert::assertSame($pesan, $this->app['session.store']->get('sukses'));
    }

    #[Then('/^saya melihat pesan galat "([^"]+)"$/')]
    public function sayaMelihatPesanGalat(string $pesan): void
    {
        Assert::assertStringContainsString($pesan, $this->pesanGalat());
    }

    #[Then('/^saya melihat pesan galat di bawah kolom "([^"]+)" "([^"]+)"$/')]
    public function sayaMelihatPesanGalatDiKolom(string $kolom, string $pesan): void
    {
        Assert::assertStringContainsString($pesan, $this->pesanGalat());
    }

    #[Then('/^kolom yang kosong ditandai$/')]
    public function kolomYangKosongDitandai(): void
    {
        $isi = $this->isiValidasi;

        if ($isi === '' && $this->response !== null && $this->response->headers->has('Location')) {
            $this->kirimRequest('GET', $this->response->headers->get('Location'));
            $isi = $this->response->getContent();
        }

        Assert::assertStringContainsString('border-red-500', $isi);
    }

    #[Given('/^saya telah mencapai batas maksimum percobaan login gagal$/')]
    public function sayaTelahMencapaiBatasLoginGagal(): void
    {
        if (Pengguna::cariByNik('9999990101900001') === null) {
            $this->buatAkunDemo();
        }
        $this->isianForm = [
            'identitas' => '9999990101900001',
            'kata_sandi' => 'password124',
        ];

        for ($i = 0; $i < LayananPenguncian::MAKS_GAGAL_LOGIN - 1; $i++) {
            $this->kirimForm('Masuk');
        }
    }

    #[Given('/^akun NIK "([^"]+)" sedang terkunci$/')]
    public function akunNikSedangTerkunci(string $nik): void
    {
        if (Pengguna::cariByNik($nik) === null) {
            $this->buatAkunDemo();
        }
        $this->bukaHalaman('Masuk');
        LayananPenguncian::kunci('login:'.strtolower($nik));
    }

    /*
    |--------------------------------------------------------------------------
    | Pengelolaan profil
    |--------------------------------------------------------------------------
    */

    #[Given('/^saya sudah masuk sebagai "([^"]+)"$/')]
    public function sayaSudahMasukSebagai(string $peran): void
    {
        [$pemohon, $verifikator, $pejabat] = $this->buatAkunDemo();
        $this->pemohon = match ($peran) {
            'Pemohon' => $pemohon,
            'Verifikator' => $verifikator,
            'Pejabat' => $pejabat,
            default => throw new InvalidArgumentException("Peran BDD tidak dikenal: {$peran}"),
        };
        $this->emailAwal = $this->pemohon->email;
        $this->teleponAwal = $this->pemohon->no_telepon;
        $this->kataSandiAwal = $this->pemohon->kata_sandi;
        $this->kirimDenganAutentikasi = true;
        Auth::login($this->pemohon);
    }

    #[When('/^saya membuka menu "Kelola Profil"$/')]
    public function sayaMembukaMenuKelolaProfil(): void
    {
        Assert::assertNotNull($this->pemohon);
        $this->kirimRequest('GET', route('profil.tampilkan'));
        Assert::assertSame(200, $this->response->getStatusCode());
    }

    #[Then('/^saya melihat bagian "Foto Profil", "Data Diri", dan "Pengaturan Keamanan"$/')]
    public function sayaMelihatBagianProfil(): void
    {
        $isi = $this->response->getContent();
        foreach (['Foto Profil', 'Data Diri', 'Pengaturan Keamanan'] as $bagian) {
            Assert::assertStringContainsString($bagian, $isi);
        }
    }

    #[Then('/^saya melihat NIK saya tetapi tidak dapat mengubahnya$/')]
    public function sayaMelihatNikReadonly(): void
    {
        Assert::assertNotNull($this->pemohon);
        Assert::assertStringContainsString((string) $this->pemohon->nik, $this->response->getContent());
        Assert::assertStringContainsString('readonly', $this->response->getContent());
    }

    #[When('/^saya mengubah "Nama Lengkap", "Alamat Surel", "Nomor Telepon", dan "Alamat"$/')]
    public function sayaMengubahDataDiri(): void
    {
        $this->isianForm = [
            'nama_lengkap' => 'Nama Baru BDD',
            'email' => 'nama.baru.bdd@example.test',
            'no_telepon' => '081234567899',
            'alamat' => 'Jalan Pengujian BDD 1',
        ];
    }

    #[When('/^saya mengisi "Alamat Surel" dengan "([^"]+)"$/')]
    public function sayaMengisiSurel(string $email): void
    {
        $this->isianForm['email'] = $email;
    }

    #[When('/^saya mengisi "Nomor Telepon" dengan "([^"]+)"$/')]
    public function sayaMengisiTelepon(string $telepon): void
    {
        $this->isianForm['no_telepon'] = $telepon;
    }

    #[Given('/^sudah ada pengguna lain dengan email atau nomor telepon tertentu$/')]
    public function sudahAdaPenggunaLainDenganKontak(): void
    {
        $this->penggunaLain = Pengguna::factory()->pemohon()->create([
            'email' => 'kontak-terpakai@example.test',
            'no_telepon' => '081299988877',
        ]);
    }

    #[When('/^saya mengisi data diri dengan email atau nomor telepon milik pengguna lain$/')]
    public function sayaMengisiKontakPenggunaLain(): void
    {
        Assert::assertNotNull($this->penggunaLain);
        $this->isianForm = [
            'nama_lengkap' => $this->pemohon->nama_lengkap,
            'email' => $this->penggunaLain->email,
            'no_telepon' => $this->penggunaLain->no_telepon,
        ];
    }

    #[When('/^saya mengunggah berkas yang bukan JPG\\/PNG atau lebih dari 2 MB$/')]
    public function sayaMengunggahFotoTidakValid(): void
    {
        Storage::fake('local');
        $this->fotoLama = 'foto_profil/lama.jpg';
        Storage::disk('local')->put($this->fotoLama, 'foto lama');
        $this->pemohon->update(['path_foto' => $this->fotoLama]);
        $this->kirimRequest('POST', route('profil.foto'), [
            'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]);
    }

    #[When('/^saya mengunggah foto berformat JPG atau PNG maksimal 2 MB$/')]
    public function sayaMengunggahFotoValid(): void
    {
        Storage::fake('local');
        $this->fotoLama = 'foto_profil/lama.jpg';
        Storage::disk('local')->put($this->fotoLama, 'foto lama');
        $this->pemohon->update(['path_foto' => $this->fotoLama]);
        $isi = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        $this->kirimRequest('POST', route('profil.foto'), [
            'foto' => UploadedFile::fake()->createWithContent('foto.png', $isi),
        ]);
    }

    #[Given('/^saya berada pada bagian "Pengaturan Keamanan"$/')]
    public function sayaBeradaPadaBagianKeamanan(): void
    {
        $this->sayaMembukaMenuKelolaProfil();
        Assert::assertStringContainsString('Pengaturan Keamanan', $this->response->getContent());
    }

    #[When('/^saya mengisi "Kata Sandi Lama" dengan kata sandi yang benar$/')]
    public function sayaMengisiKataSandiLamaBenar(): void
    {
        $this->isianForm['kata_sandi_lama'] = 'password123';
    }

    #[When('/^saya mengisi "Kata Sandi Lama" dengan kata sandi yang salah$/')]
    public function sayaMengisiKataSandiLamaSalah(): void
    {
        $this->isianForm['kata_sandi_lama'] = 'kataSandiSalah';
    }

    #[When('/^saya mengisi "Kata Sandi Baru" dengan "([^"]+)"$/')]
    public function sayaMengisiKataSandiBaru(string $kataSandi): void
    {
        $this->isianForm['kata_sandi_baru'] = $kataSandi;
    }

    #[When('/^saya mengisi "Konfirmasi Kata Sandi" dengan "([^"]+)"$/')]
    public function sayaMengisiKonfirmasiKataSandi(string $kataSandi): void
    {
        $this->isianForm['konfirmasi_kata_sandi'] = $kataSandi;
    }

    #[Then('/^data saya tersimpan di database$/')]
    public function dataSayaTersimpan(): void
    {
        $segar = $this->pemohon->fresh();
        Assert::assertSame('Nama Baru BDD', $segar->nama_lengkap);
        Assert::assertSame('nama.baru.bdd@example.test', $segar->email);
        Assert::assertSame('081234567899', $segar->no_telepon);
        Assert::assertSame('Jalan Pengujian BDD 1', $segar->alamat);
    }

    #[Then('/^saya melihat pesan kesalahan pada kolom "([^"]+)"$/')]
    public function sayaMelihatKesalahanPadaKolom(string $kolom): void
    {
        $pesanKolom = match ($kolom) {
            'Alamat Surel' => 'Format alamat surel tidak valid',
            'Nomor Telepon' => 'Format nomor telepon tidak valid',
            default => throw new InvalidArgumentException("Kolom validasi BDD tidak dikenal: {$kolom}"),
        };
        Assert::assertStringContainsString($pesanKolom, $this->pesanGalat());
    }

    #[Then('/^data saya tidak berubah$/')]
    public function dataSayaTidakBerubah(): void
    {
        $segar = $this->pemohon->fresh();
        Assert::assertSame($this->emailAwal, $segar->email);
        Assert::assertSame($this->teleponAwal, $segar->no_telepon);
    }

    #[Then('/^saya melihat pesan "([^"]+)"$/')]
    public function sayaMelihatPesan(string $pesan): void
    {
        Assert::assertStringContainsString($pesan, $this->pesanGalat());
    }

    #[Then('/^foto profil lama saya tetap tersimpan$/')]
    public function fotoProfilLamaTetapTersimpan(): void
    {
        Assert::assertSame($this->fotoLama, $this->pemohon->fresh()->path_foto);
        Assert::assertTrue(Storage::disk('local')->exists($this->fotoLama));
    }

    #[Then('/^foto profil baru saya tersimpan$/')]
    public function fotoProfilBaruTersimpan(): void
    {
        $path = $this->pemohon->fresh()->path_foto;
        Assert::assertNotEmpty($path);
        Assert::assertNotSame($this->fotoLama, $path);
        Assert::assertTrue(Storage::disk('local')->exists($path));
    }

    #[Then('/^foto profil lama saya dihapus$/')]
    public function fotoProfilLamaDihapus(): void
    {
        Assert::assertFalse(Storage::disk('local')->exists($this->fotoLama));
    }

    #[Then('/^saya melihat pesan "([^"]+)" di bawah kolom "([^"]+)"$/')]
    public function sayaMelihatPesanDiBawahKolom(string $pesan, string $kolom): void
    {
        Assert::assertStringContainsString($pesan, $this->pesanGalat());
    }

    #[Then('/^kata sandi saya tersimpan dalam bentuk hash$/')]
    public function kataSandiTersimpanDalamHash(): void
    {
        $hash = $this->pemohon->fresh()->kata_sandi;
        Assert::assertTrue(Hash::check('sandiBaru1', $hash));
        Assert::assertFalse(Hash::check('password123', $hash));
    }

    #[Then('/^kata sandi saya tidak berubah$/')]
    public function kataSandiSayaTidakBerubah(): void
    {
        Assert::assertSame($this->kataSandiAwal, $this->pemohon->fresh()->kata_sandi);
    }

    #[When('/^saya mengirim perubahan profil disertai NIK yang berbeda$/')]
    public function sayaMengirimPerubahanProfilDenganNikBerbeda(): void
    {
        $this->kirimRequest('PUT', route('profil.simpan'), [
            'nama_lengkap' => $this->pemohon->nama_lengkap,
            'email' => $this->pemohon->email,
            'nik' => '1111111111111111',
        ]);
    }

    #[Then('/^NIK saya tetap seperti semula$/')]
    public function nikSayaTetapSama(): void
    {
        Assert::assertSame('9999990101900001', $this->pemohon->fresh()->nik);
    }

    #[Given('/^ada pengguna lain dengan profil tertentu$/')]
    public function adaPenggunaLainDenganProfil(): void
    {
        $this->penggunaLain = Pengguna::factory()->pemohon()->create([
            'nama_lengkap' => 'Pemohon Lain BDD',
            'email' => 'pemohon-lain@example.test',
        ]);
    }

    #[When('/^saya memperbarui profil saya$/')]
    public function sayaMemperbaruiProfilSaya(): void
    {
        $this->isianForm = [
            'nama_lengkap' => 'Pemohon Diubah BDD',
            'email' => 'pemohon-diubah@example.test',
        ];
        $this->kirimForm('Simpan Perubahan');
    }

    #[Then('/^hanya profil saya yang berubah$/')]
    public function hanyaProfilSayaYangBerubah(): void
    {
        Assert::assertSame('Pemohon Diubah BDD', $this->pemohon->fresh()->nama_lengkap);
        Assert::assertSame('pemohon-diubah@example.test', $this->pemohon->fresh()->email);
    }

    #[Then('/^profil pengguna lain tidak berubah$/')]
    public function profilPenggunaLainTidakBerubah(): void
    {
        Assert::assertNotNull($this->penggunaLain);
        Assert::assertSame('Pemohon Lain BDD', $this->penggunaLain->fresh()->nama_lengkap);
        Assert::assertSame('pemohon-lain@example.test', $this->penggunaLain->fresh()->email);
    }
}
