<?php

namespace App\Services\Ai;

use App\Models\BerkasPendukung;
use App\Support\MaskerNik;
use App\Support\PenyaringTeksDokumen;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Implementasi penyedia nyata melalui DeepSeek Chat API (kompatibel OpenAI).
 *
 * DeepSeek hanya menerima teks, jadi dokumen selalu diubah menjadi teks lebih
 * dahulu (PDF lewat parser lokal, gambar lewat tesseract) sebelum dikirim.
 * NIK pada prompt tetap dimasker; isi dokumen sumber dikirim apa adanya
 * karena NIK di dalamnya merupakan data yang diminta untuk dibaca.
 */
class DeepSeekService implements AIService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly int $timeout,
    ) {}

    public function ekstrakSuratKematian(BerkasPendukung $berkas): array
    {
        if (trim($this->apiKey) === '') {
            throw new AiTidakTersedia('Kunci layanan AI belum dikonfigurasi.', penyebab: 'kunci_kosong');
        }

        if (! Storage::disk('local')->exists($berkas->path_file)) {
            throw new AiTidakTersedia('Berkas tidak ditemukan.', penyebab: 'berkas_tidak_ditemukan');
        }

        $isi = (string) Storage::disk('local')->get($berkas->path_file);

        $teks = PenyaringTeksDokumen::teks($berkas->path_file, $isi);

        if ($teks === null) {
            throw new AiTidakTersedia(
                'Dokumen tidak dapat dibaca menjadi teks; gunakan salinan yang lebih jelas.',
                penyebab: 'dokumen_tidak_terbaca',
            );
        }

        // Hanya instruksi yang dimasker; teks dokumen dikirim apa adanya
        // karena NIK di dalamnya adalah data yang diminta untuk dibaca.
        $prompt = MaskerNik::terapkan(
            'Anda membantu membaca surat keterangan kematian. Ekstrak hanya data yang terlihat '
            .'pada dokumen berikut: nama almarhum, NIK 16 digit, tanggal kematian (format '
            .'YYYY-MM-DD), dan tempat kematian. Jangan menebak. Jika suatu kolom tidak terbaca, '
            .'isi string kosong dan keyakinan 0. Isi keyakinan tiap kolom antara 0 dan 1. '
            .'Balas HANYA dengan JSON valid.',
        )."\n\nISI DOKUMEN:\n".$teks;

        $data = $this->panggilDeepSeek($prompt, $this->skemaRingkasPrompt());

        return $this->susunHasil($data);
    }

    public function ringkasKetidaksesuaian(array $perbandingan): array
    {
        if (trim($this->apiKey) === '') {
            throw new AiTidakTersedia('Kunci layanan AI belum dikonfigurasi.', penyebab: 'kunci_kosong');
        }

        $konteks = json_encode($perbandingan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Data perbandingan tidak mengandung NIK utuh (sudah dimasker/hashing
        // oleh NikAnalisis), tetapi dimasker sekali lagi sebagai pertahanan.
        $konteks = MaskerNik::terapkan($konteks === false ? '{}' : $konteks);

        $prompt = 'Anda membantu verifikator dokumen kematian. '
            .'Berikut data perbandingan isian pemohon dengan hasil ekstraksi dokumen yang diunggah dalam JSON. '
            .'NIK pada data sudah dimasker. '
            .'Buat ringkasan singkat dalam bahasa Indonesia dan saran tindakan pemeriksaan. '
            .'Jangan menyetujui, menolak, atau mengubah status pengajuan. '
            .'Balas HANYA dengan JSON valid berformat {"ringkasan": "...", "saran": ["..."]}. '
            .'Data: '.$konteks;

        $data = $this->panggilDeepSeek($prompt);

        $ringkasan = is_string($data['ringkasan'] ?? null) ? trim($data['ringkasan']) : '';
        $saran = $this->saranValid($data['saran'] ?? null);

        if ($ringkasan === '') {
            throw new AiTidakTersedia('Hasil ringkasan AI tidak dapat dibaca.', penyebab: 'respons_kosong');
        }

        return [
            'berhasil' => true,
            'ringkasan' => $ringkasan,
            'saran' => $saran,
            'pesan' => null,
        ];
    }

    public function namaModel(): string
    {
        return $this->model;
    }

    /**
     * Panggil endpoint chat/completions dengan mode JSON dan urai jawabannya.
     *
     * @return array<string, mixed>
     */
    private function panggilDeepSeek(string $prompt, ?string $penambah = null): array
    {
        $badan = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt.($penambah ?? '')],
            ],
            'response_format' => ['type' => 'json_object'],
            'stream' => false,
        ];

        $kirim = fn (array $isi): Response => Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout(max(1, $this->timeout))
            ->connectTimeout(max(5, (int) config('ai.connect_timeout', 30)))
            ->post(rtrim($this->baseUrl, '/').'/chat/completions', $isi);

        try {
            $respon = $kirim($badan);

            // Sebagian gateway OpenAI-kompatibel menolak `response_format` dengan
            // 400/422. Hanya status itu yang diulang tanpa mode JSON; 401/429/5xx
            // tidak diulang agar tidak menggandakan biaya dan waktu tunggu.
            if (in_array($respon->status(), [400, 422], true)) {
                $badanTanpaJson = $badan;
                unset($badanTanpaJson['response_format']);

                $respon = $kirim($badanTanpaJson);
            }
        } catch (ConnectionException) {
            throw new AiTidakTersedia('Layanan AI tidak merespons tepat waktu.', penyebab: 'timeout');
        }

        // 429/503 berarti penyedia sibuk, bukan dokumen gagal dianalisis.
        $batasLaju = AiRateLimit::dari($respon);

        if ($batasLaju !== null) {
            throw $batasLaju;
        }

        if ($respon->failed()) {
            throw new AiTidakTersedia(
                'Layanan AI tidak dapat dihubungi.',
                statusHttp: $respon->status(),
                penyebab: 'http',
            );
        }

        $teks = data_get($respon->json(), 'choices.0.message.content');

        if (! is_string($teks) || trim($teks) === '') {
            throw new AiTidakTersedia('Hasil layanan AI tidak dapat dibaca.', penyebab: 'respons_kosong');
        }

        // Sebagian model membungkus JSON dengan pagar markdown; buang pagar itu.
        $teks = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($teks)) ?? $teks;
        $data = json_decode(trim($teks), true);

        if (! is_array($data)) {
            throw new AiTidakTersedia('Hasil layanan AI tidak dapat dibaca.', penyebab: 'respons_tidak_terbaca');
        }

        return $data;
    }

    /**
     * Kunci keluaran ekstraksi diingatkan sekali lagi agar model tetap patuh.
     */
    private function skemaRingkasPrompt(): string
    {
        return ' Format JSON yang diminta: '
            .'{"nama_almarhum": "string", "nik": "string", "tanggal_kematian": "string", '
            .'"tempat_kematian": "string", "keyakinan": {"nama_almarhum": 0..1, "nik": 0..1, '
            .'"tanggal_kematian": 0..1, "tempat_kematian": 0..1}}.';
    }

    /**
     * @return list<string>
     */
    private function saranValid(mixed $saran): array
    {
        if (! is_array($saran)) {
            return [];
        }

        $valid = [];

        foreach ($saran as $item) {
            if (is_string($item) && trim($item) !== '') {
                $valid[] = trim($item);
            }
        }

        return array_values($valid);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{berhasil: bool, kolom: array<string, array{nilai: string|null, keyakinan: float}>, skor: float, pesan: string|null}
     */
    private function susunHasil(array $data): array
    {
        $kunci = ['nama_almarhum', 'nik', 'tanggal_kematian', 'tempat_kematian'];
        $kolom = [];
        $total = 0.0;
        $jumlah = 0;

        foreach ($kunci as $nama) {
            $nilai = isset($data[$nama]) && is_string($data[$nama]) ? trim($data[$nama]) : '';
            $keyakinan = (float) data_get($data, 'keyakinan.'.$nama, 0);
            $keyakinan = max(0.0, min(1.0, $keyakinan));

            $kolom[$nama] = [
                'nilai' => $nilai === '' ? null : $nilai,
                'keyakinan' => round($keyakinan, 4),
            ];

            if ($nilai !== '') {
                $total += $keyakinan;
                $jumlah++;
            }
        }

        return [
            'berhasil' => true,
            'kolom' => $kolom,
            'skor' => $jumlah === 0 ? 0.0 : round($total / $jumlah, 4),
            'pesan' => $jumlah === 0 ? 'Dokumen tidak terbaca oleh layanan AI.' : null,
        ];
    }
}
