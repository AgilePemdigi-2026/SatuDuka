<?php

namespace App\Support;

use Monolog\LogRecord;

/**
 * Processor Monolog yang memasker NIK pada pesan dan konteks log.
 *
 * Dipasang pada setiap channel agar NIK utuh tidak pernah tertulis ke
 * `storage/logs`, termasuk ketika pesan berasal dari QueryException (binding
 * SQL) atau array konteks. Prosesor juga memeriksa `previous` exception karena
 * Laravel menaruh exception di konteks.
 */
final class ProsesorMaskerNik
{
    public function __invoke(LogRecord $rekaman): LogRecord
    {
        $pesan = MaskerNik::terapkan((string) $rekaman->message);
        $konteks = MaskerNik::terapkanArray($rekaman->context);

        return $rekaman->with(
            message: $pesan,
            context: $this->maskerKonteksKhusus($konteks),
        );
    }

    /**
     * Tindak lanjut: telusuri string di dalam objek konteks seperti exception.
     *
     * @param  array<mixed>  $konteks
     * @return array<mixed>
     */
    private function maskerKonteksKhusus(array $konteks): array
    {
        if (isset($konteks['exception']) && $konteks['exception'] instanceof \Throwable) {
            // Pesan exception diganti teks yang sudah dimasker; objek aslinya
            // tidak disimpan ke log agar stack trace mentah tidak lolos.
            $konteks['exception'] = MaskerNik::terapkan(
                (string) $konteks['exception']->getMessage(),
            );
        }

        return $konteks;
    }
}
