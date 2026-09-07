<?php

namespace Imran\WebShell\Services\Environment;

use Imran\WebShell\Services\Capability\CapabilityDetector;
use Imran\WebShell\Services\Security\SecretMasker;

class PhpInspector
{
    public function __construct(
        protected CapabilityDetector $capabilities,
        protected SecretMasker $masker,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $extensions = [
            'pdo', 'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'redis', 'gd', 'imagick',
            'openssl', 'mbstring', 'curl', 'zip', 'intl', 'pcntl', 'posix', 'fileinfo',
        ];

        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'os' => PHP_OS.' ('.PHP_OS_FAMILY.')',
            'architecture' => php_uname('m'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'disabled_functions' => $this->capabilities->disabledFunctions(),
            'extensions' => collect($extensions)->mapWithKeys(fn ($ext) => [$ext => extension_loaded($ext)])->all(),
            'ini' => [
                'display_errors' => ini_get('display_errors'),
                'error_reporting' => ini_get('error_reporting'),
                'opcache.enable' => ini_get('opcache.enable'),
                'opcache.jit' => ini_get('opcache.jit'),
                'opcache.jit_buffer_size' => ini_get('opcache.jit_buffer_size'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function application(): array
    {
        $git = $this->safeGit();

        return [
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'name' => config('app.name'),
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'cache' => config('cache.default'),
            'queue' => config('queue.default'),
            'session' => config('session.driver'),
            'git' => $git,
            'stack' => $this->modernStack(),
        ];
    }

    /**
     * @return array<string, bool|string>
     */
    public function modernStack(): array
    {
        return [
            'octane' => class_exists(\Laravel\Octane\Octane::class),
            'frankenphp' => PHP_SAPI === 'frankenphp' || isset($_SERVER['FRANKENPHP_WORKER']),
            'reverb' => class_exists(\Laravel\Reverb\ReverbServiceProvider::class),
            'pulse' => class_exists(\Laravel\Pulse\Pulse::class),
            'horizon' => class_exists(\Laravel\Horizon\Horizon::class),
            'nightwatch' => isset($_ENV['NIGHTWATCH_TOKEN']) || isset($_SERVER['NIGHTWATCH_TOKEN']),
            'herd' => str_contains((string) PHP_BINARY, 'Herd'),
            'sail' => file_exists(base_path('vendor/laravel/sail')),
            'vite' => file_exists(base_path('vite.config.js')) || file_exists(base_path('vite.config.ts')),
            'livewire' => class_exists(\Livewire\Livewire::class),
            'inertia' => class_exists(\Inertia\Inertia::class),
            'pint' => file_exists(base_path('vendor/bin/pint')),
            'pest' => file_exists(base_path('vendor/bin/pest')),
            'larastan' => file_exists(base_path('vendor/bin/larastan')) || file_exists(base_path('vendor/bin/phpstan')),
            'bun' => file_exists(base_path('bun.lockb')) || file_exists(base_path('bun.lock')),
            'pnpm' => file_exists(base_path('pnpm-lock.yaml')),
            'yarn' => file_exists(base_path('yarn.lock')),
            'tailwind' => file_exists(base_path('tailwind.config.js')) || file_exists(base_path('tailwind.config.ts')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function environment(): array
    {
        $keys = (array) config('webshell.masked_env_keys', []);
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = env($key);
        }

        return $this->masker->maskEnvironment($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function server(): array
    {
        $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : null;

        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'os' => php_uname('s').' '.php_uname('r'),
            'architecture' => php_uname('m'),
            'hostname' => gethostname(),
            'uptime' => $this->uptime(),
            'cpu' => [
                'load' => $load,
            ],
            'memory' => $this->memory(),
            'disk' => $this->disk(),
            'hosting' => $this->capabilities->hostingFingerprint(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function memory(): array
    {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        return [
            'usage_bytes' => $usage,
            'peak_bytes' => $peak,
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function disk(): array
    {
        $root = base_path();

        return [
            'total' => @disk_total_space($root) ?: 0,
            'free' => @disk_free_space($root) ?: 0,
        ];
    }

    protected function uptime(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        $seconds = @file_get_contents('/proc/uptime');

        if (! $seconds) {
            return null;
        }

        $seconds = (int) floatval($seconds);

        return gmdate('z\d H\h i\m', $seconds);
    }

    /**
     * @return array<string, mixed>
     */
    protected function safeGit(): array
    {
        $head = base_path('.git/HEAD');

        if (! is_file($head)) {
            return ['available' => false];
        }

        $ref = trim((string) file_get_contents($head));
        $branch = str_starts_with($ref, 'ref:') ? trim(str_replace('ref: refs/heads/', '', $ref)) : 'detached';

        return [
            'available' => true,
            'branch' => $branch,
        ];
    }
}
