:root {
    --bg: #10140f;
    --bg-elev: #171d16;
    --bg-soft: #1e261c;
    --line: #2b3628;
    --text: #e8f0e4;
    --muted: #93a38b;
    --accent: #c6f54a;
    --accent-2: #6ee7c5;
    --danger: #ff6b4a;
    --warn: #f5c14a;
    --ok: #7dff9a;
    --font: "IBM Plex Mono", "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    --sans: "IBM Plex Sans", "Segoe UI", system-ui, sans-serif;
    --radius: 14px;
    --shadow: 0 24px 60px rgba(0,0,0,.35);
}
[data-theme="light"] {
    --bg: #f4f1e8; --bg-elev: #fffdf6; --bg-soft: #ebe6d8; --line: #d7d0bc;
    --text: #1c2118; --muted: #5d6657; --accent: #3d6b00; --accent-2: #0f766e;
    --danger: #b42318; --warn: #a16207; --ok: #157f3b;
}
[data-theme="dracula"] { --bg:#1e1f29; --bg-elev:#282a36; --bg-soft:#303241; --line:#44475a; --text:#f8f8f2; --muted:#bd93f9; --accent:#50fa7b; --accent-2:#8be9fd; --danger:#ff5555; }
[data-theme="monokai"] { --bg:#1d1e19; --bg-elev:#272822; --bg-soft:#30312b; --line:#49483e; --text:#f8f8f2; --muted:#a6a89a; --accent:#a6e22e; --accent-2:#66d9ef; --danger:#f92672; }
[data-theme="solarized"] { --bg:#002b36; --bg-elev:#073642; --bg-soft:#0a4150; --line:#164959; --text:#eee8d5; --muted:#93a1a1; --accent:#b58900; --accent-2:#2aa198; --danger:#dc322f; }
[data-theme="high-contrast"] { --bg:#000; --bg-elev:#0a0a0a; --bg-soft:#111; --line:#fff; --text:#fff; --muted:#ddd; --accent:#ffe600; --accent-2:#00ffff; --danger:#ff3b3b; }
[data-theme="cyberpunk"] { --bg:#0d0221; --bg-elev:#190b3b; --bg-soft:#261458; --line:#ff007f; --text:#00f0ff; --muted:#a78bfa; --accent:#ffe600; --accent-2:#00f0ff; --danger:#ff0055; }
[data-theme="emerald"] { --bg:#062319; --bg-elev:#0c3829; --bg-soft:#144c38; --line:#1d6b50; --text:#e6fcf5; --muted:#8ce99a; --accent:#38d9a9; --accent-2:#63e6be; --danger:#fa5252; }
[data-theme="nord"] { --bg:#2e3440; --bg-elev:#3b4252; --bg-soft:#434c5e; --line:#4c566a; --text:#eceff4; --muted:#d8dee9; --accent:#88c0d0; --accent-2:#81a1c1; --danger:#bf616a; }

* { box-sizing: border-box; }
html, body { margin: 0; height: 100%; background: var(--bg); color: var(--text); font-family: var(--sans); }
button, input, textarea, select { font: inherit; color: inherit; }
button { cursor: pointer; }
.app { display: grid; grid-template-columns: 240px 1fr; min-height: 100%; }
.sidebar { border-right: 1px solid var(--line); background: var(--bg-elev); padding: 18px 14px; display: flex; flex-direction: column; gap: 18px; }
.brand { display: flex; gap: 10px; align-items: center; }
.brand-mark { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; background: var(--accent); color: #111; font-family: var(--font); font-weight: 700; }
.brand small { display: block; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; font-size: 10px; }
.nav { display: grid; gap: 4px; overflow: auto; }
.nav button { text-align: left; border: 0; background: transparent; padding: 8px 10px; border-radius: 10px; color: var(--muted); }
.nav button.active, .nav button:hover { background: var(--bg-soft); color: var(--text); }
.sidebar-foot { margin-top: auto; display: flex; gap: 8px; align-items: center; }
.env-chip { font-size: 11px; padding: 4px 8px; border-radius: 999px; border: 1px solid var(--line); text-transform: uppercase; letter-spacing: .06em; }
.env-chip.production { border-color: var(--danger); color: var(--danger); }
.main { display: flex; flex-direction: column; min-width: 0; }
.topbar { display: flex; justify-content: space-between; gap: 16px; align-items: center; padding: 18px 22px 8px; }
.topbar h1 { margin: 0; font-size: 22px; letter-spacing: -.03em; }
.muted { color: var(--muted); margin: 4px 0 0; }
.top-actions, .modal-actions, .row-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.status { display: flex; gap: 8px; align-items: center; color: var(--ok); font-size: 13px; }
.status i { width: 8px; height: 8px; border-radius: 50%; background: var(--ok); box-shadow: 0 0 0 4px color-mix(in srgb, var(--ok) 20%, transparent); }
.view { padding: 8px 22px 96px; }
.grid { display: grid; gap: 14px; }
.grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.card { background: var(--bg-elev); border: 1px solid var(--line); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow); }
.card h3, .card h2 { margin: 0 0 10px; font-size: 14px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
.metric { font-family: var(--font); font-size: 28px; }
.ghost, .primary, .danger, .icon-btn { border-radius: 10px; border: 1px solid var(--line); background: var(--bg-soft); padding: 8px 12px; }
.primary { background: var(--accent); color: #111; border-color: transparent; font-weight: 650; }
.danger { background: var(--danger); color: #fff; border-color: transparent; }
.icon-btn { width: 38px; height: 38px; padding: 0; }
.alerts { padding: 0 22px; display: grid; gap: 8px; }
.alert { border: 1px solid var(--danger); background: color-mix(in srgb, var(--danger) 12%, var(--bg-elev)); padding: 10px 12px; border-radius: 12px; }
.terminal { background: #0b0e0a; border: 1px solid var(--line); border-radius: var(--radius); min-height: 520px; display: flex; flex-direction: column; overflow: hidden; font-family: var(--font); }
[data-theme="light"] .terminal { background: #1b1f18; color: #e8f0e4; }
.term-head { display: flex; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid var(--line); color: var(--muted); font-size: 12px; }
.term-body { flex: 1; overflow: auto; padding: 14px; white-space: pre-wrap; word-break: break-word; line-height: 1.5; }
.term-line.cmd { color: var(--accent); }
.term-line.err { color: var(--danger); }
.term-line.meta { color: var(--muted); font-size: 12px; }
.term-input { display: flex; gap: 8px; align-items: center; border-top: 1px solid var(--line); padding: 10px 12px; }
.term-input span { color: var(--accent); }
.term-input input { flex: 1; background: transparent; border: 0; outline: none; font-family: var(--font); }
.searchbar, .field { display: flex; gap: 8px; margin-bottom: 12px; }
.searchbar input, .field input, .field select, textarea { width: 100%; background: var(--bg-soft); border: 1px solid var(--line); border-radius: 10px; padding: 9px 10px; }
.tree { display: grid; gap: 10px; }
.tree details { background: var(--bg-elev); border: 1px solid var(--line); border-radius: 12px; padding: 8px 10px; }
.tree button.link { background: none; border: 0; color: var(--accent-2); padding: 4px 0; display: block; text-align: left; }
.table { width: 100%; border-collapse: collapse; font-size: 13px; }
.table th, .table td { text-align: left; padding: 8px; border-bottom: 1px solid var(--line); vertical-align: top; }
.badge { font-size: 11px; padding: 2px 6px; border-radius: 999px; border: 1px solid var(--line); }
.badge.ok { color: var(--ok); border-color: var(--ok); }
.badge.bad { color: var(--danger); border-color: var(--danger); }
.badge.warn { color: var(--warn); border-color: var(--warn); }
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: grid; place-items: start center; padding-top: 12vh; z-index: 20; }
.palette, .modal { width: min(720px, calc(100% - 24px)); background: var(--bg-elev); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); overflow: hidden; }
.palette input { width: 100%; border: 0; background: transparent; padding: 16px; outline: none; font-family: var(--font); }
.palette ul { list-style: none; margin: 0; padding: 0 0 8px; max-height: 360px; overflow: auto; }
.palette li { padding: 10px 16px; cursor: pointer; }
.palette li.active, .palette li:hover { background: var(--bg-soft); }
.modal { padding: 20px; }
.cmd-preview { background: var(--bg); padding: 10px; border-radius: 10px; overflow: auto; }
.lock { position: fixed; inset: 0; background: var(--bg); display: grid; place-items: center; z-index: 30; text-align: center; }
.mobile-only, .mobile-keys { display: none; }
.check { display: flex; gap: 8px; align-items: center; margin: 6px 0; }
.file-list { display: grid; gap: 4px; }
.file-list button { width: 100%; text-align: left; background: transparent; border: 0; padding: 6px 4px; color: var(--text); }
.editor { min-height: 320px; font-family: var(--font); }
.chips { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
.chip { border: 1px solid var(--line); border-radius: 999px; padding: 4px 10px; font-size: 11px; background: var(--bg-soft); color: var(--muted); cursor: pointer; transition: all .15s ease; font-family: var(--font); }
.chip:hover { border-color: var(--accent); color: var(--text); background: var(--bg-elev); }
.chip.active { border-color: var(--accent); color: #111; background: var(--accent); font-weight: 600; }
.breadcrumbs { display: flex; gap: 6px; align-items: center; font-size: 12px; font-family: var(--font); color: var(--muted); padding: 6px 0 10px; overflow-x: auto; }
.breadcrumbs a, .breadcrumbs button { color: var(--accent-2); cursor: pointer; text-decoration: none; background: none; border: 0; padding: 0; font: inherit; }
.toolbar { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap; }
.btn-sm { padding: 4px 8px; font-size: 12px; border-radius: 8px; }
.code-tag { font-family: var(--font); font-size: 11px; padding: 2px 6px; border-radius: 6px; background: var(--bg-soft); border: 1px solid var(--line); color: var(--accent-2); }
.card-glass { backdrop-filter: blur(16px); background: color-mix(in srgb, var(--bg-elev) 85%, transparent); }
@media (max-width: 860px) {
    .app { grid-template-columns: 1fr; }
    .sidebar { display: none; position: fixed; inset: 0 auto 0 0; width: 260px; z-index: 15; }
    .sidebar.open { display: flex; }
    .mobile-only { display: inline-flex; }
    .grid.cols-3, .grid.cols-2 { grid-template-columns: 1fr; }
    .mobile-keys { display: flex; position: fixed; left: 0; right: 0; bottom: 0; gap: 8px; padding: 8px 12px calc(8px + env(safe-area-inset-bottom)); background: var(--bg-elev); border-top: 1px solid var(--line); }
}
@media (prefers-reduced-motion: reduce) {
    * { transition: none !important; animation: none !important; }
}
