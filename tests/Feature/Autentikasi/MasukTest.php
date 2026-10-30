<?php

/**
 * Pengujian fitur Masuk (Login).
 *
 * Sumber skenario: SRS Bab VI Tabel 6.1.
 * Setiap blok pengujian memetakan satu skenario Gherkin.
 */

use App\Models\LogAudit;
use App\Models\Pengguna;
use App\Services\LayananPenguncian;
use Symfony\Component\HttpFoundation\Cookie;

const KATA_SANDI_DEMO = 'password123';

// Skenario: Berpindah ke halaman Registrasi dari halaman Masuk.
it('menampilkan halaman masuk dengan tautan ke registrasi', function () {
    $this->get(route('masuk'))
        ->assertOk()
        ->assertSee('Masuk ke Akun Anda')
        ->assertSee('NIK / NIP')
        ->assertSee('Lupa Kata Sandi?')
        ->assertSee(route('registrasi'));
});

// Skenario: Berhasil masuk dengan kredensial Pemohon yang valid (NIK 16 digit).
it('mengarahkan pemohon ke dashboard pemohon saat masuk berhasil', function () {
    [$pemohon] = buatAkunDemo();

    $respon = $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => KATA_SANDI_DEMO,
    ]);

    $respon->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($pemohon);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Pemohon');
});

// Skenario: Berhasil masuk dengan kredensial Verifikator yang valid (NIP 18 digit).
it('mengarahkan verifikator ke dashboard verifikator saat masuk dengan nip', function () {
    [, $verifikator] = buatAkunDemo();

    $respon = $this->post(route('masuk.coba'), [
        'identitas' => '999999991234567890',
        'kata_sandi' => KATA_SANDI_DEMO,
    ]);

    $respon->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($verifikator);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Verifikator');
});

// Skenario: Berhasil masuk dengan kredensial Pejabat yang valid (NIP 18 digit).
it('mengarahkan pejabat ke dashboard pejabat saat masuk dengan nip', function () {
    [,, $pejabat] = buatAkunDemo();

    $respon = $this->post(route('masuk.coba'), [
        'identitas' => '999999992345678901',
        'kata_sandi' => KATA_SANDI_DEMO,
    ]);

    $respon->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($pejabat);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Pejabat Penanda Tangan')
        ->assertSee('Anda masuk sebagai Pejabat');
});

// Skenario: Berhasil masuk dengan opsi Ingat Saya.
it('mengingat sesi ketika opsi ingat saya dicentang', function () {
    [$pemohon] = buatAkunDemo();

    $respon = $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => KATA_SANDI_DEMO,
        'ingat_saya' => '1',
    ]);

    $respon->assertRedirect(route('dashboard'));

    $adaKukiIngat = collect($respon->headers->getCookies())
        ->contains(fn (Cookie $kuki) => str_starts_with($kuki->getName(), 'remember_web_'));

    expect($adaKukiIngat)->toBeTrue();
});

// Skenario: Gagal masuk karena kata sandi salah.
it('menampilkan pesan kredensial salah ketika kata sandi salah', function () {
    [$pemohon] = buatAkunDemo();

    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => 'password124',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['identitas' => 'NIK/NIP atau kata sandi salah']);

    $this->assertGuest();
});

// Skenario: Gagal masuk karena NIK/NIP tidak valid.
it('menampilkan pesan kredensial salah ketika identitas tidak valid', function () {
    buatAkunDemo();

    $this->post(route('masuk.coba'), [
        'identitas' => '123456789012',
        'kata_sandi' => KATA_SANDI_DEMO,
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['identitas' => 'NIK/NIP atau kata sandi salah']);

    $this->assertGuest();
});

// Skenario: Gagal masuk karena kolom kosong.
it('menampilkan pesan kolom kosong dan menandai kolom tersebut', function () {
    buatAkunDemo();

    // Kunjungi halaman masuk dahulu agar redirect-back kembali ke halaman ini.
    $this->get(route('masuk'));

    $this->followRedirects($this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => '',
    ]))
        ->assertOk()
        ->assertSee('Harap isi semua field yang diperlukan')
        ->assertSee('aria-invalid="true"', false);

    $this->assertGuest();
});

