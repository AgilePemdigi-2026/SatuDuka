<?php

namespace App\Services\Ai;

use App\Models\BerkasPendukung;
use App\Support\MaskerNik;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Implementasi penyedia nyata melalui Google Gemini API (generateContent).
 *
 * Dokumen dikirim sebagai `inline_data` (PDF, JPG, atau PNG) dalam format base64.
 * Data pemohon tidak dikirim; NIK yang mungkin muncul pada teks permintaan
 * tetap dimasker sebelum dikirim.
 */
class GeminiService implements AIService
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

        $isi = Storage::disk('local')->get($berkas->path_file);

        if ($isi === null || $isi === '') {
            throw new AiTidakTersedia('Berkas kosong.', penyebab: 'berkas_kosong');
        }

        $bagianDokumen = $this->bagianDokumen($berkas, $isi);
        $prompt = MaskerNik::terapkan(
            'Anda membantu membaca surat keterangan kematian. Ekstrak hanya data yang terlihat pada dokumen: '
            .'nama almarhum, NIK 16 digit, tanggal kematian (format YYYY-MM-DD), dan tempat kematian. '
            .'Jangan menebak. Jika suatu kolom tidak terbaca, isi string kosong dan keyakinan 0. '
            .'Isi keyakinan tiap kolom antara 0 dan 1.',
        );

        $data = $this->panggilGemini(
            parts: [
                ['text' => $prompt],
                $bagianDokumen,
            ],
            skema: $this->skema(),
        );

        return $this->susunHasil($data);
    }

    public function ringkasKetidaksesuaian(array $perbandingan): array
    {
        if (trim($this->apiKey) === '') {
            throw new AiTidakTersedia('Kunci layanan AI belum dikonfigurasi.', penyebab: 'kunci_kosong');
        }

        $konteks = json_encode($perbandingan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $konteks = MaskerNik::terapkan($konteks === false ? '{}' : $konteks);

        $prompt = 'Anda membantu verifikator dokumen kematian. '
            .'Berikut data perbandingan isian pemohon, hasil ekstraksi dokumen, dan data SIAK. '
            .'NIK pada data sudah dimasker. '
            .'Buat ringkasan singkat dalam bahasa Indonesia dan saran tindakan pemeriksaan. '
            .'Jangan menyetujui, menolak, atau mengubah status pengajuan. '
            .'Data: '.$konteks;

        $data = $this->panggilGemini(
            parts: [['text' => $prompt]],
            skema: $this->skemaRingkasan(),
        );

        $ringkasan = is_string($data['ringkasan'] ?? null) ? trim($data['ringkasan']) : '';
        $saran = [];

        if (is_array($data['saran'] ?? null)) {
            foreach ($data['saran'] as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $saran[] = trim($item);
                }
            }
        }

        if ($ringkasan === '') {
            throw new AiTidakTersedia('Hasil ringkasan AI tidak dapat dibaca.', penyebab: 'respons_kosong');
        }

        return [
            'berhasil' => true,
            'ringkasan' => $ringkasan,
            'saran' => array_values($saran),
            'pesan' => null,
        ];
    }

    public function namaModel(): string
    {
        return $this->model;
    }

    /**
     * Mengirim permintaan ke endpoint generateContent Gemini dan mengurai JSON keluaran.
     *
     * @param  list<array<string, mixed>>  $parts
     * @param  array<string, mixed>  $skema
     * @return array<string, mixed>
     */
    private function panggilGemini(array $parts, array $skema): array
    {
        $url = rtrim($this->baseUrl, '/').'/models/'.$this->model.':generateContent';

        try {
            $respon = Http::acceptJson()
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->timeout(max(1, $this->timeout))
                ->connectTimeout(max(5, (int) config('ai.connect_timeout', 30)))
                ->post($url, [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => $parts,
                    ]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $skema,
                    ],
                ]);
        } catch (ConnectionException) {
            throw new AiTidakTersedia('Layanan AI tidak merespons tepat waktu.', penyebab: 'timeout');
        }

        if ($respon->failed()) {
            throw new AiTidakTersedia(
                'Layanan AI tidak dapat dihubungi.',
                statusHttp: $respon->status(),
                penyebab: 'http',
            );
        }

        $teks = data_get($respon->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($teks) || trim($teks) === '') {
            throw new AiTidakTersedia('Hasil layanan AI tidak dapat dibaca.', penyebab: 'respons_kosong');
        }

        $data = json_decode($teks, true);

        if (! is_array($data)) {
            throw new AiTidakTersedia('Hasil layanan AI tidak dapat dibaca.', penyebab: 'respons_tidak_terbaca');
        }

        return $data;
    }

    /**
     * Bagian dokumen sesuai tipe berkas yang didukung.
     *
     * @return array{inline_data: array{mime_type: string, data: string}}
     */
    private function bagianDokumen(BerkasPendukung $berkas, string $isi): array
    {
        $ekstensi = strtolower(pathinfo($berkas->path_file, PATHINFO_EXTENSION));

        $mime = match ($ekstensi) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => throw new AiTidakTersedia(
                'Format dokumen tidak didukung layanan AI.',
                penyebab: 'format_tidak_didukung',
            ),
        };

        return [
            'inline_data' => [
                'mime_type' => $mime,
                'data' => base64_encode($isi),
            ],
        ];
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

    /**
     * Skema keluaran terstruktur untuk ekstraksi surat kematian.
     *
     * @return array<string, mixed>
     */
    private function skema(): array
    {
        $angka = ['type' => 'NUMBER'];
        $teks = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'nama_almarhum' => $teks,
                'nik' => $teks,
                'tanggal_kematian' => $teks,
                'tempat_kematian' => $teks,
                'keyakinan' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'nama_almarhum' => $angka,
                        'nik' => $angka,
                        'tanggal_kematian' => $angka,
                        'tempat_kematian' => $angka,
                    ],
                    'required' => ['nama_almarhum', 'nik', 'tanggal_kematian', 'tempat_kematian'],
                ],
            ],
            'required' => ['nama_almarhum', 'nik', 'tanggal_kematian', 'tempat_kematian', 'keyakinan'],
        ];
    }

    /**
     * Skema keluaran ringkasan ketidaksesuaian.
     *
     * @return array<string, mixed>
     */
    private function skemaRingkasan(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'ringkasan' => ['type' => 'STRING'],
                'saran' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['ringkasan', 'saran'],
        ];
    }
}
