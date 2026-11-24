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
 * Implementasi penyedia nyata melalui OpenAI Responses API.
 *
 * Dokumen dikirim sebagai `input_file` (PDF) atau `input_image` (JPG/PNG)
 * sesuai dokumentasi resmi. Data pemohon tidak dikirim; NIK yang mungkin
 * muncul pada teks permintaan tetap dimasker sebelum dikirim.
 */
class OpenAiService implements AIService
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

        try {
            $respon = $this->kirimEkstraksi($prompt, $bagianDokumen);
        } catch (ConnectionException) {
            throw new AiTidakTersedia('Layanan AI tidak merespons tepat waktu.', penyebab: 'timeout');
        }

        // 429/503: penyedia sibuk. Jangan coba bentuk permintaan lain, karena
        // itu hanya menambah permintaan tanpa peluang berhasil.
        $batasLaju = AiRateLimit::dari($respon);

        if ($batasLaju !== null) {
            throw $batasLaju;
        }

        if ($respon->failed()) {
            $teksDokumen = PenyaringTeksDokumen::teks($berkas->path_file, (string) $isi);

            if ($teksDokumen !== null) {
                try {
                    $respon = $this->kirimEkstraksi($prompt, [
                        'type' => 'input_text',
                        'text' => "Isi dokumen:\n".$teksDokumen,
                    ]);
                } catch (ConnectionException) {
                    throw new AiTidakTersedia('Layanan AI tidak merespons tepat waktu.', penyebab: 'timeout');
                }
            }
        }

        if ($respon->failed()) {
            $batasLaju = AiRateLimit::dari($respon);

            if ($batasLaju !== null) {
                throw $batasLaju;
            }

            throw new AiTidakTersedia(
                'Layanan AI tidak dapat dihubungi.',
                statusHttp: $respon->status(),
                penyebab: 'http',
            );
        }

        $teks = $this->teksKeluaran($respon->json());
        $teks = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($teks)) ?? $teks;
        $data = json_decode(trim($teks), true);

        if (! is_array($data)) {
            throw new AiTidakTersedia('Hasil layanan AI tidak dapat dibaca.', penyebab: 'respons_tidak_terbaca');
        }

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

        try {
            $respon = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(max(1, $this->timeout))
                ->connectTimeout(max(5, (int) config('ai.connect_timeout', 30)))
                ->post(rtrim($this->baseUrl, '/').'/responses', [
                    'model' => $this->model,
                    'input' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => $prompt],
                        ],
                    ]],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'ringkasan_ketidaksesuaian',
                            'strict' => true,
                            'schema' => $this->skemaRingkasan(),
                        ],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new AiTidakTersedia('Layanan AI tidak merespons tepat waktu.', penyebab: 'timeout');
        }

        $batasLaju = AiRateLimit::dari($respon);

        if ($batasLaju !== null) {
            throw $batasLaju;
        }

        if ($respon->failed()) {
            try {
                $respon = Http::withToken($this->apiKey)
                    ->acceptJson()
                    ->timeout(max(1, $this->timeout))
                    ->connectTimeout(max(5, (int) config('ai.connect_timeout', 30)))
                    ->post(rtrim($this->baseUrl, '/').'/responses', [
                        'model' => $this->model,
                        'input' => $prompt."\n\nBalas HANYA dengan JSON valid berformat: {\"ringkasan\": \"...\", \"saran\": [\"...\"]}",
                    ]);
            } catch (ConnectionException) {
                throw new AiTidakTersedia('Layanan AI tidak merespons tepat waktu.', penyebab: 'timeout');
            }
        }

        if ($respon->failed()) {
            $batasLaju = AiRateLimit::dari($respon);

            if ($batasLaju !== null) {
                throw $batasLaju;
            }

            throw new AiTidakTersedia(
                'Layanan AI tidak dapat dihubungi.',
                statusHttp: $respon->status(),
                penyebab: 'http',
            );
        }

        $teks = $this->teksKeluaran($respon->json());
        $teks = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($teks)) ?? $teks;
        $data = json_decode(trim($teks), true);

        if (! is_array($data)) {
            throw new AiTidakTersedia('Hasil ringkasan AI tidak dapat dibaca.', penyebab: 'respons_tidak_terbaca');
        }

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
     * @param  array<string, mixed>  $bagianDokumen
     */
    private function kirimEkstraksi(string $prompt, array $bagianDokumen): Response
    {
        $respon = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout(max(1, $this->timeout))
            ->connectTimeout(max(5, (int) config('ai.connect_timeout', 30)))
            ->post(rtrim($this->baseUrl, '/').'/responses', [
                'model' => $this->model,
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $prompt],
                        $bagianDokumen,
                    ],
                ]],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'ekstraksi_surat_kematian',
                        'strict' => true,
                        'schema' => $this->skema(),
                    ],
                ],
            ]);

        if ($respon->failed() && ($bagianDokumen['type'] ?? null) === 'input_text') {
            $respon = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout(max(1, $this->timeout))
                ->connectTimeout(max(5, (int) config('ai.connect_timeout', 30)))
                ->post(rtrim($this->baseUrl, '/').'/responses', [
                    'model' => $this->model,
                    'input' => $prompt."\n\n".($bagianDokumen['text'] ?? '')."\n\nBalas HANYA dengan JSON valid berisi key: nama_almarhum, nik, tanggal_kematian, tempat_kematian, dan keyakinan (object berisi key yang sama bernilai 0..1).",
                ]);
        }

        return $respon;
    }

    /**
     * Skema keluaran ringkasan ketidaksesuaian.
     *
     * @return array<string, mixed>
     */
    private function skemaRingkasan(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ringkasan' => ['type' => 'string'],
                'saran' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['ringkasan', 'saran'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Bagian dokumen sesuai tipe berkas yang didukung.
     *
     * @return array<string, string>
     */
    private function bagianDokumen(BerkasPendukung $berkas, string $isi): array
    {
        $ekstensi = strtolower(pathinfo($berkas->path_file, PATHINFO_EXTENSION));
        $base64 = base64_encode($isi);

        return match ($ekstensi) {
            'pdf' => [
                'type' => 'input_file',
                'filename' => 'dokumen.pdf',
                'file_data' => 'data:application/pdf;base64,'.$base64,
                'detail' => 'high',
            ],
            'jpg', 'jpeg' => [
                'type' => 'input_image',
                'image_url' => 'data:image/jpeg;base64,'.$base64,
                'detail' => 'high',
            ],
            'png' => [
                'type' => 'input_image',
                'image_url' => 'data:image/png;base64,'.$base64,
                'detail' => 'high',
            ],
            default => throw new AiTidakTersedia(
                'Format dokumen tidak didukung layanan AI.',
                penyebab: 'format_tidak_didukung',
            ),
        };
    }

    /**
     * Mengambil teks keluaran dari bentuk Responses API.
     */
    private function teksKeluaran(mixed $json): string
    {
        if (is_array($json) && isset($json['output_text']) && is_string($json['output_text'])) {
            return $json['output_text'];
        }

        if (is_array($json) && isset($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $keluaran) {
                if (($keluaran['type'] ?? null) !== 'message' || ! is_array($keluaran['content'] ?? null)) {
                    continue;
                }

                foreach ($keluaran['content'] as $bagian) {
                    if (($bagian['type'] ?? null) === 'output_text' && is_string($bagian['text'] ?? null)) {
                        return $bagian['text'];
                    }
                }
            }
        }

        throw new AiTidakTersedia('Hasil layanan AI tidak dapat dibaca.', penyebab: 'respons_kosong');
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
     * Skema keluaran terstruktur sesuai dokumentasi Structured Outputs.
     *
     * @return array<string, mixed>
     */
    private function skema(): array
    {
        $angka = ['type' => 'number'];
        $teks = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'nama_almarhum' => $teks,
                'nik' => $teks,
                'tanggal_kematian' => $teks,
                'tempat_kematian' => $teks,
                'keyakinan' => [
                    'type' => 'object',
                    'properties' => [
                        'nama_almarhum' => $angka,
                        'nik' => $angka,
                        'tanggal_kematian' => $angka,
                        'tempat_kematian' => $angka,
                    ],
                    'required' => ['nama_almarhum', 'nik', 'tanggal_kematian', 'tempat_kematian'],
                    'additionalProperties' => false,
                ],
            ],
            'required' => ['nama_almarhum', 'nik', 'tanggal_kematian', 'tempat_kematian', 'keyakinan'],
            'additionalProperties' => false,
        ];
    }
}
