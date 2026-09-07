<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ config('webshell.ui.theme', 'dark') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#c6f54a">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ url(trim(config('webshell.path', 'webshell'), '/').'/manifest.webmanifest') }}">
    <title>{{ config('webshell.name', 'Laravel WebShell') }}</title>
    <style>@include('webshell::partials.styles')</style>
</head>
<body>
    <div id="app" class="app" data-base="{{ url(config('webshell.path', 'webshell')) }}">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">$</span>
                <div>
                    <strong>WebShell</strong>
                    <small id="edition">community</small>
                </div>
            </div>
            <nav class="nav" id="nav" aria-label="Primary"></nav>
            <div class="sidebar-foot">
                <div class="env-chip" id="envChip">local</div>
                <button type="button" class="ghost" id="lockBtn">Lock</button>
            </div>
        </aside>

        <main class="main">
            <header class="topbar">
                <button type="button" class="icon-btn mobile-only" id="menuBtn" aria-label="Open navigation">☰</button>
                <div>
                    <h1 id="pageTitle">Dashboard</h1>
                    <p id="pageSub" class="muted">Secure Laravel console</p>
                </div>
                <div class="top-actions">
                    <span class="status" id="connStatus"><i></i> Connected</span>
                    <button type="button" class="ghost" id="paletteBtn" title="Command palette">⌘K</button>
                    <button type="button" class="ghost" id="themeBtn" aria-label="Toggle theme">Theme</button>
                </div>
            </header>

            <div id="alerts" class="alerts" hidden></div>
            <section id="view" class="view" aria-live="polite"></section>
        </main>
    </div>

    <div id="palette" class="overlay" hidden>
        <div class="palette" role="dialog" aria-label="Command palette">
            <input id="paletteInput" type="search" placeholder="Run a command, deploy, clear cache…" autocomplete="off">
            <ul id="paletteList"></ul>
        </div>
    </div>

    <div id="confirm" class="overlay" hidden>
        <div class="modal" role="alertdialog" aria-labelledby="confirmTitle">
            <h2 id="confirmTitle">Dangerous command</h2>
            <p id="confirmText"></p>
            <pre id="confirmCmd" class="cmd-preview"></pre>
            <div class="modal-actions">
                <button type="button" class="ghost" id="confirmCancel">Cancel</button>
                <button type="button" class="danger" id="confirmOk">I understand — execute</button>
            </div>
        </div>
    </div>

    <div id="lockScreen" class="lock" hidden>
        <div>
            <h2>WebShell locked</h2>
            <p>Unlock to continue this session.</p>
            <button type="button" class="primary" id="unlockBtn">Unlock</button>
        </div>
    </div>

    <div class="mobile-keys" id="mobileKeys">
        <button type="button" data-key="Tab">⇥</button>
        <button type="button" data-key="ArrowUp">↑</button>
        <button type="button" data-key="ArrowDown">↓</button>
        <button type="button" data-key="CtrlC">Ctrl+C</button>
        <button type="button" data-key="CtrlL">Ctrl+L</button>
    </div>

    <script>@include('webshell::partials.scripts')</script>
</body>
</html>
