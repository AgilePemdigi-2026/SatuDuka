<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SKD') }} - @yield('judul', 'Verifikasi')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand: #1E3A5F;
            --brand-dark: #16304f;
            --brand-soft: #EEF3F9;
            --line: #E5EAF1;
            --ink: #0F172A;
            --muted: #64748B;
            --faint: #94A3B8;
            --bg: #F5F7FB;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif; background: var(--bg); color: #1e293b; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; }
        h1, h2, h3, p { margin: 0; }

        /* ===== Kerangka ===== */
        .skd-app { display: flex; min-height: 100vh; }
        .skd-side {
            width: 260px; flex-shrink: 0; background: #fff; border-right: 1px solid var(--line);
            padding: 28px 22px; display: flex; flex-direction: column; justify-content: space-between;
            position: sticky; top: 0; height: 100vh;
        }
        .skd-brand { text-align: center; padding: 6px 0 30px; }
        .skd-brand h1 { font-size: 30px; font-weight: 800; letter-spacing: -.02em; color: var(--brand); }
        .skd-brand p { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .skd-nav { display: flex; flex-direction: column; gap: 10px; }
        .skd-nav-label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--faint); padding: 0 4px; margin: 14px 0 4px; }
        .skd-nav-label:first-child { margin-top: 0; }
        .nav-link {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 14px;
            border: 1px solid var(--line); background: #fff; color: #475569; font-size: 13px; font-weight: 500;
            transition: all .15s ease;
        }
        .nav-link:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-soft); }
        .nav-link.active { background: var(--brand); border-color: var(--brand); color: #fff; font-weight: 600; box-shadow: 0 8px 16px -8px rgba(30,58,95,.6); }
        .nav-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; opacity: .5; flex-shrink: 0; }
        .nav-link.active .nav-dot { opacity: 1; }
        .skd-foot { font-size: 11px; text-align: center; color: var(--faint); padding-top: 18px; border-top: 1px solid var(--line); }

        .skd-main { flex: 1; min-width: 0; }
        .skd-container { max-width: 1180px; margin: 0 auto; padding: 36px 44px 56px; }
        .skd-topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 32px; }
        .skd-title { font-size: 28px; font-weight: 800; letter-spacing: -.02em; line-height: 1.2; color: var(--ink); }
        .skd-sub { font-size: 14px; color: var(--muted); margin-top: 6px; }
        .skd-user { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .skd-user-name { font-size: 14px; font-weight: 700; color: var(--ink); text-align: right; }
        .skd-user-role { font-size: 12px; color: var(--muted); text-align: right; }
        .skd-avatar { width: 42px; height: 42px; border-radius: 50%; background: var(--brand); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 15px; }

        /* ===== Komponen ===== */
        .stack { display: flex; flex-direction: column; gap: 28px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 20px; box-shadow: 0 1px 3px rgba(15,23,42,.05); overflow: hidden; }
        .card-head { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; padding: 22px 28px; border-bottom: 1px solid var(--line); }
        .card-title { font-size: 17px; font-weight: 700; color: var(--ink); }
        .card-desc { font-size: 12.5px; color: var(--muted); margin-top: 3px; }
        .card-body { padding: 26px 28px; }

        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .stat { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 20px; padding: 26px 20px 22px; text-align: center; box-shadow: 0 1px 3px rgba(15,23,42,.05); overflow: hidden; }
        .stat::before { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: var(--brand); }
        .stat-label { font-size: 11.5px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--muted); }
        .stat-value { font-size: 40px; font-weight: 800; letter-spacing: -.02em; line-height: 1; color: var(--brand); margin: 14px 0 10px; }
        .stat-note { font-size: 12px; color: var(--faint); }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 12px; font-family: inherit; font-weight: 600; font-size: 14px; padding: 11px 22px; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
        .btn-sm { font-size: 12.5px; padding: 8px 20px; }
        .btn-pill { border-radius: 999px; }
        .btn-brand { background: var(--brand); color: #fff; }
        .btn-brand:hover { background: var(--brand-dark); }
        .btn-green { background: #047857; color: #fff; }
        .btn-green:hover { background: #036649; }
        .btn-outline { background: #fff; color: #475569; border-color: #cbd5e1; }
        .btn-outline:hover { border-color: var(--brand); color: var(--brand); background: var(--brand-soft); }
        .btn-block { width: 100%; }

        .field { width: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 11px 14px; font-family: inherit; font-size: 14px; color: var(--ink); outline: none; transition: all .15s ease; }
        .field:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(30,58,95,.12); }
        .field::placeholder { color: var(--faint); }
        textarea.field { resize: vertical; }
        .field-label { display: block; font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        .search-form { display: flex; align-items: center; gap: 8px; }
        .search-input { width: 270px; border-radius: 999px; padding: 10px 20px; }

        .table-wrap { overflow-x: auto; }
        .tbl { width: 100%; border-collapse: collapse; font-size: 14px; }
        .tbl th { background: #F4F7FB; color: var(--muted); font-size: 11.5px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; text-align: left; padding: 15px 18px; white-space: nowrap; }
        .tbl td { padding: 18px; border-top: 1px solid var(--line); color: #334155; vertical-align: middle; }
        .tbl tbody tr:hover td { background: #F8FAFD; }
        .tbl .c { text-align: center; }
        .tbl th:first-child, .tbl td:first-child { padding-left: 28px; }
        .tbl th:last-child, .tbl td:last-child { padding-right: 28px; }
        .num { font-variant-numeric: tabular-nums; }

        .badge { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 5px 13px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .badge-brand { background: var(--brand-soft); color: var(--brand); }
        .badge-ok { background: #ecfdf5; color: #065f46; }
        .badge-bad { background: #fef2f2; color: #991b1b; }
        .badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        .alert { border-radius: 16px; padding: 16px 20px; font-size: 14px; margin-bottom: 24px; }
        .alert ul { margin: 8px 0 0; padding-left: 20px; }
        .alert-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-weight: 500; }
        .alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; font-weight: 500; margin: 0; }

        .empty { text-align: center; padding: 64px 20px; }
        .empty-ic { width: 52px; height: 52px; border-radius: 50%; background: var(--brand-soft); color: var(--brand); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; font-size: 20px; font-weight: 700; }
        .pager { padding: 18px 28px; border-top: 1px solid var(--line); }

        /* ===== Halaman detail ===== */
        .page-narrow { max-width: 980px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .info-item { background: #F7F9FC; border: 1px solid #EEF2F7; border-radius: 14px; padding: 14px 18px; }
        .info-item dt { font-size: 12px; color: var(--faint); margin-bottom: 4px; }
        .info-item dd { margin: 0; font-size: 14.5px; font-weight: 600; color: var(--ink); }
        .doc-list { display: flex; flex-direction: column; gap: 12px; }
        .doc-item { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 20px; border: 1px solid var(--line); border-radius: 14px; background: #fff; }
        .doc-left { display: flex; align-items: center; gap: 16px; min-width: 0; }
        .doc-ic { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
        .doc-ic.ok { background: #ecfdf5; color: #047857; }
        .doc-ic.bad { background: #fef2f2; color: #b91c1c; }
        .doc-name { font-size: 14px; font-weight: 600; color: var(--ink); }
        .doc-file { font-size: 12px; color: var(--muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .match-row { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #F7F9FC; border: 1px solid var(--line); border-radius: 14px; font-size: 14px; color: var(--muted); }
        .decision-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .decision-box { display: flex; flex-direction: column; justify-content: space-between; gap: 20px; background: #F7F9FC; border: 1px solid var(--line); border-radius: 16px; padding: 24px; }
        .decision-box h3 { font-size: 15px; font-weight: 700; color: var(--ink); }
        .decision-box .hint { font-size: 12.5px; color: var(--muted); margin: 4px 0 16px; }
        .form-stack { display: flex; flex-direction: column; gap: 12px; }

        @media (max-width: 1100px) { .stats { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 900px) {
            .skd-app { flex-direction: column; }
            .skd-side { width: 100%; height: auto; position: static; }
            .skd-container { padding: 24px 18px 40px; }
            .info-grid, .decision-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) { .stats { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="skd-app">

    <aside class="skd-side">
        <div>
            <div class="skd-brand">
                <h1>SKD</h1>
                <p>Sistem Kependudukan Digital</p>
            </div>

            <nav class="skd-nav">
                <p class="skd-nav-label">Menu Utama</p>
                <a href="{{ route('verifikasi.index') }}" class="nav-link {{ request()->routeIs('verifikasi.index') ? 'active' : '' }}">
                    <span class="nav-dot"></span>Dashboard
                </a>
                <a href="#" class="nav-link {{ request()->routeIs('verifikasi.show') ? 'active' : '' }}">
                    <span class="nav-dot"></span>Verifikasi &amp; Rekonsiliasi
                </a>
                <a href="#" class="nav-link"><span class="nav-dot"></span>Approve / Reject Berkas</a>

                <p class="skd-nav-label" style="margin-top: 22px;">Lainnya</p>
                <a href="#" class="nav-link"><span class="nav-dot"></span>Pengaturan</a>
            </nav>
        </div>

        <p class="skd-foot">&copy; {{ date('Y') }} SKD Indonesia</p>
    </aside>

    <div class="skd-main">
        <main class="skd-container">

            <div class="skd-topbar">
                <div>
                    <h1 class="skd-title">@yield('judul', 'Verifikasi')</h1>
                    @hasSection('subjudul')
                        <p class="skd-sub">@yield('subjudul')</p>
                    @endif
                </div>
                <div class="skd-user">
                    <div>
                        <p class="skd-user-name">{{ auth()->user()->nama ?? 'Andi Setiawan' }}</p>
                        <p class="skd-user-role">Verifikator</p>
                    </div>
                    <div class="skd-avatar">{{ strtoupper(mb_substr(auth()->user()->nama ?? 'Andi Setiawan', 0, 1)) }}</div>
                </div>
            </div>

            @if (session('sukses') || session('success'))
                <div class="alert alert-ok">{{ session('sukses') ?? session('success') }}</div>
            @endif

            @if ($errors->any() && ! trim($__env->yieldContent('tanpa_error_global')))
                <div role="alert" class="alert alert-err">
                    <strong>Perhatikan kendala berikut:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('konten')
        </main>
    </div>

</div>
</body>
</html>