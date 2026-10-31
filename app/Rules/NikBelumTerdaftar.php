<?php

namespace App\Rules;

use App\Models\Pengguna;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Memastikan NIK belum terdaftar.
 *
 * Pengganti aturan `unique:pengguna,nik` karena kolom `nik` kini berisi
 * ciphertext acak; keunikan diperiksa lewat blind index `nik_hash`.
 */
class NikBelumTerdaftar implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (Pengguna::cariDenganNik($value) !== null) {
            $fail('NIK sudah terdaftar');
        }
    }
}
