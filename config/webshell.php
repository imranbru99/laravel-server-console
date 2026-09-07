<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable WebShell
    |--------------------------------------------------------------------------
    |
    | Kill switch. Prefer WEBSHELL_ENABLED=false in production until you have
    | authenticated access, an IP allowlist, and a gate/policy in place.
    | php artisan webshell:disable writes this to false via the environment.
    |
    */

    'enabled' => env('WEBSHELL_ENABLED', env('APP_ENV') !== 'production'),

    'name' => env('WEBSHELL_NAME', 'Laravel WebShell'),

    'path' => env('WEBSHELL_PATH', 'webshell'),

    'edition' => env('WEBSHELL_EDITION', 'community'),

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    */

    'middleware' => array_values(array_filter([
        'web',
        env('WEBSHELL_AUTH_MIDDLEWARE', 'auth'),
        \Imran\WebShell\Http\Middleware\EnsureWebShellEnabled::class,
        \Imran\WebShell\Http\Middleware\AuthorizeWebShell::class,
        \Imran\WebShell\Http\Middleware\IpAllowlist::class,
        \Imran\WebShell\Http\Middleware\SecurityHeaders::class,
        'throttle:'.env('WEBSHELL_RATE_LIMIT', '30,1'),
    ])),

    'api_middleware' => array_values(array_filter([
        'api',
        \Imran\WebShell\Http\Middleware\EnsureWebShellEnabled::class,
        \Imran\WebShell\Http\Middleware\AuthorizeWebShell::class,
        \Imran\WebShell\Http\Middleware\IpAllowlist::class,
    ])),

    'authorization' => [
        'gate' => env('WEBSHELL_GATE', 'webshell.access'),
        'policy' => \Imran\WebShell\Policies\WebShellPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Terminal / process engine
    |--------------------------------------------------------------------------
    */

    'terminal' => [
        'timeout' => (int) env('WEBSHELL_TIMEOUT', 60),
        'idle_timeout' => (int) env('WEBSHELL_IDLE_TIMEOUT', 30),
        'max_output' => (int) env('WEBSHELL_MAX_OUTPUT', 1024 * 1024),
        'working_directory' => env('WEBSHELL_CWD') ?: base_path(),
        'restrict_working_directory' => env('WEBSHELL_RESTRICT_CWD', true),
        'allowed_roots' => array_filter([
            base_path(),
        ]),
        'shell' => env('WEBSHELL_SHELL'),
        'environment' => [],
        'stream' => env('WEBSHELL_STREAM', true),
        'history_limit' => (int) env('WEBSHELL_HISTORY_LIMIT', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'security' => [
        'authentication' => true,
        'confirmation' => env('WEBSHELL_CONFIRMATION', true),
        'block_dangerous_commands' => env('WEBSHELL_BLOCK_DANGEROUS', true),
        'require_dangerous_confirmation' => true,
        'audit' => env('WEBSHELL_AUDIT', true),
        'ip_allowlist' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('WEBSHELL_IP_ALLOWLIST', ''))
        ))),
        'session_timeout' => (int) env('WEBSHELL_SESSION_TIMEOUT', 30),
        'auto_lock' => env('WEBSHELL_AUTO_LOCK', true),
        'rate_limit' => env('WEBSHELL_RATE_LIMIT', '30,1'),
        'max_execution_time' => (int) env('WEBSHELL_TIMEOUT', 60),
        'max_output_size' => (int) env('WEBSHELL_MAX_OUTPUT', 1024 * 1024),
        'hide_environment_secrets' => true,
        'protect_env_files' => true,
        'read_only' => env('WEBSHELL_READ_ONLY', false),
        'csrf' => true,
    ],

    'production' => [
        'require_confirmation' => true,
        'block_destructive_commands' => true,
        'require_approval' => env('WEBSHELL_REQUIRE_APPROVAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Command policies
    |--------------------------------------------------------------------------
    |
    | allowlist: when non-empty, only matching commands may run.
    | denylist: always blocked (in addition to dangerous patterns).
    | Binary policies restrict which tool families are available.
    |
    */

    'policies' => [
        'allowlist' => [],
        'denylist' => [],
        'artisan' => true,
        'git' => ['status', 'log', 'diff', 'branch', 'fetch', 'pull', 'stash', 'tag', 'remote'],
        'composer' => ['install', 'dump-autoload', 'validate', 'diagnose', 'outdated', 'show', 'audit'],
        'node' => ['install', 'run', 'outdated', 'audit'],
        'dangerous' => false,
        'read_only_allowlist' => [
            'ls', 'dir', 'pwd', 'cd', 'cat', 'type', 'head', 'tail', 'whoami',
            'php -v', 'php -m', 'php --ini', 'php artisan about', 'php artisan list',
            'php artisan schedule:list', 'php artisan queue:failed',
            'git status', 'git log', 'git diff', 'git branch', 'git remote',
            'composer --version', 'composer show', 'composer diagnose', 'composer validate',
            'node -v', 'npm -v', 'npm run',
        ],
    ],

    'dangerous_patterns' => [
        'rm\s+-rf\s+/',
        'rm\s+-rf\s+\*',
        'rm\s+-rf\s+\.',
        'mkfs',
        '\bdd\b',
        'shutdown',
        'reboot',
        'halt',
        'poweroff',
        'chmod\s+-R\s+777',
        'chown\s+-R',
        ':\(\)\s*\{\s*:\|\:&\s*;\s*\}',
        'mkfs\.',
        '>\s*/dev/sd',
        'curl\s+[^\n]*\|\s*(ba)?sh',
        'wget\s+[^\n]*\|\s*(ba)?sh',
        'DROP\s+DATABASE',
        'DROP\s+TABLE',
        'TRUNCATE\s+TABLE',
        'php\s+artisan\s+migrate:fresh',
        'php\s+artisan\s+migrate:reset',
        'php\s+artisan\s+db:wipe',
        'docker\s+compose\s+down\s+-v',
        'docker\s+system\s+prune\s+-a',
        'kubectl\s+delete\s+namespace',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        'terminal' => true,
        'artisan' => true,
        'command_builder' => true,
        'command_palette' => true,
        'history' => true,
        'macros' => true,
        'git' => true,
        'composer' => true,
        'node' => true,
        'database' => true,
        'files' => true,
        'logs' => true,
        'queue' => true,
        'scheduler' => true,
        'deployment' => true,
        'backups' => true,
        'health' => true,
        'doctor' => true,
        'dashboard' => true,
        'php_inspector' => true,
        'environment' => true,
        'audit' => true,
        'security' => true,
        'processes' => true,
        'api' => env('WEBSHELL_API', false),
        'webhooks' => env('WEBSHELL_WEBHOOKS', false),
        'multi_project' => false,
        'approvals' => env('WEBSHELL_APPROVALS', false),
        'pwa' => env('WEBSHELL_PWA', true),
        'sql' => env('WEBSHELL_DB_SQL', false),
        'rbac' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | database | file | redis | disabled
    |
    */

    'storage' => env('WEBSHELL_STORAGE', 'file'),

    'storage_path' => storage_path('app/webshell'),

    /*
    |--------------------------------------------------------------------------
    | Protected paths
    |--------------------------------------------------------------------------
    */

    'protected_files' => [
        '.env',
        '.env.backup',
        '.env.production',
        '.env.local',
        '.env.testing',
        '.env.example',
        'auth.json',
        'id_rsa',
        'id_ed25519',
        '.git/config',
    ],

    'secret_env_keys' => [
        'APP_KEY',
        'DB_PASSWORD',
        'REDIS_PASSWORD',
        'MAIL_PASSWORD',
        'AWS_SECRET_ACCESS_KEY',
        'AWS_SESSION_TOKEN',
        'OPENAI_API_KEY',
        'STRIPE_SECRET',
        'PUSHER_APP_SECRET',
        'NIGHTWATCH_TOKEN',
    ],

    'masked_env_keys' => [
        'APP_KEY', 'APP_ENV', 'APP_DEBUG', 'APP_URL',
        'CACHE_STORE', 'CACHE_DRIVER', 'QUEUE_CONNECTION',
        'SESSION_DRIVER', 'FILESYSTEM_DISK', 'LOG_CHANNEL',
        'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database tools
    |--------------------------------------------------------------------------
    */

    'database' => [
        'mode' => env('WEBSHELL_DB_MODE', 'read-only'), // read-only | read-write | administrator
        'max_rows' => 200,
        'export_limit' => (int) env('WEBSHELL_DB_EXPORT_LIMIT', 5000),
        'allow_raw_sql' => env('WEBSHELL_DB_SQL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | File manager
    |--------------------------------------------------------------------------
    */

    'files' => [
        'root' => base_path(),
        'hidden' => ['.git', 'vendor', 'node_modules', 'storage/logs'],
        'editable_extensions' => [
            'php', 'js', 'ts', 'vue', 'json', 'md', 'txt', 'css', 'scss',
            'html', 'blade.php', 'yml', 'yaml', 'xml', 'env', 'ini', 'sh',
        ],
        'max_edit_bytes' => 512 * 1024,
        'max_upload_bytes' => 8 * 1024 * 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */

    'logs' => [
        'default' => storage_path('logs/laravel.log'),
        'sources' => [
            'laravel' => storage_path('logs'),
        ],
        'max_bytes' => 256 * 1024,
        'tail_lines' => 400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment
    |--------------------------------------------------------------------------
    */

    'deployment' => [
        'default_environment' => env('APP_ENV', 'production'),
        'default_branch' => env('WEBSHELL_DEPLOY_BRANCH', 'main'),
        'environments' => [
            'production' => [
                'label' => 'Production',
                'confirm' => true,
                'branch' => 'main',
            ],
            'staging' => [
                'label' => 'Staging',
                'confirm' => true,
                'branch' => 'staging',
            ],
            'local' => [
                'label' => 'Development',
                'confirm' => false,
                'branch' => 'main',
            ],
        ],
        'steps' => [
            ['key' => 'down', 'label' => 'Maintenance mode', 'command' => 'php artisan down --retry=60', 'optional' => true],
            ['key' => 'git', 'label' => 'Git pull', 'command' => 'git pull', 'optional' => false],
            ['key' => 'composer', 'label' => 'Composer install', 'command' => 'composer install --no-dev --optimize-autoloader --no-interaction', 'optional' => false],
            ['key' => 'migrate', 'label' => 'Database migration', 'command' => 'php artisan migrate --force', 'optional' => false],
            ['key' => 'frontend', 'label' => 'Frontend build', 'command' => 'npm run build', 'optional' => true],
            ['key' => 'optimize_clear', 'label' => 'Cache clear', 'command' => 'php artisan optimize:clear', 'optional' => true],
            ['key' => 'optimize', 'label' => 'Optimize', 'command' => 'php artisan optimize', 'optional' => true],
            ['key' => 'queue', 'label' => 'Queue restart', 'command' => 'php artisan queue:restart', 'optional' => true],
            ['key' => 'up', 'label' => 'Maintenance mode OFF', 'command' => 'php artisan up', 'optional' => true],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Macros / saved commands
    |--------------------------------------------------------------------------
    */

    'macros' => [
        'deploy-production' => [
            'label' => 'Deploy Production',
            'description' => 'Pull, install, migrate, and optimize.',
            'commands' => [
                'git pull',
                'composer install --no-dev --optimize-autoloader --no-interaction',
                'php artisan migrate --force',
                'php artisan optimize',
            ],
        ],
        'clear-everything' => [
            'label' => 'Clear Everything',
            'description' => 'Clear all Laravel caches.',
            'commands' => [
                'php artisan optimize:clear',
            ],
        ],
        'build-frontend' => [
            'label' => 'Build Frontend',
            'description' => 'Install npm dependencies and build assets.',
            'commands' => [
                'npm ci',
                'npm run build',
            ],
        ],
    ],

    'palette' => [
        ['id' => 'cache-clear', 'label' => 'Clear Laravel cache', 'command' => 'php artisan optimize:clear'],
        ['id' => 'migrate', 'label' => 'Run migrations', 'command' => 'php artisan migrate --force', 'dangerous' => true],
        ['id' => 'git-pull', 'label' => 'Git pull', 'command' => 'git pull'],
        ['id' => 'composer-install', 'label' => 'Composer install', 'command' => 'composer install --no-interaction'],
        ['id' => 'npm-build', 'label' => 'Build frontend', 'command' => 'npm run build'],
        ['id' => 'queue-restart', 'label' => 'Restart queue', 'command' => 'php artisan queue:restart'],
        ['id' => 'php-version', 'label' => 'Check PHP version', 'command' => 'php -v'],
        ['id' => 'disk-space', 'label' => 'Check disk space', 'command' => PHP_OS_FAMILY === 'Windows' ? 'wmic logicaldisk get size,freespace,caption' : 'df -h'],
        ['id' => 'about', 'label' => 'Application about', 'command' => 'php artisan about'],
        ['id' => 'composer-audit', 'label' => 'Composer security audit', 'command' => 'composer audit'],
        ['id' => 'npm-audit', 'label' => 'NPM security audit', 'command' => 'npm audit --omit=dev'],
        ['id' => 'pint', 'label' => 'Run Laravel Pint', 'command' => 'vendor/bin/pint --test'],
        ['id' => 'pest', 'label' => 'Run Pest tests', 'command' => 'php artisan test'],
        ['id' => 'route-cache', 'label' => 'Cache routes', 'command' => 'php artisan route:cache'],
        ['id' => 'octane-reload', 'label' => 'Reload Octane', 'command' => 'php artisan octane:reload'],
        ['id' => 'bun-run', 'label' => 'Run Bun build', 'command' => 'bun run build'],
        ['id' => 'pnpm-build', 'label' => 'Run PNPM build', 'command' => 'pnpm run build'],
        ['id' => 'phpstan', 'label' => 'Run Larastan / PHPStan', 'command' => 'vendor/bin/phpstan analyse'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-project (advanced, disabled by default)
    |--------------------------------------------------------------------------
    */

    'projects' => [
        'default' => [
            'name' => env('APP_NAME', 'Laravel'),
            'path' => base_path(),
            'environment' => env('APP_ENV', 'production'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'enabled' => env('WEBSHELL_NOTIFICATIONS', false),
        'channels' => array_values(array_filter(explode(',', (string) env('WEBSHELL_NOTIFICATION_CHANNELS', 'mail')))),
        'routes' => [
            'mail' => env('WEBSHELL_NOTIFY_MAIL'),
            'slack' => env('WEBSHELL_NOTIFY_SLACK'),
        ],
        'on' => [
            'deployment_completed' => true,
            'deployment_failed' => true,
            'command_blocked' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RBAC
    |--------------------------------------------------------------------------
    */

    'rbac' => [
        'default_role' => env('WEBSHELL_ROLE', 'developer'),
        'guest_role' => 'viewer',
        'roles' => [
            'owner' => ['*'],
            'administrator' => ['dashboard', 'terminal', 'artisan', 'git', 'composer', 'node', 'deploy', 'logs', 'files', 'queue', 'scheduler', 'database', 'health', 'audit', 'history', 'macros', 'backups', 'approvals', 'processes', 'security'],
            'developer' => ['dashboard', 'terminal', 'artisan', 'git', 'composer', 'node', 'logs', 'files', 'health', 'history', 'macros'],
            'operator' => ['dashboard', 'logs', 'queue', 'scheduler', 'health', 'history'],
            'viewer' => ['dashboard', 'logs', 'health', 'history'],
        ],
    ],

    'webhooks' => [
        'enabled' => env('WEBSHELL_WEBHOOKS', false),
        'secret' => env('WEBSHELL_WEBHOOK_SECRET'),
        'branch' => env('WEBSHELL_WEBHOOK_BRANCH', 'main'),
        'environment' => env('WEBSHELL_WEBHOOK_ENV', 'production'),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    */

    'ui' => [
        'theme' => env('WEBSHELL_THEME', 'dark'),
        'themes' => ['dark', 'light', 'dracula', 'monokai', 'solarized', 'high-contrast', 'cyberpunk', 'emerald', 'nord'],
        'font' => env('WEBSHELL_FONT', 'JetBrains Mono'),
        'locale' => env('WEBSHELL_LOCALE', 'en'),
        'locales' => ['en', 'bn', 'ar', 'es', 'fr', 'de', 'hi', 'pt', 'zh', 'ja'],
        'pwa' => env('WEBSHELL_PWA', true),
        'system_theme' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API tokens (never unrestricted by default)
    |--------------------------------------------------------------------------
    */

    'api' => [
        'enabled' => env('WEBSHELL_API', false),
        'scopes' => [
            'webshell:artisan',
            'webshell:git',
            'webshell:deploy',
            'webshell:logs',
            'webshell:health',
        ],
        'tokens' => array_values(array_filter([
            env('WEBSHELL_API_TOKEN') ? [
                'name' => 'default',
                'token' => env('WEBSHELL_API_TOKEN'),
                'scopes' => array_values(array_filter(explode(',', (string) env('WEBSHELL_API_SCOPES', 'webshell:health')))),
            ] : null,
        ])),
    ],

];
