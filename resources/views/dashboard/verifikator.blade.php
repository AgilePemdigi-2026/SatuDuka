
<div class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
    <div class="flex items-start gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M7.5 3.75h6l4.5 4.5v12H7.5a2.25 2.25 0 0 1-2.25-2.25V6A2.25 2.25 0 0 1 7.5 3.75Z"
                />
            </svg>
        </div>

        <div class="min-w-0 flex-1">
            <h2 class="text-base font-semibold text-slate-800">
                Verifikasi Berkas
            </h2>

            <p class="mt-1 text-sm leading-6 text-slate-500">
                Periksa kelengkapan dokumen pengajuan, cocokkan identitas
                dengan data SIAK, dan tentukan hasil verifikasi.
            </p>

            <a
                href="{{ route('verifikasi.index') }}"
                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Buka antrean verifikasi
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</div>
