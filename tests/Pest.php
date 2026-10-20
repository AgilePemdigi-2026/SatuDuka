<?php

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Fungsi Bantu Pengujian
|--------------------------------------------------------------------------
*/

/**
 * Kredensial demo SATUDUKA (docs/DATABASE.md): kata sandi seluruh akun adalah
 * `password123`.
 */
function buatAkunDemo(): array
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

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
