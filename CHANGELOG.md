# Changelog

## [0.2.0] - 2026-09-08

### Added

- RBAC roles: owner, administrator, developer, operator, viewer
- Command approval workflow for production
- Backup and download of database/storage archives
- Signed GitHub/GitLab/Bitbucket webhooks
- Scoped API tokens (`webshell:token`) — wildcard scopes are rejected
- PWA manifest, service worker, and installable UI
- Live SSE terminal streaming with request/response fallback
- SQL runner with read-only / read-write / administrator modes
- File upload and download
- Audit export as JSON or CSV
- Security headers (CSP, frame deny, nosniff, Permissions-Policy)
- Modern stack detection: Octane, FrankenPHP, Herd, Sail, Laravel Cloud, Pulse, Nightwatch, Reverb, Vite, Pint, Pest, Bun, pnpm
- Laravel notifications for deploy success/failure
- PHP attributes for plugin commands
- Public `/healthz` uptime probe (no secrets)
- Locales: English, Bangla, Arabic, Spanish, French, German

## [0.1.0] - 2026-09-08

### Added

- Process engine, terminal, Artisan builder, palette, macros, history, audit
- Git, Composer, Node, files, logs, queue, scheduler, deploy, health, doctor
