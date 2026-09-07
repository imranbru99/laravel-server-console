(() => {
    const root = document.getElementById('app');
    const base = root.dataset.base.replace(/\/$/, '');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const state = {
        bootstrap: null,
        view: 'dashboard',
        history: [],
        histIndex: -1,
        cwd: '',
        running: false,
        abort: null,
        artisanDef: null,
        pending: null,
        lastActivity: Date.now(),
    };

    const NAV = [
        ['dashboard', 'Dashboard'],
        ['terminal', 'Terminal'],
        ['artisan', 'Artisan'],
        ['macros', 'Macros'],
        ['git', 'Git'],
        ['composer', 'Composer'],
        ['node', 'Node'],
        ['database', 'Database'],
        ['files', 'Files'],
        ['logs', 'Logs'],
        ['queue', 'Queue'],
        ['scheduler', 'Scheduler'],
        ['deploy', 'Deploy'],
        ['backups', 'Backups'],
        ['approvals', 'Approvals'],
        ['health', 'Health'],
        ['security', 'Security'],
        ['audit', 'Audit'],
        ['history', 'History'],
    ];

    const $ = (id) => document.getElementById(id);
    const viewEl = $('view');

    async function api(path, options = {}) {
        const res = await fetch(`${base}/api${path}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            ...options,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok && res.status !== 409) {
            throw new Error(data.message || `Request failed (${res.status})`);
        }
        data.__status = res.status;
        return data;
    }

    function html(strings, ...values) {
        return strings.reduce((out, s, i) => out + s + (values[i] ?? ''), '');
    }
    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }
    function ansi(text) {
        return esc(text)
            .replace(/\x1B\[32m/g, '<span style="color:var(--ok)">')
            .replace(/\x1B\[31m/g, '<span style="color:var(--danger)">')
            .replace(/\x1B\[33m/g, '<span style="color:var(--warn)">')
            .replace(/\x1B\[36m/g, '<span style="color:var(--accent-2)">')
            .replace(/\x1B\[0m/g, '</span>');
    }
    function bytes(n) {
        if (!n) return '0 B';
        const u = ['B', 'KB', 'MB', 'GB'];
        let i = 0; let v = Number(n);
        while (v >= 1024 && i < u.length - 1) { v /= 1024; i++; }
        return `${v.toFixed(1)} ${u[i]}`;
    }

    function setView(name) {
        state.view = name;
        document.querySelectorAll('.nav button').forEach((b) => b.classList.toggle('active', b.dataset.view === name));
        $('pageTitle').textContent = NAV.find((n) => n[0] === name)?.[1] || name;
        render();
    }

    function navHtml() {
        $('nav').innerHTML = NAV.map(([id, label]) => `<button type="button" data-view="${id}" class="${state.view === id ? 'active' : ''}">${label}</button>`).join('');
        $('nav').onclick = (e) => {
            const btn = e.target.closest('button');
            if (btn) { setView(btn.dataset.view); $('sidebar').classList.remove('open'); }
        };
    }

    function renderAlerts() {
        const warnings = state.bootstrap?.security_warnings || [];
        $('alerts').hidden = warnings.length === 0;
        $('alerts').innerHTML = warnings.slice(0, 3).map((w) => `<div class="alert"><strong>${esc(w.title)}</strong><div>${esc(w.message)}</div></div>`).join('');
    }

    function render() {
        const fn = views[state.view] || views.dashboard;
        viewEl.innerHTML = fn();
        bindView();
    }

    const views = {
        dashboard() {
            const app = state.bootstrap.application || {};
            const server = state.bootstrap.server || {};
            const health = state.bootstrap.health || {};
            const hist = state.bootstrap.history || [];
            const stack = app.stack || {};
            const disk = server.disk || {};
            const used = disk.total ? Math.round(((disk.total - disk.free) / disk.total) * 100) : 0;
            return html`
                <div class="grid cols-3">
                    <div class="card"><h3>Laravel</h3><div class="metric">${esc(app.laravel)}</div><p class="muted">${esc(app.environment)} · debug ${app.debug ? 'on' : 'off'}</p></div>
                    <div class="card"><h3>PHP</h3><div class="metric">${esc(app.php)}</div><p class="muted">${esc(server.os || '')}</p></div>
                    <div class="card"><h3>Disk</h3><div class="metric">${used}%</div><p class="muted">${bytes(disk.free)} free</p></div>
                </div>
                <div class="grid cols-2" style="margin-top:14px">
                    <div class="card">
                        <h3>2027 stack</h3>
                        ${Object.entries(stack).map(([k, ok]) => `<div class="check"><span class="badge ${ok ? 'ok' : 'bad'}">${ok ? 'on' : 'off'}</span> ${esc(k)}</div>`).join('') || '<p class="muted">No extra runtime detected.</p>'}
                    </div>
                    <div class="card">
                        <h3>Health</h3>
                        <p>${health.passed || 0} passed · ${health.warnings || 0} warnings · ${health.critical || 0} critical</p>
                        ${(health.checks || []).slice(0, 8).map((c) => `<div class="check"><span class="badge ${c.status === 'ok' ? 'ok' : (c.status === 'warning' ? 'warn' : 'bad')}">${esc(c.status)}</span> ${esc(c.label)}</div>`).join('')}
                    </div>
                    <div class="card">
                        <h3>Recent commands</h3>
                        ${hist.length ? `<table class="table">${hist.map((h) => `<tr><td><code>${esc(h.command)}</code></td><td>${esc(h.status)}</td></tr>`).join('')}</table>` : '<p class="muted">No commands yet.</p>'}
                    </div>
                </div>`;
        },
        terminal() {
            return html`
                <div class="chips" id="termChips">
                    <span class="chip" data-quick="php artisan about">php artisan about</span>
                    <span class="chip" data-quick="php artisan route:list">route:list</span>
                    <span class="chip" data-quick="php artisan schedule:list">schedule:list</span>
                    <span class="chip" data-quick="git status">git status</span>
                    <span class="chip" data-quick="composer diagnose">composer diagnose</span>
                    <span class="chip" data-quick="php -v">php -v</span>
                </div>
                <div class="searchbar">
                    <input id="termSearch" placeholder="Search terminal output…">
                    <button class="ghost" id="exportTerm">Export Log</button>
                    <button class="ghost" id="clearTerm">Clear</button>
                </div>
                <div class="terminal" role="log">
                    <div class="term-head">
                        <span id="cwdLabel">${esc(state.cwd)}</span>
                        <span id="termMeta">idle</span>
                    </div>
                    <div class="term-body" id="termBody"></div>
                    <form class="term-input" id="termForm">
                        <span>$</span>
                        <input id="termInput" autocomplete="off" spellcheck="false" aria-label="Command">
                    </form>
                </div>`;
        },
        artisan() {
            return html`
                <div class="grid cols-2">
                    <div class="card">
                        <div class="searchbar"><input id="artisanSearch" placeholder="Filter commands"></div>
                        <div class="tree" id="artisanTree"><p class="muted">Loading commands…</p></div>
                    </div>
                    <div class="card" id="artisanBuilder"><p class="muted">Select a command to build options.</p></div>
                </div>`;
        },
        macros() {
            const macros = state.bootstrap.macros || {};
            return `<div class="grid cols-2">${Object.entries(macros).map(([id, m]) => `
                <div class="card">
                    <h3>${esc(m.label)}</h3>
                    <p class="muted">${esc(m.description || '')}</p>
                    <pre class="cmd-preview">${esc((m.commands || []).join('\n'))}</pre>
                    <button class="primary" data-macro="${esc(id)}">Run macro</button>
                </div>`).join('')}</div>`;
        },
        git() { return `<div class="card" id="toolPanel">Loading Git…</div>`; },
        composer() { return `<div class="card" id="toolPanel">Loading Composer…</div>`; },
        node() { return `<div class="card" id="toolPanel">Loading Node…</div>`; },
        database() { return `<div class="card" id="toolPanel">Loading database…</div>`; },
        files() { return `<div class="card" id="toolPanel">Loading files…</div>`; },
        logs() { return `<div class="card" id="toolPanel">Loading logs…</div>`; },
        queue() { return `<div class="card" id="toolPanel">Loading queue…</div>`; },
        scheduler() { return `<div class="card" id="toolPanel">Loading schedule…</div>`; },
        backups() { return `<div class="card" id="toolPanel">Loading backups…</div>`; },
        approvals() { return `<div class="card" id="toolPanel">Loading approvals…</div>`; },
        deploy() {
            const dep = state.bootstrap.deployment || {};
            const envs = dep.environments || {};
            return html`<div class="card">
                <div class="field">
                    <select id="deployEnv">${Object.entries(envs).map(([k, v]) => `<option value="${esc(k)}">${esc(v.label || k)}</option>`).join('')}</select>
                    <input id="deployBranch" value="${esc(dep.default_branch || 'main')}">
                    <button class="primary" id="deployBtn">Deploy</button>
                </div>
                <ol>${(dep.steps || []).map((s) => `<li>${esc(s.label)} <code>${esc(s.command)}</code></li>`).join('')}</ol>
                <div id="deployOut"></div>
            </div>`;
        },
        health() { return `<div class="card" id="toolPanel">Running health checks…</div>`; },
        security() {
            const php = state.bootstrap.php || {};
            const env = state.bootstrap.environment || {};
            const cap = state.bootstrap.capabilities || {};
            return html`<div class="grid cols-2">
                <div class="card">
                    <h3>PHP</h3>
                    <p>${esc(php.version)} · ${esc(php.sapi)}</p>
                    <p class="muted">Memory ${esc(php.memory_limit)} · max ${esc(php.max_execution_time)}s</p>
                    <h3>Extensions</h3>
                    ${Object.entries(php.extensions || {}).map(([k, ok]) => `<div class="check"><span class="badge ${ok ? 'ok' : 'bad'}">${ok ? 'on' : 'off'}</span> ${esc(k)}</div>`).join('')}
                    <h3>Disabled functions</h3>
                    <p>${(php.disabled_functions || []).map(esc).join(', ') || 'none listed'}</p>
                </div>
                <div class="card">
                    <h3>Environment</h3>
                    ${Object.entries(env).map(([k, v]) => `<div><code>${esc(k)}</code> = ${esc(v)}</div>`).join('')}
                    <h3>Capabilities</h3>
                    ${Object.entries(cap.checks || {}).map(([, c]) => `<div class="check"><span class="badge ${c.ok ? 'ok' : 'bad'}">${c.ok ? 'ok' : 'no'}</span> ${esc(c.label)}</div>`).join('')}
                </div>
            </div>`;
        },
        audit() { return `<div class="card" id="toolPanel">Loading audit log…</div>`; },
        history() { return `<div class="card" id="toolPanel">Loading history…</div>`; },
    };

    function bindView() {
        if (state.view === 'terminal') bindTerminal();
        if (state.view === 'artisan') loadArtisan();
        if (state.view === 'macros') bindMacros();
        if (state.view === 'git') loadTool('/git', renderGit);
        if (state.view === 'composer') loadTool('/composer', renderComposer);
        if (state.view === 'node') loadTool('/node', renderNode);
        if (state.view === 'database') loadTool('/database', renderDatabase);
        if (state.view === 'files') loadFiles('.');
        if (state.view === 'logs') loadLogs();
        if (state.view === 'queue') loadQueue();
        if (state.view === 'scheduler') loadScheduler();
        if (state.view === 'deploy') bindDeploy();
        if (state.view === 'backups') loadBackups();
        if (state.view === 'approvals') loadApprovals();
        if (state.view === 'health') loadHealth();
        if (state.view === 'audit') loadAudit();
        if (state.view === 'history') loadHistory();
    }

    function bindTerminal() {
        const input = $('termInput');
        const body = $('termBody');
        const form = $('termForm');
        input?.focus();
        $('clearTerm')?.addEventListener('click', () => { body.innerHTML = ''; });
        $('exportTerm')?.addEventListener('click', () => {
            const text = body.innerText || '';
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `webshell-terminal-${Date.now()}.log`;
            a.click();
            URL.revokeObjectURL(url);
        });
        $('termChips')?.addEventListener('click', (e) => {
            const chip = e.target.closest('[data-quick]');
            if (chip && !state.running) {
                runCommand(chip.dataset.quick);
            }
        });
        $('termSearch')?.addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase();
            body.querySelectorAll('.term-line').forEach((line) => {
                line.style.display = !q || line.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const command = input.value.trim();
            if (!command || state.running) return;
            state.history.unshift(command);
            state.histIndex = -1;
            input.value = '';
            await runCommand(command);
        });
        input?.addEventListener('keydown', async (e) => {
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                state.histIndex = Math.min(state.histIndex + 1, state.history.length - 1);
                input.value = state.history[state.histIndex] || '';
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                state.histIndex = Math.max(state.histIndex - 1, -1);
                input.value = state.histIndex === -1 ? '' : (state.history[state.histIndex] || '');
            }
            if (e.key === 'l' && e.ctrlKey) { e.preventDefault(); body.innerHTML = ''; }
            if (e.key === 'c' && e.ctrlKey) { e.preventDefault(); state.abort?.abort(); writeTerm('^C', 'meta'); }
            if (e.key === 'Tab') {
                e.preventDefault();
                const data = await api('/terminal/complete', { method: 'POST', body: JSON.stringify({ input: input.value, working_directory: state.cwd }) });
                if (data.suggestions?.[0]) input.value = data.suggestions[0];
            }
        });
    }

    function writeTerm(text, cls = '') {
        const body = $('termBody');
        if (!body) return;
        const line = document.createElement('div');
        line.className = `term-line ${cls}`;
        line.innerHTML = ansi(text);
        body.appendChild(line);
        body.scrollTop = body.scrollHeight;
    }

    async function runCommand(command, confirmed = false) {
        writeTerm(`$ ${command}`, 'cmd');
        state.running = true;
        const started = performance.now();
        if ($('termMeta')) $('termMeta').textContent = 'running…';
        state.abort = new AbortController();
        try {
            const streamed = await streamCommand(command, confirmed);
            if (streamed) return;
            const data = await api('/terminal', {
                method: 'POST',
                body: JSON.stringify({ command, working_directory: state.cwd, confirmed, source: 'terminal' }),
            });
            renderCommandResult(data, started);
        } catch (err) {
            writeTerm(err.message, 'err');
        } finally {
            state.running = false;
            state.abort = null;
            if ($('termMeta')) $('termMeta').textContent = 'idle';
        }
    }

    async function streamCommand(command, confirmed) {
        try {
            const res = await fetch(`${base}/api/terminal/stream`, {
                method: 'POST',
                headers: {
                    'Accept': 'text/event-stream',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: state.abort?.signal,
                body: JSON.stringify({ command, working_directory: state.cwd, confirmed, source: 'terminal' }),
            });
            if (!res.ok || !res.body) return false;
            const reader = res.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            while (true) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                const parts = buffer.split('\n\n');
                buffer = parts.pop();
                for (const block of parts) {
                    const event = (block.match(/^event: (.+)$/m) || [])[1];
                    const raw = (block.match(/^data: (.+)$/m) || [])[1];
                    if (!raw) continue;
                    const data = JSON.parse(raw);
                    if (event === 'chunk') writeTerm(data.chunk, data.stream === 'stderr' ? 'err' : '');
                    if (event === 'done') renderCommandResult({ ...data, __streamed: true }, performance.now());
                }
            }
            return true;
        } catch {
            return false;
        }
    }

    function renderCommandResult(data, started) {
        if (data.requires_confirmation) {
            askConfirm(data.block_reason || data.error_output || data.message, data.command || '');
            return;
        }
        if (data.status === 'pending_approval') {
            writeTerm(data.output || 'Waiting for approval.', 'meta');
            return;
        }
        if (data.output && !data.__streamed) writeTerm(data.output);
        if (data.error_output) writeTerm(data.error_output, 'err');
        if (data.working_directory) {
            state.cwd = data.working_directory;
            if ($('cwdLabel')) $('cwdLabel').textContent = state.cwd;
        }
        const ms = ((performance.now() - started) / 1000).toFixed(2);
        writeTerm(`exit ${data.exit_code ?? 0} · ${data.duration ?? ms}s`, 'meta');
    }

    function askConfirm(message, command) {
        state.pending = command;
        $('confirmText').textContent = message;
        $('confirmCmd').textContent = command;
        $('confirm').hidden = false;
    }

    async function loadArtisan() {
        const data = await api('/artisan');
        const renderTree = (q = '') => {
            $('artisanTree').innerHTML = (data.groups || []).map((g) => {
                const cmds = g.commands.filter((c) => !q || c.name.includes(q) || (c.description || '').toLowerCase().includes(q));
                if (!cmds.length) return '';
                return `<details open><summary>${esc(g.namespace)}</summary>${cmds.map((c) => `<button class="link" data-cmd="${esc(c.name)}">${esc(c.name)} <span class="muted">${esc(c.description)}</span></button>`).join('')}</details>`;
            }).join('');
            $('artisanTree').onclick = (e) => {
                const btn = e.target.closest('[data-cmd]');
                if (btn) showArtisanBuilder(btn.dataset.cmd);
            };
        };
        renderTree();
        $('artisanSearch')?.addEventListener('input', (e) => renderTree(e.target.value.toLowerCase()));
    }

    async function showArtisanBuilder(name) {
        const def = await api(`/artisan/${encodeURIComponent(name)}`);
        state.artisanDef = def;
        $('artisanBuilder').innerHTML = html`
            <h3>${esc(def.name)}</h3>
            <p class="muted">${esc(def.description)}</p>
            ${(def.arguments || []).map((a) => `<label class="field">${esc(a.name)}${a.required ? ' *' : ''}<input data-arg="${esc(a.name)}" placeholder="${esc(a.description)}"></label>`).join('')}
            ${(def.options || []).map((o) => o.accepts_value
                ? `<label class="field">--${esc(o.name)}<input data-opt="${esc(o.name)}" placeholder="${esc(o.description)}"></label>`
                : `<label class="check"><input type="checkbox" data-flag="${esc(o.name)}"> --${esc(o.name)} <span class="muted">${esc(o.description)}</span></label>`
            ).join('')}
            <button class="primary" id="runArtisan">Run command</button>
            <pre class="cmd-preview" id="artisanPreview">php artisan ${esc(def.name)}</pre>`;
        $('runArtisan').onclick = () => runArtisan(def);
    }

    async function runArtisan(def) {
        const parameters = {};
        document.querySelectorAll('[data-arg]').forEach((el) => { if (el.value) parameters[el.dataset.arg] = el.value; });
        document.querySelectorAll('[data-opt]').forEach((el) => { if (el.value) parameters[el.dataset.opt] = el.value; });
        document.querySelectorAll('[data-flag]').forEach((el) => { if (el.checked) parameters[el.dataset.flag] = true; });
        const data = await api('/artisan', { method: 'POST', body: JSON.stringify({ command: def.name, parameters, confirmed: true }) });
        $('artisanPreview').textContent = (data.output || '') + (data.error_output || data.message || '');
        if (data.requires_confirmation) askConfirm(data.block_reason || data.message, `php artisan ${def.name}`);
    }

    function bindMacros() {
        viewEl.onclick = async (e) => {
            const btn = e.target.closest('[data-macro]');
            if (!btn) return;
            const data = await api('/macros', { method: 'POST', body: JSON.stringify({ macro: btn.dataset.macro, confirmed: true }) });
            alert((data.results || []).map((r) => r.command + '\n' + (r.combined_output || r.error_output || '')).join('\n\n') || data.message);
        };
    }

    async function loadTool(path, renderer) {
        try {
            renderer(await api(path));
        } catch (e) {
            $('toolPanel').innerHTML = `<p class="muted">${esc(e.message)}</p>`;
        }
    }

    function renderGit(data) {
        $('toolPanel').innerHTML = data.available === false
            ? `<p>${esc(data.message)}</p>`
            : html`<pre class="cmd-preview">${esc(data.status_output)}</pre>
                <div class="row-actions">
                    <button class="ghost" data-git="status">Status</button>
                    <button class="ghost" data-git="fetch">Fetch</button>
                    <button class="ghost" data-git="pull">Pull</button>
                    <button class="ghost" data-git="log -n 15 --oneline">Log</button>
                </div>
                <h3>Branches</h3><pre class="cmd-preview">${esc(data.branch_output)}</pre>
                <h3>Log</h3><pre class="cmd-preview">${esc(data.log_output)}</pre>
                <div id="gitOut"></div>`;
        $('toolPanel').onclick = async (e) => {
            const btn = e.target.closest('[data-git]');
            if (!btn) return;
            const res = await api('/git', { method: 'POST', body: JSON.stringify({ arguments: btn.dataset.git, confirmed: true }) });
            $('gitOut').innerHTML = `<pre class="cmd-preview">${esc(res.combined_output || res.error_output || res.message)}</pre>`;
        };
    }

    function renderComposer(data) {
        $('toolPanel').innerHTML = data.available === false
            ? `<p>${esc(data.message)}</p>`
            : html`<div class="row-actions">
                    <button class="ghost" data-c="install --no-interaction">Install</button>
                    <button class="ghost" data-c="dump-autoload">Dump autoload</button>
                    <button class="ghost" data-c="validate">Validate</button>
                    <button class="ghost" data-c="diagnose">Diagnose</button>
                </div>
                <table class="table">${Object.entries(data.require || {}).map(([k, v]) => `<tr><td>${esc(k)}</td><td>${esc(v)}</td></tr>`).join('')}</table>
                <div id="compOut"></div>`;
        $('toolPanel').onclick = async (e) => {
            const btn = e.target.closest('[data-c]');
            if (!btn) return;
            const res = await api('/composer', { method: 'POST', body: JSON.stringify({ arguments: btn.dataset.c, confirmed: true }) });
            $('compOut').innerHTML = `<pre class="cmd-preview">${esc(res.combined_output || res.error_output || res.message)}</pre>`;
        };
    }

    function renderNode(data) {
        const mgr = data.package_manager || 'npm';
        $('toolPanel').innerHTML = data.available === false
            ? `<p>${esc(data.message)}</p>`
            : html`<p><span class="badge ok">${esc(mgr)}</span> detected · package: <strong>${esc(data.name || 'unnamed')}</strong></p>
                <div class="row-actions">
                    <button class="ghost" data-n="install">${esc(mgr)} install</button>
                    <button class="ghost" data-n="run build">${esc(mgr)} run build</button>
                    <button class="ghost" data-n="outdated">${esc(mgr)} outdated</button>
                    <button class="ghost" data-n="audit">${esc(mgr)} audit</button>
                </div>
                <h3>Package scripts</h3>
                <div class="chips">
                    ${Object.keys(data.scripts || {}).map((s) => `<span class="chip" data-n="run ${esc(s)}">${esc(mgr)} run ${esc(s)}</span>`).join('') || '<p class="muted">No scripts defined.</p>'}
                </div>
                <div id="nodeOut"></div>`;
        $('toolPanel').onclick = async (e) => {
            const btn = e.target.closest('[data-n]');
            if (!btn) return;
            const res = await api('/node', { method: 'POST', body: JSON.stringify({ arguments: btn.dataset.n, confirmed: true }) });
            $('nodeOut').innerHTML = `<pre class="cmd-preview">${esc(res.combined_output || res.error_output || res.message)}</pre>`;
        };
    }

    function renderDatabase(data) {
        $('toolPanel').innerHTML = data.available === false
            ? `<p>${esc(data.message)}</p>`
            : html`<p>${esc(data.driver)} · ${esc(data.database)} · mode <span class="badge ${data.mode === 'administrator' ? 'warn' : 'ok'}">${esc(data.mode)}</span></p>
                <div class="field"><textarea id="sqlInput" class="editor" placeholder="SELECT … (read-only unless enabled)"></textarea><button class="ghost" id="runSql">Run SQL</button></div>
                <h3>Tables (${(data.tables || []).length})</h3>
                <table class="table">
                    <tr><th>Table Name</th><th>Row Count</th><th>Export</th></tr>
                    ${(data.tables || []).map((t) => `<tr>
                        <td><button class="link" data-table="${esc(t.name)}">${esc(t.name)}</button></td>
                        <td>${t.rows ?? '—'}</td>
                        <td class="row-actions">
                            <a class="ghost btn-sm" href="${base}/api/database/${encodeURIComponent(t.name)}/export?format=json" download>JSON</a>
                            <a class="ghost btn-sm" href="${base}/api/database/${encodeURIComponent(t.name)}/export?format=csv" download>CSV</a>
                        </td>
                    </tr>`).join('')}
                </table>
                <div id="dbOut"></div>`;
        $('runSql')?.addEventListener('click', async () => {
            const res = await api('/database/query', { method: 'POST', body: JSON.stringify({ sql: $('sqlInput').value, confirmed: true }) });
            $('dbOut').innerHTML = `<pre class="cmd-preview">${esc(JSON.stringify(res.rows || res, null, 2))}</pre>`;
        });
        $('toolPanel').onclick = async (e) => {
            const btn = e.target.closest('[data-table]');
            if (!btn) return;
            const res = await api(`/database/${encodeURIComponent(btn.dataset.table)}`);
            const cols = res.schema?.columns || [];
            const colBadges = cols.length
                ? `<div style="margin:10px 0;display:flex;gap:6px;flex-wrap:wrap"><strong>Columns:</strong> ` + cols.map(c => typeof c === 'object' ? `<span class="code-tag">${esc(c.name)} (${esc(c.type_name || c.type || 'any')}${c.nullable ? '?' : ''})</span>` : `<span class="code-tag">${esc(c)}</span>`).join('') + `</div>`
                : '';
            $('dbOut').innerHTML = `<h3>Table: ${esc(btn.dataset.table)} (${(res.rows || []).length} rows previewed)</h3>${colBadges}<pre class="cmd-preview">${esc(JSON.stringify(res.rows || [], null, 2))}</pre>`;
        };
    }

    async function loadFiles(path) {
        path = path || '.';
        const data = await api(`/files?path=${encodeURIComponent(path)}`);
        const parts = (data.path || '.').split(/[\/\\]/).filter(Boolean);
        let crumbPath = '';
        const breadcrumbs = ['<button data-goto=".">root</button>'].concat(
            parts.map((p) => {
                crumbPath += (crumbPath ? '/' : '') + p;
                const target = crumbPath;
                return ` / <button data-goto="${esc(target)}">${esc(p)}</button>`;
            })
        ).join('');

        $('toolPanel').innerHTML = html`
            <div class="breadcrumbs">${breadcrumbs}</div>
            <div class="toolbar">
                <button class="ghost btn-sm" id="newFolderBtn">+ New Folder</button>
                <button class="ghost btn-sm" id="uploadFileBtn">Upload File</button>
                <input type="file" id="fileUploader" style="display:none">
                <button class="ghost btn-sm" id="refreshFilesBtn">Refresh</button>
            </div>
            <table class="table">
                <tr><th>Name</th><th>Size</th><th>Modified</th><th>Actions</th></tr>
                ${(data.entries || []).map((f) => `<tr>
                    <td><button class="link" data-path="${esc(f.path)}" data-type="${f.type}" ${f.protected ? 'disabled' : ''}>${f.type === 'directory' ? '📁' : '📄'} ${esc(f.name)}</button></td>
                    <td>${f.type === 'file' ? bytes(f.size) : '—'}</td>
                    <td>${f.modified || ''}</td>
                    <td class="row-actions">
                        ${f.type === 'file' ? `<a class="ghost btn-sm" href="${base}/api/files/download?path=${encodeURIComponent(f.path)}">Download</a>` : ''}
                        <button class="ghost btn-sm" data-rename="${esc(f.path)}" data-name="${esc(f.name)}">Rename</button>
                        <button class="danger btn-sm" data-del="${esc(f.path)}">Delete</button>
                    </td>
                </tr>`).join('')}
            </table>
            <div id="fileOut"></div>`;

        $('refreshFilesBtn')?.addEventListener('click', () => loadFiles(path));

        $('newFolderBtn')?.addEventListener('click', async () => {
            const name = prompt('Folder name:');
            if (!name) return;
            const newPath = (path === '.' ? '' : path + '/') + name;
            await api('/files/directory', { method: 'POST', body: JSON.stringify({ path: newPath }) });
            loadFiles(path);
        });

        $('uploadFileBtn')?.addEventListener('click', () => $('fileUploader').click());
        $('fileUploader')?.addEventListener('change', async (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            const form = new FormData();
            form.append('directory', path);
            form.append('file', file);
            await fetch(`${base}/api/files/upload`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: form,
            });
            loadFiles(path);
        });

        $('toolPanel').onclick = async (e) => {
            const goto = e.target.closest('[data-goto]');
            if (goto) return loadFiles(goto.dataset.goto);

            const del = e.target.closest('[data-del]');
            if (del) {
                if (confirm(`Delete ${del.dataset.del}?`)) {
                    await api(`/files?path=${encodeURIComponent(del.dataset.del)}`, { method: 'DELETE' });
                    loadFiles(path);
                }
                return;
            }

            const ren = e.target.closest('[data-rename]');
            if (ren) {
                const to = prompt('New name / path:', ren.dataset.name);
                if (to && to !== ren.dataset.name) {
                    const targetTo = (path === '.' ? '' : path + '/') + to;
                    await api('/files/rename', { method: 'POST', body: JSON.stringify({ from: ren.dataset.rename, to: targetTo }) });
                    loadFiles(path);
                }
                return;
            }

            const btn = e.target.closest('[data-path]');
            if (!btn) return;
            if (btn.dataset.type === 'directory') return loadFiles(btn.dataset.path);
            const file = await api(`/files/show?path=${encodeURIComponent(btn.dataset.path)}`);
            $('fileOut').innerHTML = `<p><strong>Editing:</strong> <code>${esc(btn.dataset.path)}</code> (${bytes(file.size)})</p><textarea class="editor" id="fileEditor">${esc(file.content || '')}</textarea><button class="primary" id="saveFile">Save</button>`;
            $('saveFile').onclick = async () => {
                await api('/files', { method: 'POST', body: JSON.stringify({ path: btn.dataset.path, content: $('fileEditor').value }) });
                $('saveFile').textContent = 'Saved!';
                setTimeout(() => { $('saveFile').textContent = 'Save'; }, 2000);
            };
        };
    }

    async function loadLogs(filterLevel = '') {
        const data = await api('/logs');
        const entries = data.log?.entries || [];
        const filtered = filterLevel ? entries.filter(e => e.level === filterLevel) : entries;
        $('toolPanel').innerHTML = html`
            <div class="toolbar">
                <div class="chips">
                    <span class="chip ${!filterLevel ? 'active' : ''}" data-lvl="">ALL</span>
                    <span class="chip ${filterLevel === 'ERROR' ? 'active' : ''}" data-lvl="ERROR">ERROR</span>
                    <span class="chip ${filterLevel === 'WARNING' ? 'active' : ''}" data-lvl="WARNING">WARNING</span>
                    <span class="chip ${filterLevel === 'INFO' ? 'active' : ''}" data-lvl="INFO">INFO</span>
                </div>
                <div class="row-actions" style="margin-left:auto">
                    ${(data.files || []).map((f) => `<button class="ghost btn-sm" data-log="${esc(f)}">${esc(f)}</button>`).join('')}
                    <button class="ghost btn-sm" id="refreshLogs">Refresh</button>
                    <button class="danger btn-sm" id="clearLogs">Clear</button>
                </div>
            </div>
            ${filtered.map((e) => `<div class="check"><span class="badge ${e.level === 'ERROR' ? 'bad' : (e.level === 'WARNING' ? 'warn' : 'ok')}">${esc(e.level)}</span> <span class="muted">${esc(e.timestamp)}</span> ${esc(e.message)}</div>`).join('') || `<pre class="cmd-preview">${esc(data.log?.raw || 'No log entries found.')}</pre>`}`;
        $('refreshLogs')?.addEventListener('click', () => loadLogs(filterLevel));
        $('clearLogs')?.addEventListener('click', async () => { await api('/logs/clear', { method: 'POST', body: '{}' }); loadLogs(filterLevel); });
        $('toolPanel').onclick = (e) => {
            const chip = e.target.closest('[data-lvl]');
            if (chip) loadLogs(chip.dataset.lvl);
            const logBtn = e.target.closest('[data-log]');
            if (logBtn) loadLogs(filterLevel);
        };
    }

    async function loadQueue() {
        const data = await api('/queue');
        $('toolPanel').innerHTML = html`<p>Connection <strong>${esc(data.connection)}</strong> · pending <span class="badge ok">${data.pending}</span> · failed <span class="badge ${data.failed > 0 ? 'bad' : 'ok'}">${data.failed}</span></p>
            <div class="row-actions">
                <button class="ghost" data-q="queue:restart">Restart workers</button>
                <button class="ghost" data-q="queue:retry all">Retry failed</button>
                <button class="ghost" id="showFailedJobs">Inspect failed jobs</button>
                <button class="danger" data-q="queue:flush">Flush failed</button>
            </div>
            <div id="qOut"></div>`;
        $('showFailedJobs')?.addEventListener('click', async () => {
            const failed = await api('/queue/failed');
            const list = failed.failed || [];
            $('qOut').innerHTML = list.length
                ? `<h3>Failed Jobs (${list.length})</h3><table class="table"><tr><th>ID</th><th>Queue</th><th>Failed At</th><th>Exception</th></tr>${list.map(j => `<tr><td>${j.id}</td><td>${esc(j.queue)}</td><td>${esc(j.failed_at)}</td><td><pre class="cmd-preview" style="max-height:100px">${esc(j.exception?.slice(0, 300))}</pre></td></tr>`).join('')}</table>`
                : '<p class="muted">No failed jobs recorded in failed_jobs table.</p>';
        });
        $('toolPanel').onclick = async (e) => {
            const btn = e.target.closest('[data-q]');
            if (!btn) return;
            const res = await api('/queue', { method: 'POST', body: JSON.stringify({ command: btn.dataset.q, confirmed: true }) });
            $('qOut').innerHTML = `<pre class="cmd-preview">${esc(res.combined_output || res.error_output || res.message)}</pre>`;
        };
    }

    async function loadScheduler() {
        const data = await api('/scheduler');
        $('toolPanel').innerHTML = `<pre class="cmd-preview">${esc(data.combined_output || data.error_output || data.message)}</pre>`;
    }

    function bindDeploy() {
        $('deployBtn')?.addEventListener('click', async () => {
            const env = $('deployEnv').value;
            if (env === 'production' && !confirm('Deploy to PRODUCTION?')) return;
            const data = await api('/deploy', { method: 'POST', body: JSON.stringify({ environment: env, branch: $('deployBranch').value, confirmed: true }) });
            $('deployOut').innerHTML = (data.steps || []).map((s) => `<div class="check"><span class="badge ${s.successful ? 'ok' : 'bad'}">${s.successful ? 'ok' : 'fail'}</span> ${esc(s.label)} (${s.duration}s)</div>`).join('') + `<p>Status: ${esc(data.status)} · ${data.duration || 0}s</p>`;
        });
    }

    async function loadHealth() {
        const data = await api('/doctor');
        $('toolPanel').innerHTML = html`${(data.health?.checks || []).map((c) => `<div class="check"><span class="badge ${c.status === 'ok' ? 'ok' : (c.status === 'warning' ? 'warn' : 'bad')}">${esc(c.status)}</span> ${esc(c.label)} — ${esc(c.detail)}</div>`).join('')}
            ${(data.recommendations || []).map((r) => `<div class="alert"><strong>${esc(r.title)}</strong><div>${esc(r.recommendation)}</div></div>`).join('')}`;
    }

    async function loadAudit() {
        const data = await api('/audit');
        $('toolPanel').innerHTML = `<div class="row-actions"><a class="ghost" href="${base}/api/audit/export?format=json">Export JSON</a><a class="ghost" href="${base}/api/audit/export?format=csv">Export CSV</a></div>
            <table class="table"><tr><th>Time</th><th>Command</th><th>Status</th><th>IP</th></tr>${(data.records || []).map((r) => `<tr><td>${esc(r.created_at)}</td><td><code>${esc(r.command)}</code></td><td>${esc(r.status)}</td><td>${esc(r.ip || '')}</td></tr>`).join('')}</table>`;
    }

    async function loadBackups() {
        const data = await api('/backups');
        $('toolPanel').innerHTML = html`<div class="row-actions"><button class="primary" id="makeBackup">Create backup</button></div>
            <table class="table">
                <tr><th>Archive Name</th><th>Size</th><th>Created</th><th>Actions</th></tr>
                ${(data.backups || []).map((b) => `<tr>
                    <td><code>${esc(b.name)}</code></td>
                    <td>${bytes(b.size)}</td>
                    <td>${esc(b.modified || '')}</td>
                    <td class="row-actions">
                        <a class="ghost btn-sm" href="${base}/api/backups/${encodeURIComponent(b.name)}">Download</a>
                        <button class="danger btn-sm" data-delbackup="${esc(b.name)}">Delete</button>
                    </td>
                </tr>`).join('') || '<tr><td colspan="4" class="muted">No backups yet.</td></tr>'}
            </table>`;
        $('makeBackup')?.addEventListener('click', async () => {
            $('makeBackup').textContent = 'Creating backup…';
            await api('/backups', { method: 'POST', body: JSON.stringify({ parts: ['database', 'storage'] }) });
            loadBackups();
        });
        $('toolPanel').onclick = async (e) => {
            const del = e.target.closest('[data-delbackup]');
            if (!del) return;
            if (confirm(`Delete backup ${del.dataset.delbackup}?`)) {
                await api(`/backups/${encodeURIComponent(del.dataset.delbackup)}`, { method: 'DELETE' });
                loadBackups();
            }
        };
    }

    async function loadApprovals() {
        const data = await api('/approvals');
        $('toolPanel').innerHTML = (data.pending || []).length
            ? `<table class="table">${data.pending.map((r) => `<tr><td><code>${esc(r.command)}</code></td><td>${esc(r.created_at)}</td><td><button class="primary" data-approve="${esc(r.id)}">Approve</button> <button class="ghost" data-reject="${esc(r.id)}">Reject</button></td></tr>`).join('')}</table>`
            : '<p class="muted">No pending approvals.</p>';
        $('toolPanel').onclick = async (e) => {
            const approve = e.target.closest('[data-approve]');
            const reject = e.target.closest('[data-reject]');
            if (approve) await api(`/approvals/${approve.dataset.approve}/approve`, { method: 'POST', body: '{}' });
            if (reject) await api(`/approvals/${reject.dataset.reject}/reject`, { method: 'POST', body: '{}' });
            if (approve || reject) loadApprovals();
        };
    }

    async function loadHistory() {
        const data = await api('/history');
        $('toolPanel').innerHTML = `<table class="table">${(data.records || []).map((r) => `<tr><td><code>${esc(r.command)}</code></td><td>${esc(r.status)}</td><td>${r.duration}s</td><td><button class="ghost" data-rerun="${esc(r.command)}">Rerun</button></td></tr>`).join('')}</table>`;
        $('toolPanel').onclick = (e) => {
            const btn = e.target.closest('[data-rerun]');
            if (btn) { setView('terminal'); setTimeout(() => runCommand(btn.dataset.rerun), 50); }
        };
    }

    function openPalette() {
        const items = (state.bootstrap.palette || []).concat(
            Object.entries(state.bootstrap.macros || {}).map(([id, m]) => ({ id, label: m.label, macro: id }))
        );
        const list = $('paletteList');
        const input = $('paletteInput');
        const draw = (q = '') => {
            const filtered = items.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()));
            list.innerHTML = filtered.map((i, idx) => `<li class="${idx === 0 ? 'active' : ''}" data-idx="${idx}">${esc(i.label)}</li>`).join('');
            list.onclick = (e) => {
                const li = e.target.closest('li');
                if (li) runPalette(filtered[Number(li.dataset.idx)]);
            };
            input.onkeydown = (e) => {
                if (e.key === 'Enter' && filtered[0]) runPalette(filtered[0]);
                if (e.key === 'Escape') $('palette').hidden = true;
            };
        };
        $('palette').hidden = false;
        input.value = '';
        input.focus();
        input.oninput = () => draw(input.value);
        draw();
    }

    async function runPalette(item) {
        $('palette').hidden = true;
        if (item.macro) {
            await api('/macros', { method: 'POST', body: JSON.stringify({ macro: item.macro, confirmed: true }) });
            setView('macros');
            return;
        }
        if (item.command) {
            setView('terminal');
            setTimeout(() => runCommand(item.command, item.dangerous), 40);
        }
    }

    function bindChrome() {
        $('paletteBtn').onclick = openPalette;
        $('menuBtn').onclick = () => $('sidebar').classList.toggle('open');
        $('themeBtn').onclick = () => {
            const themes = state.bootstrap.ui?.themes || ['dark', 'light'];
            const cur = document.documentElement.dataset.theme;
            const next = themes[(themes.indexOf(cur) + 1) % themes.length];
            document.documentElement.dataset.theme = next;
        };
        $('lockBtn').onclick = () => { $('lockScreen').hidden = false; };
        $('unlockBtn').onclick = () => { $('lockScreen').hidden = true; };
        $('confirmCancel').onclick = () => { $('confirm').hidden = true; state.pending = null; };
        $('confirmOk').onclick = async () => {
            const cmd = state.pending;
            $('confirm').hidden = true;
            state.pending = null;
            if (cmd) await runCommand(cmd, true);
        };
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || (e.shiftKey && e.key.toLowerCase() === 'p'))) {
                e.preventDefault();
                openPalette();
            }
            state.lastActivity = Date.now();
        });
        document.addEventListener('pointerdown', () => { state.lastActivity = Date.now(); });
        $('mobileKeys').onclick = (e) => {
            const key = e.target.dataset.key;
            const input = $('termInput');
            if (!input || !key) return;
            if (key === 'ArrowUp' || key === 'ArrowDown') input.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
            if (key === 'Tab') input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }));
            if (key === 'CtrlC') { state.abort?.abort(); writeTerm('^C', 'meta'); }
            if (key === 'CtrlL' && $('termBody')) $('termBody').innerHTML = '';
        };
        const timeout = (state.bootstrap.app?.session_timeout || 30) * 60 * 1000;
        if (state.bootstrap.app?.auto_lock) {
            setInterval(() => {
                if (Date.now() - state.lastActivity > timeout) $('lockScreen').hidden = false;
            }, 5000);
        }
    }

    async function boot() {
        try {
            state.bootstrap = await api('/bootstrap');
            state.cwd = state.bootstrap.terminal?.working_directory || '';
            $('edition').textContent = state.bootstrap.app?.edition || 'community';
            $('envChip').textContent = state.bootstrap.app?.environment || 'local';
            $('envChip').classList.toggle('production', state.bootstrap.app?.environment === 'production');
            $('pageSub').textContent = state.bootstrap.application?.name || 'Laravel WebShell';
            navHtml();
            renderAlerts();
            bindChrome();
            if (state.bootstrap.app?.pwa && 'serviceWorker' in navigator) {
                navigator.serviceWorker.register(`${base}/sw.js`).catch(() => {});
            }
            render();
        } catch (e) {
            viewEl.innerHTML = `<div class="card"><h3>Unable to start WebShell</h3><p>${esc(e.message)}</p></div>`;
        }
    }

    boot();
})();
