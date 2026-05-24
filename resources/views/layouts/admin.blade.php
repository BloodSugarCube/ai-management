<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI-management')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg: #0f172a;
            --panel: #111827;
            --muted: #94a3b8;
            --text: #e5e7eb;
            --accent: #38bdf8;
            --border: #1f2937;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, sans-serif;
            background: radial-gradient(1200px 600px at 20% -10%, #1e293b, var(--bg));
            color: var(--text);
            min-height: 100vh;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        header {
            border-bottom: 1px solid var(--border);
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 16px 20px; }
        .nav { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .brand { font-weight: 700; letter-spacing: 0.02em; }
        .menu { display: flex; gap: 16px; align-items: center; }
        .menu a { color: var(--text); opacity: 0.9; }
        .menu a.active { color: var(--accent); }
        main .wrap { padding-top: 24px; padding-bottom: 48px; }
        .card {
            background: rgba(17, 24, 39, 0.75);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { border-bottom: 1px solid var(--border); padding: 10px 12px; vertical-align: top; }
        th { text-align: left; color: var(--muted); font-weight: 600; background: #0b1220; }
        tr:hover td { background: rgba(56, 189, 248, 0.04); }
        input[type="text"], input[type="password"], textarea, select {
            width: 100%;
            background: #0b1220;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 8px;
            padding: 8px 10px;
        }
        textarea { min-height: 72px; resize: vertical; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid var(--border);
            background: #0b1220;
            color: var(--text);
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-primary {
            background: linear-gradient(180deg, #38bdf8, #0ea5e9);
            color: #0b1220;
            border-color: #67e8f9;
        }
        .btn:disabled { opacity: 0.55; cursor: not-allowed; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; border: 1px solid var(--border); color: var(--muted); font-size: 12px; }
        .error { color: #fecaca; margin: 8px 0; }
        .ok { color: #bbf7d0; margin: 8px 0; }
        .loader-overlay {
            position: fixed; inset: 0; background: rgba(2, 6, 23, 0.55);
            display: none; align-items: center; justify-content: center; z-index: 100;
        }
        .loader-overlay.show { display: flex; }
        .spinner {
            width: 48px; height: 48px; border-radius: 50%;
            border: 4px solid rgba(148, 163, 184, 0.35);
            border-top-color: var(--accent);
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .modal-backdrop { position: fixed; inset: 0; background: rgba(2, 6, 23, 0.65); display: none; z-index: 90; }
        .modal-backdrop.show { display: block; }
        .modal {
            position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%);
            width: min(720px, calc(100vw - 32px));
            background: #0b1220; border: 1px solid var(--border); border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.55);
            display: none; z-index: 95; max-height: min(80vh, 900px); overflow: auto;
        }
        .modal.show { display: block; }
        .modal header { position: static; border-bottom: 1px solid var(--border); }
        .modal .modal-head { display:flex; align-items:center; justify-content: space-between; gap: 12px; }
        .x { border: none; background: transparent; color: var(--muted); font-size: 22px; cursor: pointer; }
        .toggle { display:flex; align-items:center; gap: 10px; user-select:none; }
        .switch {
            width: 46px; height: 26px; border-radius: 999px; border: 1px solid var(--border);
            background: #0b1220; position: relative; cursor: pointer;
        }
        .switch::after {
            content: ""; width: 20px; height: 20px; border-radius: 50%;
            background: var(--accent); position: absolute; top: 2px; left: 3px; transition: left 0.15s ease;
        }
        .switch.on::after { left: 22px; }
        ul.compact { margin: 0; padding-left: 18px; }
        ul.compact li { margin: 2px 0; }
        details summary { cursor: pointer; color: var(--accent); }
    </style>
    @stack('head')
</head>
<body>
<div id="global-loader" class="loader-overlay" aria-hidden="true"><div class="spinner" role="status" aria-label="Загрузка"></div></div>

<header>
    <div class="wrap nav">
        <div class="brand">AI-management</div>
        @if(session(config('admin.session_key')))
        <nav class="menu">
            <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">Сотрудники</a>
            <a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.*') ? 'active' : '' }}">Задачи</a>
            <form method="post" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button class="btn" type="submit">Выйти</button>
            </form>
        </nav>
        @endif
    </div>
</header>

<main>
    <div class="wrap">
        @if (session('status'))
            <div class="ok">{{ session('status') }}</div>
        @endif
        @yield('content')
    </div>
</main>

<script>
    window.setGlobalLoading = function (on) {
        const el = document.getElementById('global-loader');
        if (!el) return;
        el.classList.toggle('show', !!on);
        document.documentElement.style.overflow = on ? 'hidden' : '';
    };
</script>
@stack('scripts')
</body>
</html>
