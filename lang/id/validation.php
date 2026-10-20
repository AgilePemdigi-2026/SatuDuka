<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi
    |--------------------------------------------------------------------------
    |
    | Berikut adalah pesan galat bawaan yang digunakan oleh kelas Validator.
    | Beberapa aturan memiliki beberapa versi (misalnya aturan ukuran). Feel
    | free untuk menyesuaikan pesan-pesan ini sesuai kebutuhan aplikasi.
    |
    */

    'accepted' => 'Kolom :attribute harus disetujui.',
    'accepted_if' => 'Kolom :attribute harus disetujui ketika :other bernilai :value.',
    'active_url' => 'Kolom :attribute harus berupa URL yang valid.',
    'after' => 'Kolom :attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berisi tanggal setelah atau sama dengan :date.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, garis, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'any_of' => 'Kolom :attribute tidak valid.',
    'array' => 'Kolom :attribute harus berupa larik.',
    'array_keys' => 'Kolom :attribute hanya boleh berisi kunci berikut: :values.',
    'ascii' => 'Kolom :attribute hanya boleh berisi karakter alfanumerik dan simbol satu byte.',
    'base64' => 'Kolom :attribute harus berupa string Base64 yang valid.',
    'before' => 'Kolom :attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berisi tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Kolom :attribute harus berisi antara :min sampai :max item.',
        'file' => 'Kolom :attribute harus berukuran antara :min sampai :max kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
        'string' => 'Kolom :attribute harus terdiri dari :min sampai :max karakter.',
    ],
    'boolean' => 'Kolom :attribute harus bernilai benar atau salah.',
    'can' => 'Kolom :attribute mengandung nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => 'Kolom :attribute tidak mengandung nilai yang diperlukan.',
    'current_password' => 'Kata sandi saat ini salah.',
    'date' => 'Kolom :attribute harus berisi tanggal yang valid.',
    'date_equals' => 'Kolom :attribute harus berisi tanggal yang sama dengan :date.',
    'date_format' => 'Kolom :attribute harus sesuai dengan format :format.',
    'decimal' => 'Kolom :attribute harus memiliki :decimal angka di belakang koma.',
    'declined' => 'Kolom :attribute harus ditolak.',
    'declined_if' => 'Kolom :attribute harus ditolak ketika :other bernilai :value.',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus terdiri dari :digits angka.',
    'digits_between' => 'Kolom :attribute harus terdiri dari :min sampai :max angka.',
    'dimensions' => 'Kolom :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Kolom :attribute memiliki nilai yang duplikat.',
    'doesnt_contain' => 'Kolom :attribute tidak boleh mengandung salah satu dari berikut: :values.',
    'doesnt_end_with' => 'Kolom :attribute tidak boleh diakhiri dengan salah satu dari berikut: :values.',
    'doesnt_start_with' => 'Kolom :attribute tidak boleh diawali dengan salah satu dari berikut: :values.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'encoding' => 'Kolom :attribute harus dienkode dengan :encoding.',
    'ends_with' => 'Kolom :attribute harus diakhiri dengan salah satu dari berikut: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'extensions' => 'Kolom :attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file' => 'Kolom :attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute harus diisi.',
    'gt' => [
        'array' => 'Kolom :attribute harus memiliki lebih dari :value item.',
        'file' => 'Kolom :attribute harus lebih besar dari :value kilobyte.',
        'numeric' => 'Kolom :attribute harus lebih besar dari :value.',
        'string' => 'Kolom :attribute harus terdiri dari lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Kolom :attribute harus memiliki :value item atau lebih.',
        'file' => 'Kolom :attribute harus lebih besar atau sama dengan :value kilobyte.',
        'numeric' => 'Kolom :attribute harus lebih besar atau sama dengan :value.',
        'string' => 'Kolom :attribute harus terdiri dari :value karakter atau lebih.',
    ],
    'hex_color' => 'Kolom :attribute harus berupa warna heksadesimal yang valid.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => 'Kolom :attribute harus ada di dalam :other.',
    'in_array_keys' => 'Kolom :attribute harus mengandung minimal satu kunci berikut: :values.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Kolom :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Kolom :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Kolom :attribute harus berupa string JSON yang valid.',
    'list' => 'Kolom :attribute harus berupa daftar.',
    'lowercase' => 'Kolom :attribute harus berupa huruf kecil.',
    'lt' => [
        'array' => 'Kolom :attribute harus memiliki kurang dari :value item.',
        'file' => 'Kolom :attribute harus lebih kecil dari :value kilobyte.',
        'numeric' => 'Kolom :attribute harus lebih kecil dari :value.',
        'string' => 'Kolom :attribute harus terdiri dari kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Kolom :attribute tidak boleh memiliki lebih dari :value item.',
        'file' => 'Kolom :attribute harus lebih kecil atau sama dengan :value kilobyte.',
        'numeric' => 'Kolom :attribute harus lebih kecil atau sama dengan :value.',
        'string' => 'Kolom :attribute harus terdiri dari kurang dari :value karakter atau sama dengan itu.',
    ],
    'mac_address' => 'Kolom :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'Kolom :attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Kolom :attribute tidak boleh terdiri dari lebih dari :max karakter.',
    ],
    'max_digits' => 'Kolom :attribute tidak boleh terdiri dari lebih dari :max angka.',
    'mimes' => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => 'Kolom :attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'array' => 'Kolom :attribute harus memiliki minimal :min item.',
        'file' => 'Kolom :attribute harus minimal :min kilobyte.',
        'numeric' => 'Kolom :attribute harus minimal :min.',
        'string' => 'Kolom :attribute harus terdiri dari minimal :min karakter.',
    ],
    'min_digits' => 'Kolom :attribute harus terdiri dari minimal :min angka.',
    'missing' => 'Kolom :attribute harus tidak ada.',
    'missing_if' => 'Kolom :attribute harus tidak ada ketika :other bernilai :value.',
    'missing_unless' => 'Kolom :attribute harus tidak ada kecuali :other bernilai :value.',
    'missing_with' => 'Kolom :attribute harus tidak ada ketika :values ada.',
    'missing_with_all' => 'Kolom :attribute harus tidak ada ketika :values ada.',
    'multiple_of' => 'Kolom :attribute harus merupakan kelipatan dari :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format kolom :attribute tidak valid.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Kolom :attribute harus mengandung minimal satu huruf.',
        'mixed' => 'Kolom :attribute harus mengandung minimal satu huruf besar dan satu huruf kecil.',
        'numbers' => 'Kolom :attribute harus mengandung minimal satu angka.',
        'symbols' => 'Kolom :attribute harus mengandung minimal satu simbol.',
        'uncompromised' => ':attribute yang diberikan telah muncul dalam kebocoran data. Silakan pilih :attribute yang lain.',
    ],
    'present' => 'Kolom :attribute harus ada.',
    'present_if' => 'Kolom :attribute harus ada ketika :other bernilai :value.',
    'present_unless' => 'Kolom :attribute harus ada kecuali :other bernilai :value.',
    'present_with' => 'Kolom :attribute harus ada ketika :values ada.',
    'present_with_all' => 'Kolom :attribute harus ada ketika :values ada.',
    'prohibited' => 'Kolom :attribute tidak diizinkan.',
    'prohibited_if' => 'Kolom :attribute tidak diizinkan ketika :other bernilai :value.',
    'prohibited_if_accepted' => 'Kolom :attribute tidak diizinkan ketika :other disetujui.',
    'prohibited_if_declined' => 'Kolom :attribute tidak diizinkan ketika :other ditolak.',
    'prohibited_unless' => 'Kolom :attribute tidak diizinkan kecuali :other termasuk :values.',
    'prohibits' => 'Kolom :attribute melarang :other untuk ada.',
    'regex' => 'Format kolom :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => 'Kolom :attribute harus mengandung entri untuk: :values.',
    'required_if' => 'Kolom :attribute wajib diisi ketika :other bernilai :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi ketika :other disetujui.',
    'required_if_declined' => 'Kolom :attribute wajib diisi ketika :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other termasuk :values.',
    'required_with' => 'Kolom :attribute wajib diisi ketika :values ada.',
    'required_with_all' => 'Kolom :attribute wajib diisi ketika :values ada.',
    'required_without' => 'Kolom :attribute wajib diisi ketika :values tidak ada.',
    'required_without_all' => 'Kolom :attribute wajib diisi ketika tidak ada satu pun dari :values.',
    'same' => 'Kolom :attribute harus sama dengan :other.',
    'size' => [
        'array' => 'Kolom :attribute harus mengandung :size item.',
        'file' => 'Kolom :attribute harus berukuran :size kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string' => 'Kolom :attribute harus terdiri dari :size karakter.',
    ],
    'starts_with' => 'Kolom :attribute harus diawali dengan salah satu dari berikut: :values.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'timezone' => 'Kolom :attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => 'Kolom :attribute gagal diunggah.',
    'uppercase' => 'Kolom :attribute harus berupa huruf besar.',
    'url' => 'Kolom :attribute harus berupa URL yang valid.',
    'ulid' => 'Kolom :attribute harus berupa ULID yang valid.',
    'uuid' => 'Kolom :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi Kustom
    |--------------------------------------------------------------------------
    |
    | Di sini Anda dapat menentukan pesan validasi kustom untuk atribut
    | menggunakan konvensi "attribute.rule" untuk menamai baris tersebut.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'pesan-kustom',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Atribut Validasi Kustom
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut digunakan untuk mengganti placeholder atribut
    | dengan nama yang lebih mudah dipahami, misalnya "Alamat Email" alih-alih
    | "email". Tujuannya agar pesan validasi lebih ramah dibaca pengguna.
    |
    */

    'attributes' => [
        'nama' => 'nama',
        'nik' => 'NIK',
        'no_kk' => 'Nomor Kartu Keluarga',
        'tanggal_lahir' => 'tanggal lahir',
        'tanggal_meninggal' => 'tanggal meninggal',
        'tempat_lahir' => 'tempat lahir',
        'tempat_meninggal' => 'tempat meninggal',
        'jenis_kelamin' => 'jenis kelamin',
        'alamat' => 'alamat',
        'no_hp' => 'nomor telepon',
        'email' => 'email',
        'kata_sandi' => 'kata sandi',
        'konfirmasi_kata_sandi' => 'konfirmasi kata sandi',
        'pejabat' => 'pejabat pencatat',
        'verifikator' => 'verifikator',
        'laporan_kematian_id' => 'laporan kematian',
        'dokumen' => 'dokumen',
    ],

];