// Skenario: Akun terkunci setelah terlalu banyak percobaan gagal.
it('mengunci akun selama 15 menit setelah batas percobaan gagal tercapai', function () {
    buatAkunDemo();

    $kunci = 'login:9999990101900001';

    // Empat percobaan gagal pertama masih menampilkan pesan kredensial salah.
    for ($i = 0; $i < LayananPenguncian::MAKS_GAGAL_LOGIN - 1; $i++) {
        $this->post(route('masuk.coba'), [
            'identitas' => '9999990101900001',
            'kata_sandi' => 'password124',
        ])->assertSessionHasErrors(['identitas' => 'NIK/NIP atau kata sandi salah']);
    }

    // Percobaan gagal kelima memicu penguncian.
    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => 'password124',
    ])->assertSessionHasErrors([
        'identitas' => 'Terlalu banyak percobaan gagal. Akun Anda dikunci sementara. Coba lagi dalam 15 menit',
    ]);

    expect(LayananPenguncian::terkunci($kunci))->toBeTrue()
        ->and(LayananPenguncian::sisaKunci($kunci))->toBeGreaterThan(0);

    // Percobaan berikutnya ditolak meskipun kata sandinya benar.
    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => KATA_SANDI_DEMO,
    ])->assertSessionHasErrors(['identitas' => 'Akun Anda dikunci sementara']);

    $this->assertGuest();

    // Penguncian dicatat ke log audit oleh LayananPenguncian.
    expect(LogAudit::where('aksi', 'mengunci_akses')->count())->toBe(1)
        ->and(LogAudit::where('aksi', 'masuk_gagal')->count())->toBe(5);
});

// Skenario: Gagal masuk saat akun terkunci.
it('menolak percobaan masuk saat akun sedang terkunci', function () {
    buatAkunDemo();

    $kunci = 'login:9999990101900001';
    LayananPenguncian::kunci($kunci);

    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => KATA_SANDI_DEMO,
    ])->assertSessionHasErrors(['identitas' => 'Akun Anda dikunci sementara']);

    $this->assertGuest();
});

// Aksi masuk yang berhasil wajib tercatat ke log_audit.
it('mencatat aksi masuk yang berhasil ke log audit', function () {
    [$pemohon] = buatAkunDemo();

    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => KATA_SANDI_DEMO,
    ])->assertRedirect(route('dashboard'));

    $log = LogAudit::where('aksi', 'masuk')->first();

    expect($log)->not->toBeNull()
        ->and($log->id_pengguna)->toBe($pemohon->id_pengguna)
        ->and($log->detail['peran'])->toBe('pemohon');
});

// Setelah masuk berhasil, hitungan kegagalan direset.
it('membuka kunci dan mereset hitungan gagal saat masuk berhasil', function () {
    [$pemohon] = buatAkunDemo();

    $kunci = 'login:9999990101900001';
    LayananPenguncian::catatGagal($kunci);
    LayananPenguncian::catatGagal($kunci);

    $this->post(route('masuk.coba'), [
        'identitas' => '9999990101900001',
        'kata_sandi' => KATA_SANDI_DEMO,
    ])->assertRedirect(route('dashboard'));

    expect(LayananPenguncian::jumlahGagal($kunci))->toBe(0)
        ->and(LayananPenguncian::terkunci($kunci))->toBeFalse();
});

// Pengguna yang sudah masuk tidak melihat halaman masuk lagi.
it('mengalihkan pengguna yang sudah masuk dari halaman masuk ke dashboard', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->get(route('masuk'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('info', 'Anda sudah masuk sebagai Pemohon Demo (Pemohon). Keluar terlebih dahulu untuk berganti akun.');
});

it('mengalihkan akun verifikator yang masih aktif dari halaman masuk', function () {
    [, $verifikator] = buatAkunDemo();

    $this->actingAs($verifikator)
        ->get(route('masuk'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(
            'info',
            'Anda sudah masuk sebagai Verifikator Demo (Verifikator). Keluar terlebih dahulu untuk berganti akun.',
        );

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Verifikator');
});

it('memberi tahu akun pejabat yang masih aktif saat membuka halaman registrasi', function () {
    [,, $pejabat] = buatAkunDemo();

    $this->actingAs($pejabat)
        ->followingRedirects()
        ->get(route('registrasi'))
        ->assertOk()
        ->assertSee('Anda sudah masuk sebagai Pejabat Demo (Pejabat).')
        ->assertSee('Keluar terlebih dahulu untuk berganti akun.')
        ->assertSee('Dashboard Pejabat Penanda Tangan');
});

// Keluar dari sistem mengakhiri sesi.
it('mengakhiri sesi ketika pengguna keluar', function () {
    [$pemohon] = buatAkunDemo();

    $this->actingAs($pemohon)
        ->post(route('keluar'))
        ->assertRedirect(route('masuk'));

    $this->assertGuest();
    expect(LogAudit::where('aksi', 'keluar')->where('id_pengguna', $pemohon->id_pengguna)->exists())->toBeTrue();
});
