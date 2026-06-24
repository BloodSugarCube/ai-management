<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
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
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: var(--text);
            text-decoration: none;
        }
        .brand:hover { text-decoration: none; opacity: 0.95; }
        .brand-logo {
            width: 28px;
            height: 28px;
            color: var(--accent);
            flex-shrink: 0;
        }
        .menu { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .menu a, .menu .menu-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text);
            opacity: 0.9;
            padding: 6px 10px;
            border-radius: 8px;
            text-decoration: none;
        }
        .menu a:hover, .menu .menu-link:hover { background: rgba(56, 189, 248, 0.08); text-decoration: none; }
        .menu a.active { color: var(--accent); background: rgba(56, 189, 248, 0.12); }
        .menu svg { width: 18px; height: 18px; flex-shrink: 0; opacity: 0.9; }
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
        input[type="text"], input[type="email"], input[type="password"], input[type="date"], input[type="number"], input[type="file"], textarea, select {
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
            font-size: 14px;
            text-decoration: none;
        }
        .btn:hover { background: #111827; text-decoration: none; }
        .btn-primary {
            background: linear-gradient(180deg, #38bdf8, #0ea5e9);
            color: #0b1220;
            border-color: #67e8f9;
        }
        .btn-primary:hover { filter: brightness(1.05); }
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
        .modal-title {
            display: flex; align-items: center; gap: 10px;
            font-weight: 800; font-size: 20px; line-height: 1.2;
        }
        .modal-title svg { width: 24px; height: 24px; flex-shrink: 0; color: var(--accent); }
        .x {
            border: none; background: transparent; color: var(--muted); font-size: 22px; cursor: pointer;
            width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; line-height: 1; padding: 0;
        }
        .x:hover { background: rgba(148, 163, 184, 0.12); color: var(--text); }
        .x-lg { width: 44px; height: 44px; font-size: 28px; }
        .role-badge {
            display: inline-block; padding: 4px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 600; white-space: nowrap;
        }
        .role-badge--admin {
            background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.4); color: #38bdf8;
        }
        .role-badge--manager {
            background: rgba(148, 163, 184, 0.12); border: 1px solid rgba(148, 163, 184, 0.35); color: #cbd5e1;
        }
        .card-overflow-visible { overflow: visible; }
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
        .form-grid { display: grid; gap: 14px; max-width: 640px; }
        .form-grid label span { display: block; margin-bottom: 6px; color: var(--muted); font-size: 13px; }
        .row-actions { position: relative; display: inline-block; }
        .row-actions-btn {
            border: 1px solid var(--border); background: #0b1220; color: var(--text);
            border-radius: 8px; width: 32px; height: 32px; cursor: pointer; font-size: 18px; line-height: 1;
        }
        .row-actions-menu {
            display: none; position: fixed;
            min-width: 160px; background: #0b1220; border: 1px solid var(--border);
            border-radius: 8px; box-shadow: 0 12px 40px rgba(0,0,0,0.55); z-index: 200; overflow: hidden;
        }
        .row-actions.open .row-actions-menu { display: block; }
        .row-actions-menu a, .row-actions-menu button {
            display: block; width: 100%; text-align: left; padding: 10px 12px;
            border: none; background: transparent; color: var(--text); cursor: pointer; font-size: 14px;
        }
        .row-actions-menu a:hover, .row-actions-menu button:hover { background: rgba(56, 189, 248, 0.08); text-decoration: none; }
        .row-actions-menu .danger { color: #fecaca; }
        .page-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    </style>
    @stack('head')
</head>
<body>
<div id="global-loader" class="loader-overlay" aria-hidden="true"><div class="spinner" role="status" aria-label="Загрузка"></div></div>

<header>
    <div class="wrap nav">
        <a href="{{ auth()->check() ? route('tasks.index') : route('login') }}" class="brand">
            <svg class="brand-logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 5a3 3 0 1 0-5.997.142 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588A4 4 0 1 0 12 18Z"/>
                <path d="M12 5a3 3 0 1 1 5.997.142 4 4 0 0 1 2.526 5.77 4 4 0 0 1-.556 6.588A4 4 0 1 1 12 18Z"/>
                <path d="M15 13a4.5 4.5 0 0 1-3-4 4.5 4.5 0 0 1-3 4"/>
                <path d="M12 18v4"/>
            </svg>
            <span>{{ config('app.name') }}</span>
        </a>
        @auth
        <nav class="menu">
            <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Сотрудники</span>
            </a>
            <a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <span>Задачи</span>
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Пользователи системы</span>
            </a>
            @endif
            <form method="post" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button class="btn menu-link" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Выйти</span>
                </button>
            </form>
        </nav>
        @endauth
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
