<?php

namespace App\Support;

use Illuminate\Log\Logger as LoggerLaravel;
use Monolog\Logger;

/**
 * Memasang {@see ProsesorMaskerNik} pada channel log yang memakai tap.
 *
 * Laravel menyerahkan `Illuminate\Log\Logger` (pembungkus), bukan Monolog
 * langsung, sehingga logger Monolog diambil lewat `getLogger()`.
 */
final class TapMaskerNik
{
    public function __invoke(LoggerLaravel|Logger $logger): void
    {
        if ($logger instanceof LoggerLaravel) {
            $logger = $logger->getLogger();
        }

        if ($logger instanceof Logger) {
            $logger->pushProcessor(new ProsesorMaskerNik);
        }
    }
}
