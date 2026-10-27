<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Layanan AI tidak dapat dihubungi atau tidak merespons tepat waktu.
 *
 * Kondisi ini tidak boleh menolak pengajuan secara otomatis.
 */
class AiTidakTersedia extends RuntimeException {}
