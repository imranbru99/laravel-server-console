# Laravel WebShell — Modern 2027 Era Server Operations & Web Terminal

[![Latest Version on Packagist](https://img.shields.io/packagist/v/imrandevbd/laravel-webshell.svg?style=flat-square)](https://packagist.org/packages/imrandevbd/laravel-webshell)
[![Total Downloads](https://img.shields.io/packagist/dt/imrandevbd/laravel-webshell.svg?style=flat-square)](https://packagist.org/packages/imrandevbd/laravel-webshell)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%20--%208.5-777bb4.svg?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-10%20|%2011%20|%2012%20|%2013-ff2d20.svg?style=flat-square&logo=laravel)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-Passing%20(23%20tests%2C%2060%20assertions)-brightgreen.svg?style=flat-square)]()

**A secure, modern browser-based terminal, automated deployment console, and server operations platform designed for Laravel environments without direct SSH access.**

Engineered specifically for the modern 2027 cloud and hosting landscape: **Laravel 10, 11, 12, and 13**, **PHP 8.2 through 8.5 with JIT compiler analysis**, **Octane (Swoole/RoadRunner)**, **FrankenPHP / Caddy**, **Laravel Cloud**, **Coolify**, **Railway**, **Fly.io**, **Render**, **Docker & Kubernetes**, **Herd**, **Sail**, **Reverb**, **Pulse**, **Nightwatch**, **Bun**, **pnpm**, **Yarn**, **Vite**, **Larastan / PHPStan**, **Pint**, and **Pest**.

> **Important Security Architecture**: Laravel WebShell is **never** a blind `shell_exec()` wrapper. Every action traverses a unified, multi-tiered security pipeline:

```text
Incoming Command / Action
  │
  ├── 1. Command Syntax & Argument Validation (CommandValidator)
  ├── 2. Authorization & RBAC Checks (RoleService / Gates / Policies)
  ├── 3. Dangerous Pattern Detection (DangerousCommandDetector + Cloud/Container Patterns)
  ├── 4. Working Directory Sandboxing (WorkingDirectoryGuard)
  ├── 5. Protected Files & Secrets Masking (SecretMasker)
  ├── 6. Approval Gate (Optional Multi-User Staging/Production Workflow)
  ├── 7. Isolated Process Engine (ProcessManager with strict timeouts & output caps)
  ├── 8. Real-Time Streaming (SSE with chunk buffering and ANSI parsing)
  ├── 9. Immutable Audit Logging (AuditLogger with CSV/JSON exports)
  └── 10. Webhook & Alert Dispatchers (Slack, Mail, Custom Channels)
```

> **No SSH required does not mean “bypass server restrictions.”** PHP still needs permission to run the processes you ask for. If `proc_open()` or process execution is disabled, WebShell automatically enters **Restricted Mode** — diagnostics, log viewing, health checks, and database analysis continue working safely without crashing.

---

## ⚡ Key Highlights (2027 Edition)

- **Universal 2027 Runtime Support**: Native auto-detection and execution for **Bun**, **pnpm**, **Yarn**, **npm**, and **Deno** alongside Composer.
- **Deep Cloud & Container Awareness**: Automatic environment fingerprinting for **Coolify**, **Railway**, **Fly.io**, **Render**, **Laravel Cloud**, **Forge**, **Vapor**, **Docker**, **Kubernetes**, **Herd**, **Sail**, and shared hosts (cPanel, Plesk, DirectAdmin, CyberPanel, CloudPanel).
- **PHP 8.5 JIT Compiler Monitoring**: Live inspection of OPcache JIT status, buffer allocations, memory usage, and architecture constraints.
- **Modern 2027 Themes**: Ultra-sleek curated colorways including **Cyberpunk**, **Emerald**, **Nord**, **Dracula**, **Monokai**, **Solarized**, **Light**, **Dark**, and **High-Contrast**.
- **Interactive File Manager**: Full breadcrumb path traversal, in-browser code editor, file upload, file download, folder creation, renaming, and safe deletion (with `.env` and secrets strictly blocked).
- **Database Explorer & Exporter**: Schema explorer with column datatypes, primary key badges, nullable indicators, read-only/write query runner, and **one-click CSV / JSON data export**.
- **Queue Diagnostics & Failed Jobs**: View worker connections, count pending/failed jobs, inspect the `failed_jobs` table with stack trace snippets, restart workers, retry failed jobs, or flush queues.
- **Backup Management**: Fast Zip-archive creation of SQLite database, application storage, and public uploads with instant browser download and archive deletion.
- **Terminal Productivity**: Quick-command chips (`php artisan about`, `route:list`, `schedule:list`, `git status`, `composer diagnose`), live terminal text export, search filter, and command auto-completion.
- **Static Analysis & QA Pipeline**: Pre-configured palette commands for **Larastan / PHPStan** analysis, **Laravel Pint** linting, and **Pest / PHPUnit** test execution.

---

## 📋 Requirements

| Component | Minimum Supported Version | Recommended / Modern Version |
| --- | --- | --- |
| **PHP** | 8.2.0 | 8.3 / 8.4 / 8.5 (with OPcache JIT) |
| **Laravel** | 10.0 | 11.x, 12.x, or 13.x |
| **Node / Runtime** | Node 18+ | Node 20+, Bun 1.x, or pnpm 9.x |
| **PHP Extensions** | `fileinfo`, `json`, `mbstring` | `zip`, `pdo`, `curl`, `pcntl`, `opcache` |

---

## 🚀 Quick Start

### 1. Install via Composer

```bash
composer require imrandevbd/laravel-webshell
```

### 2. Run the Interactive Installer

```bash
php artisan webshell:install
```

The installer publishes the configuration (`config/webshell.php`), initializes the secure storage directory (`storage/app/webshell`), and executes an environment capability diagnostic.

### 3. Configure Authentication & Access Control

By default, WebShell is disabled in production. Enable it only after configuring authorization:

```env
# .env
WEBSHELL_ENABLED=true
WEBSHELL_PATH=webshell
```

In your `AppServiceProvider` or `AuthServiceProvider`, define who can access WebShell:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('webshell.access', function ($user) {
        // Only allow designated super-administrators or DevOps engineers
        return $user->email === 'admin@yourcompany.com' || $user->hasRole('devops');
    });
}
```

You can also implement `canAccessWebShell(): bool` or `webshellRole(): string` on your `User` model.

### 4. Open WebShell

Navigate to `https://your-domain.com/webshell` in your browser.

### 5. CLI Management Commands

| Command | Purpose |
| --- | --- |
| `php artisan webshell:install` | Publish config, create storage, run diagnostics |
| `php artisan webshell:doctor` | Comprehensive capability + Laravel health report |
| `php artisan webshell:disable` | Emergency kill switch (`WEBSHELL_ENABLED=false` in `.env`) |
| `php artisan webshell:token` | Generate a **scoped** API token (never `*` wildcard) |

```bash
# Emergency kill switch: immediately returns 404
php artisan webshell:disable
```

---

## 🛠️ Complete Feature Matrix (Everything WebShell Handles)

Below is the exhaustive catalog of features and capabilities built into Laravel WebShell:

### 1. Core Web Terminal
Modern browser terminal designed specifically for servers without SSH access:
- **Streaming Output (SSE)**: Standard output and standard error stream live via Server-Sent Events with automatic fallback to single-response HTTP.
- **ANSI Color Rendering**: High-fidelity terminal colors and formatting.
- **Quick-Action Chips**: Instant execution of standard commands (`php artisan about`, `route:list`, `schedule:list`, `git status`, `composer diagnose`, `php -v`).
- **Export Terminal Buffer**: Download the live terminal output as a timestamped `.log` file.
- **Command History**: Navigate previous commands with `↑` and `↓` arrow keys or the history sidebar.
- **Jailed Working Directory**: Real `cd` and `pwd` execution strictly jailed to allowed directory roots (`allowed_roots`).
- **Tab Auto-Completion**: Autocompletes Artisan commands and directory file paths.
- **Keyboard Accelerators**: `Ctrl+C` aborts running process, `Ctrl+L` clears output, `Ctrl+K` opens palette.
- **Mobile Virtual Bar**: Touchscreen auxiliary buttons for Tab, arrows, Ctrl+C, and clear.
- **Configurable Limits**: Custom execution timeouts (`WEBSHELL_TIMEOUT`), idle timeouts, and maximum output buffer size (`WEBSHELL_MAX_OUTPUT`).

### 2. Laravel Artisan Console
- **Complete Command Discovery**: Automatically discovers every Artisan command registered in the Laravel container, including commands from vendor packages.
- **Namespace Grouping**: Commands categorized by namespace (App, Cache, Config, Database, Make, Migrate, Queue, Route, Schedule, View, etc.).
- **Search & Filter**: Real-time filtering by command name or description.
- **Hidden Command Awareness**: Respects command visibility settings.

### 3. Artisan Command Builder
- **Symfony Console Reflection**: Dynamically reads arguments and options from the Symfony Console definition.
- **Interactive Flags**: Checkboxes for boolean flags (`--force`, `--pretend`, `--seed`).
- **Input Fields**: Input fields for parameterized options (`--step`, `--path`, `--class`).
- **One-Click Execution**: Generates and executes the command string through the safe process engine.

### 4. Command Palette (`Ctrl+K` / `Ctrl+Shift+P`)
- Global spotlight command palette accessible from anywhere in the UI.
- Built-in shortcuts for cache clearing, migrations, git pull, composer install, frontend builds, queue restarting, and system diagnostics.
- Quick QA tools: **Laravel Pint**, **Pest Tests**, **Larastan / PHPStan**, **Composer Audit**, **NPM Audit**.
- Custom macros and registered plugin actions seamlessly appear in the search results.

### 5. Command History
- **Persistent Search**: Search past commands by command string, status, or date.
- **One-Click Rerun**: Rerun any previous command with one click.
- **Favorites & Retention**: Bookmark frequently used commands; auto-pruning based on `history_limit`.
- **Flexible Storage Backends**: Store history in `file`, `redis`, `database`, or `disabled`.

### 6. Saved Commands & Macros
Config-defined multi-step execution pipelines:
- **Deploy Production**: `git pull` → `composer install --no-dev` → `migrate --force` → `optimize`.
- **Clear Everything**: `php artisan optimize:clear`.
- **Build Frontend**: `npm ci` → `npm run build` (or `bun install` → `bun run build`).
- Custom macros can be registered via `config/webshell.php` or dynamically via the developer API.

### 7. Automated Deployment Pipeline
- **Environment & Branch Selector**: Target `production`, `staging`, or `development` branches with confirmation barriers.
- **Step-by-Step Execution**: Live visual progress indicators showing duration and status for each step.
- **Standard Deployment Steps**:
  1. Maintenance mode on (`php artisan down --retry=60`)
  2. Git pull (`git pull`)
  3. Composer dependencies (`composer install --no-dev --optimize-autoloader --no-interaction`)
  4. Database migration (`php artisan migrate --force`)
  5. Frontend asset compilation (`npm run build` or `bun run build`)
  6. Cache clearing (`php artisan optimize:clear`)
  7. Optimization (`php artisan optimize`)
  8. Queue restart (`php artisan queue:restart`)
  9. Maintenance mode off (`php artisan up`)
- **Webhook Integration**: Trigger deployments automatically from GitHub, GitLab, or Bitbucket commits.

### 8. Git Repository Manager
- Repository status overview (`git status`).
- Branch listings and active HEAD tracking.
- Commit logs (`git log -n 15 --oneline`).
- Remotes, fetch, pull, stash, and tag inspection.
- Policy-protected actions prevent unauthorized git resets or forced pushes.

### 9. Composer Package Manager
- Reads and parses `composer.json` and `composer.lock`.
- Inspects required packages, dev dependencies, and installed versions.
- Actions: `install`, `dump-autoload`, `validate`, `diagnose`, `outdated`, `show`.
- **Composer Security Audit**: Integrated vulnerability checking via `composer audit`.

### 10. Node, Bun, PNPM & Yarn Manager
- Automatic lockfile detection (`bun.lockb`, `bun.lock`, `pnpm-lock.yaml`, `yarn.lock`, `package-lock.json`).
- Displays package name, dependencies, devDependencies, and scripts.
- Instant runner chips for all defined `scripts` (`build`, `dev`, `lint`, `test`).
- Dependency vulnerability scanning via `npm audit` / `pnpm audit` / `bun audit`.

### 11. Queue Diagnostics & Failed Jobs Explorer
- Live overview of default connection (`sync`, `database`, `redis`, `sqs`).
- Metric counters for pending and failed jobs.
- **Failed Jobs Inspector**: View failed job IDs, target queues, failed timestamps, and exception stack traces.
- Worker management triggers: restart workers (`queue:restart`), retry all failed (`queue:retry all`), flush failed (`queue:flush`).

### 12. Scheduler Manager
- Runs and formats `php artisan schedule:list`.
- Inspects scheduled commands, intervals, cron expressions, next run times, and execution timezone.

### 13. Database Tools & Data Exporter
- Connection health check, active driver (`mysql`, `pgsql`, `sqlite`, `sqlsrv`), and database name.
- Table listings with live row count statistics.
- **Rich Schema Explorer**: Displays column names, database types, primary keys, and nullable flags.
- **High-Performance Data Export**: Stream table data into **CSV** or **JSON** file downloads.
- **Controlled SQL Runner**: Disabled by default (`WEBSHELL_DB_SQL=true`), limited to single statements, protected with configurable modes:
  - `read-only`: Only `SELECT` statements are permitted.
  - `read-write`: Allows `INSERT`, `UPDATE`, `DELETE` with explicit confirmation modals.
  - `administrator`: Unlocks structural modifications (`CREATE`, `ALTER`) when explicitly permitted.

### 14. Interactive File Manager & Code Editor
- **Breadcrumb Path Traversal**: Easily navigate through application folders.
- **In-Browser Code Editor**: Edit configuration files, Blade templates, scripts, and logs within size boundaries (`max_edit_bytes`).
- **File Upload & Download**: Drag-and-drop or file-picker upload and single-click file downloading.
- **Directory Creation & Renaming**: Easily create subfolders and rename files or directories.
- **Security Shield**: Protected files like `.env`, `.env.*`, SSL keys, `id_rsa`, and `auth.json` are blocked from viewing, downloading, editing, or deleting.

### 15. Log Viewer & Real-Time Monitoring
- **Level Filtering**: Filter entries instantly by `ALL`, `ERROR`, `WARNING`, or `INFO`.
- **Log Switching**: Switch between available log files in `storage/logs/` or custom log channels.
- **Log Maintenance**: One-click log clearing with safety confirmations.

### 16. Server Dashboard
- Comprehensive metrics at a glance: Laravel version, PHP version, OS, system architecture, hostname, memory usage, peak memory, disk total/free space, load averages, uptime, hosting fingerprint, recent commands, and health summary.

### 17. PHP Environment Inspector
- Live PHP runtime snapshot: version, SAPI, architecture, memory limit, max execution time, upload max filesize, post max size, enabled extensions, and disabled functions.
- **OPcache & JIT**: Live inspection of `opcache.enable`, `opcache.jit`, and `opcache.jit_buffer_size`.

### 18. Capability Detection & Hosting Fingerprinting
- Probes binary availability (`git`, `composer`, `node`, `npm`, `bun`, `pnpm`, `yarn`, `deno`, `pint`, `pest`, `larastan`, `jit`).
- Automatic hosting fingerprinting: **Coolify**, **Railway**, **Fly.io**, **Render**, **Laravel Cloud**, **Forge**, **Vapor**, **Docker**, **Kubernetes**, **Herd**, **Sail**, **Octane**, **FrankenPHP**, and shared hosts (cPanel, Plesk, DirectAdmin, CyberPanel, CloudPanel).
- Enters **Restricted Mode** when `proc_open` is disabled, providing safe diagnostics without crashing.

### 19. Security Firewall & Hardening
- Authentication, authorization gate (`webshell.access`), IP allowlisting (`WEBSHELL_IP_ALLOWLIST`), rate limiting (`throttle:30,1`), CSRF token verification, session auto-lock, and strict security headers (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`).

### 20. Role-Based Access Control (RBAC)
- Fine-grained permission assignments:
  - `owner`: Full unrestricted access.
  - `administrator`: Terminal, deployments, database, file management, backups, and approvals.
  - `developer`: Terminal, Artisan, Git, Composer, Node, logs, and files.
  - `operator`: Logs, queue inspection, scheduler, and health.
  - `viewer`: Read-only dashboard and health statistics.
- Configure via `webshell_role` on the user, implement `webshellRole()`, or use `canAccessWebShell()`.

### 21. Immutable Audit Log
- Logs every command: user ID, IP address, user agent, command string, family, execution duration, exit code, and status.
- Export audit history to **JSON** or **CSV** formats for compliance reporting.

### 22. Dangerous Command Protection
- Blocks or demands explicit confirmation for destructive patterns:
  - Filesystem: `rm -rf /`, `rm -rf *`, `mkfs`, `dd`
  - System: `shutdown`, `reboot`, `halt`, `poweroff`, `chmod -R 777`
  - Database: `DROP DATABASE`, `DROP TABLE`, `TRUNCATE TABLE`, `migrate:fresh`, `db:wipe`
  - Container / Cloud: `docker compose down -v`, `docker system prune -a`, `kubectl delete namespace`
  - Malicious: pipe-to-shell (`curl ... | sh`, `wget ... | sh`), fork bombs

### 23. Environment Secrets Masking
- `.env` files are blocked from editing or reading in the file manager.
- Inspector displays high-level configuration (`APP_ENV`, `APP_DEBUG`, cache/queue drivers) while masking sensitive credentials (`APP_KEY`, `DB_PASSWORD`, `REDIS_PASSWORD`, `MAIL_PASSWORD`, `AWS_SECRET_ACCESS_KEY`, `OPENAI_API_KEY`, `STRIPE_SECRET`, `NIGHTWATCH_TOKEN`).

### 24. Real-Time Streaming Terminal (SSE)
- Streams chunks live as commands execute. Falls back gracefully to single HTTP responses when running behind proxies or shared hosts that buffer streaming responses.

### 25. Process Management & Timeout Guards
- Enforces strict process timeouts (`WEBSHELL_TIMEOUT`) and idle timeouts (`WEBSHELL_IDLE_TIMEOUT`) to prevent runaway processes or orphaned HTTP workers.

### 26. Background Process Execution
- Execute long-running maintenance jobs with `background: true` without holding the browser connection open.

### 27. Process Inspector
- Lists running and recent processes executed through WebShell, tracking PID, command line, duration, and status.

### 28. Backup & Archive Management
- Fast Zip-archive creation of SQLite database, connection manifest, application storage, and public uploads.
- Instant browser download and safe archive deletion to reclaim disk space.

### 29. Scheduled Task Integration
- Inspects and verifies Laravel's scheduled task suite without requiring direct terminal access.

### 30. Health & Diagnostics Checks
- Application health checks: database connection, cache store, queue status, storage permissions, environment consistency, PHP version, Composer health, Git status, disk space, and debug-mode warnings.
- Public **`/webshell/healthz`** returns `{ status: "ok" }` for external uptime monitors without exposing sensitive data.

### 31. Laravel Doctor
- CLI (`php artisan webshell:doctor`) and web-based diagnostics.
- Identifies missing `APP_KEY`, unoptimized caches, unwritable directories, missing binaries, and security misconfigurations.

### 32. 2027 Modern Stack Awareness
- Auto-detects and reports the active state of **Octane**, **FrankenPHP**, **Reverb**, **Pulse**, **Horizon**, **Nightwatch**, **Herd**, **Sail**, **Vite**, **Livewire**, **Inertia**, **Pint**, and **Pest**.

### 33. Multi-Project Support (Optional)
- Allows managing multiple co-located Laravel installations from a unified configuration when explicitly configured.

### 34. Multi-Environment Visual Indicators
- Visual environment badges (`production`, `staging`, `development`) with distinct warning borders and confirmation prompts for mutating production actions.

### 35. Scoped Automation API
- REST endpoints for CI/CD automation authenticated via scoped Bearer tokens:
  - `webshell:artisan` — Run Artisan commands.
  - `webshell:git` — Run Git operations.
  - `webshell:deploy` — Trigger deployment pipelines.
  - `webshell:logs` — Retrieve log streams.
  - `webshell:health` — Fetch health snapshots.
- Wildcard (`*`) tokens are strictly rejected.

### 36. Signed Webhook Handlers
- Ingests HMAC-signed webhooks from **GitHub**, **GitLab**, and **Bitbucket** to trigger automated branch-specific deployments.

### 37. Notification Dispatchers
- Dispatch deployment success, deployment failure, and security blocked alerts via Laravel notifications (Mail, Slack, or custom notification channels).

### 38. Extensible Developer API & Plugins
- Register custom commands, panels, health checks, and deployment steps using `WebShell::register...` or PHP 8 Attributes (`#[WebShellCommand]`).

### 39. Modern 2027 UI Themes
- 9 curated colorways: **Cyberpunk**, **Emerald**, **Nord**, **Dracula**, **Monokai**, **Solarized**, **Dark**, **Light**, and **High-Contrast**.

### 40. Progressive Web App (PWA) & Offline Shell
- Web app manifest and service worker enabling installation as a standalone desktop or mobile application.

---

## 🎨 Design System & Themes

Laravel WebShell features a handcrafted Vanilla CSS design system optimized for performance, accessibility, and high visual appeal without heavy third-party CSS dependencies.

Switch themes instantly from the top-bar or configure `WEBSHELL_THEME` in your environment:

- 🌌 **Cyberpunk**: Deep neon purple and vibrant cyan with high-energy accents.
- 🌲 **Emerald**: Forest green tones tailored for eco-sleek monitoring.
- ❄️ **Nord**: Arctic-inspired cool blue-gray palette for focused operations.
- 🧛 **Dracula**: Classic dark contrast palette beloved by developers.
- 🎨 **Monokai**: High-contrast syntax-inspired coding palette.
- ☀️ **Solarized**: Precision calibrated dark cyan palette.
- 🌙 **Dark (Default)**: Modern charcoal-elevated dark mode.
- 📄 **Light**: Warm paper palette with crisp legibility.
- ⚡ **High-Contrast**: Pure black-and-white WCAG AAA compliance.

---

## 🌐 API & Webhook Endpoints

Laravel WebShell provides scoped REST endpoints for CI/CD automation and external health checks:

### Operational Endpoints

| Method | URI | Description |
| --- | --- | --- |
| `GET` | `/webshell` | WebShell Interactive SPA |
| `GET` | `/webshell/healthz` | Public status endpoint (`{"status": "ok"}`) for uptime monitors |
| `POST` | `/webshell/webhooks/{provider}` | Signed deployment webhook (GitHub, GitLab, Bitbucket) |

### Internal / AJAX API (`/webshell/api/...`)

| Method | URI | Description |
| --- | --- | --- |
| `GET` | `/api/bootstrap` | Initial application, server, stack, and capability payload |
| `POST` | `/api/terminal` | Execute command synchronously |
| `POST` | `/api/terminal/stream` | Execute command with live Server-Sent Events stream |
| `POST` | `/api/terminal/complete` | Autocomplete command arguments and file paths |
| `GET` | `/api/artisan` | List all discovered Artisan commands |
| `POST` | `/api/artisan` | Execute parameterized Artisan command |
| `GET` | `/api/git` | Git repository status, branch, and log overview |
| `POST` | `/api/git` | Execute approved Git action |
| `GET` | `/api/composer` | Composer dependency and diagnostic overview |
| `POST` | `/api/composer` | Run Composer command |
| `GET` | `/api/node` | Node/Bun/pnpm package scripts overview |
| `POST` | `/api/node` | Run Node/Bun/pnpm command |
| `GET` | `/api/files` | List files and directories |
| `GET` | `/api/files/show` | Read file contents into editor |
| `POST` | `/api/files` | Save modified file contents |
| `POST` | `/api/files/directory` | Create a new directory |
| `DELETE`| `/api/files` | Delete a file or directory |
| `POST` | `/api/files/rename` | Rename a file or directory |
| `GET` | `/api/files/download` | Download file attachment |
| `POST` | `/api/files/upload` | Upload file to directory |
| `GET` | `/api/database` | Database connection and table list |
| `GET` | `/api/database/{table}` | Table schema and row preview |
| `GET` | `/api/database/{table}/export` | Export table records as CSV or JSON |
| `POST` | `/api/database/query` | Execute single-statement SQL |
| `GET` | `/api/queue` | Queue metrics overview |
| `POST` | `/api/queue` | Execute queue Artisan command |
| `GET` | `/api/queue/failed` | Retrieve list of failed jobs |
| `GET` | `/api/scheduler` | Run `schedule:list` |
| `GET` | `/api/backups` | List backup archives |
| `POST` | `/api/backups` | Create new backup archive |
| `GET` | `/api/backups/{name}` | Download backup archive |
| `DELETE`| `/api/backups/{name}` | Delete backup archive |
| `GET` | `/api/logs` | Retrieve log files and log tails |
| `POST` | `/api/logs/clear` | Clear log file |
| `GET` | `/api/deployments` | Deployment configurations and history |
| `POST` | `/api/deploy` | Trigger deployment pipeline |
| `GET` | `/api/audit` | Audit log records |
| `GET` | `/api/audit/export` | Export audit log as CSV or JSON |
| `GET` | `/api/doctor` | Comprehensive system health & security diagnosis |

---

## 💻 Developer & Plugin API

Extend WebShell with custom commands, diagnostic checks, and deployment steps from your own packages:

```php
use Imran\WebShell\Facades\WebShell;
use Imran\WebShell\Attributes\WebShellCommand;

// Register custom palette commands
WebShell::registerCommand('reindex-search', [
    'label' => 'Rebuild Search Index',
    'command' => 'php artisan search:reindex --all',
]);

// Register custom health checks
WebShell::registerHealthCheck('redis-cluster', function () {
    $ok = Redis::ping();
    return [
        'ok' => (bool) $ok,
        'label' => 'Redis Cluster Connectivity',
        'detail' => $ok ? 'Pong received' : 'Connection failed',
    ];
});

// Register custom deployment warmup steps
WebShell::registerDeploymentStep('warm-cache', [
    'label' => 'Warm Application Cache',
    'command' => 'php artisan app:warm-cache',
    'optional' => true,
]);
```

You can also use PHP 8 Attributes directly on your service classes:

```php
#[WebShellCommand(id: 'sync-billing', label: 'Sync Stripe Invoices', command: 'php artisan stripe:sync')]
class StripeBillingService
{
    // ...
}
```

---

## 🔧 Comprehensive Configuration Reference

All behavior is customizable in `config/webshell.php`:

```php
return [
    'enabled' => env('WEBSHELL_ENABLED', false),
    'name' => env('WEBSHELL_NAME', 'Laravel WebShell'),
    'path' => env('WEBSHELL_PATH', 'webshell'),

    'terminal' => [
        'timeout' => (int) env('WEBSHELL_TIMEOUT', 60),
        'idle_timeout' => (int) env('WEBSHELL_IDLE_TIMEOUT', 30),
        'max_output' => (int) env('WEBSHELL_MAX_OUTPUT', 1024 * 1024),
        'restrict_working_directory' => env('WEBSHELL_RESTRICT_CWD', true),
        'allowed_roots' => [base_path()],
    ],

    'security' => [
        'confirmation' => env('WEBSHELL_CONFIRMATION', true),
        'block_dangerous_commands' => env('WEBSHELL_BLOCK_DANGEROUS', true),
        'audit' => env('WEBSHELL_AUDIT', true),
        'ip_allowlist' => array_filter(explode(',', (string) env('WEBSHELL_IP_ALLOWLIST', ''))),
        'session_timeout' => (int) env('WEBSHELL_SESSION_TIMEOUT', 30),
        'read_only' => env('WEBSHELL_READ_ONLY', false),
    ],

    'database' => [
        'mode' => env('WEBSHELL_DB_MODE', 'read-only'), // read-only | read-write | administrator
        'max_rows' => 200,
        'export_limit' => (int) env('WEBSHELL_DB_EXPORT_LIMIT', 5000),
        'allow_raw_sql' => env('WEBSHELL_DB_SQL', false),
    ],

    'ui' => [
        'theme' => env('WEBSHELL_THEME', 'dark'),
        'themes' => ['dark', 'light', 'dracula', 'monokai', 'solarized', 'high-contrast', 'cyberpunk', 'emerald', 'nord'],
        'locale' => env('WEBSHELL_LOCALE', 'en'),
        'pwa' => env('WEBSHELL_PWA', true),
    ],
];
```

---

## 🧪 Testing

The test suite validates security policies, process execution, capability detection, and modern endpoints:

```bash
composer test
# or
vendor/bin/phpunit
```

```text
Runtime:       PHP 8.5.7
Configuration: phpunit.xml

.......................                                           23 / 23 (100%)

Time: 00:02.278, Memory: 30.00 MB
OK (23 tests, 60 assertions)
```

---

## 📄 License

Laravel WebShell is open-sourced software licensed under the [MIT license](LICENSE).

---

## 🤝 Let's Connect & Collaborate

I am open to **Senior Remote Full-Stack Roles**, **AI Platform Architecture Contracts**, and **Enterprise Technical Advisory**.

- 📍 **Timezone**: UTC+6 (Dhaka / Rangpur, Bangladesh) — Flexible overlap with US, UK, and European business hours.
- ⚡ **Delivery Mode**: Async-ready, Slack, Discord, Jira, GitHub, and production-first accountability.

| Channel | Address / Handle | Quick Action |
| :--- | :--- | :--- |
| 🌐 Primary Portfolio | [imrandev.bd](https://imrandev.bd/) | [Visit Site ↗](https://imrandev.bd/) |
| 📦 Packagist Packages | [packagist.org/packages/imrandevbd/](https://packagist.org/packages/imrandevbd/) | [View Packages ↗](https://packagist.org/packages/imrandevbd/) |
| 💼 LinkedIn Profile | [linkedin.com/in/imranbru99](https://linkedin.com/in/imranbru99) | [Connect ↗](https://linkedin.com/in/imranbru99) |
| 🐙 GitHub Profile | [github.com/imranbru99](https://github.com/imranbru99) | [Follow ↗](https://github.com/imranbru99) |
| 💬 WhatsApp Direct | [+880 1576-918420](http://wa.me/+8801576918420) | [Chat Now ↗](http://wa.me/+8801576918420) |
| 📧 Direct Email | [me@imrandev.bd](mailto:me@imrandev.bd) | [Send Email ↗](mailto:me@imrandev.bd) |
| 🐦 X (Twitter) | [@imrandev_bd](https://x.com/imrandev_bd) | [Follow ↗](https://x.com/imrandev_bd) |
| 📺 YouTube Tech | [@ImranDevBD](https://youtube.com/@ImranDevBD) | [Subscribe ↗](https://youtube.com/@ImranDevBD) |

