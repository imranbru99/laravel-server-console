<?php

namespace Imran\WebShell\Services\Capability;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CapabilityDetector
{
    /**
     * @return array<string, mixed>
     */
    public function detect(): array
    {
        $disabled = $this->disabledFunctions();

        $checks = [
            'php_version' => [
                'label' => 'PHP version',
                'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'detail' => PHP_VERSION,
            ],
            'proc_open' => $this->functionCheck('proc_open', $disabled),
            'proc_get_status' => $this->functionCheck('proc_get_status', $disabled),
            'proc_terminate' => $this->functionCheck('proc_terminate', $disabled),
            'shell_exec' => $this->functionCheck('shell_exec', $disabled),
            'exec' => $this->functionCheck('exec', $disabled),
            'passthru' => $this->functionCheck('passthru', $disabled),
            'system' => $this->functionCheck('system', $disabled),
            'artisan' => [
                'label' => 'Laravel Artisan',
                'ok' => File::exists(base_path('artisan')),
                'detail' => File::exists(base_path('artisan')) ? 'artisan binary found' : 'artisan not found',
            ],
            'filesystem' => [
                'label' => 'Filesystem access',
                'ok' => is_readable(base_path()) && is_writable(storage_path()),
                'detail' => is_writable(storage_path()) ? 'storage is writable' : 'storage is not writable',
            ],
            'git' => $this->binaryCheck('git', ['--version']),
            'composer' => $this->binaryCheck($this->composerBinary(), ['--version']),
            'node' => $this->binaryCheck('node', ['-v']),
            'npm' => $this->binaryCheck('npm', ['-v']),
            'bun' => $this->binaryCheck('bun', ['-v']),
            'pnpm' => $this->binaryCheck('pnpm', ['-v']),
            'yarn' => $this->binaryCheck('yarn', ['-v']),
            'deno' => $this->binaryCheck('deno', ['--version']),
            'pint' => [
                'label' => 'Laravel Pint',
                'ok' => File::exists(base_path('vendor/bin/pint')),
                'detail' => File::exists(base_path('vendor/bin/pint')) ? 'installed' : 'not installed',
            ],
            'pest' => [
                'label' => 'Pest',
                'ok' => File::exists(base_path('vendor/bin/pest')),
                'detail' => File::exists(base_path('vendor/bin/pest')) ? 'installed' : 'not installed',
            ],
            'larastan' => [
                'label' => 'Larastan / PHPStan',
                'ok' => File::exists(base_path('vendor/bin/larastan')) || File::exists(base_path('vendor/bin/phpstan')),
                'detail' => (File::exists(base_path('vendor/bin/larastan')) || File::exists(base_path('vendor/bin/phpstan'))) ? 'installed' : 'not installed',
            ],
            'jit' => [
                'label' => 'PHP JIT Compiler',
                'ok' => (bool) ini_get('opcache.jit_buffer_size') && (int) ini_get('opcache.jit_buffer_size') > 0,
                'detail' => (ini_get('opcache.jit_buffer_size') && (int) ini_get('opcache.jit_buffer_size') > 0) ? 'enabled ('.ini_get('opcache.jit_buffer_size').')' : 'disabled',
            ],
            'storage_webshell' => [
                'label' => 'WebShell storage',
                'ok' => $this->ensureStorage(),
                'detail' => (string) config('webshell.storage_path'),
            ],
        ];

        $ok = collect($checks)->where('ok', true)->count();

        return [
            'hosting' => $this->hostingFingerprint(),
            'restricted_mode' => ! ($checks['proc_open']['ok'] ?? false),
            'summary' => [
                'passed' => $ok,
                'total' => count($checks),
            ],
            'disabled_functions' => $disabled,
            'checks' => $checks,
        ];
    }

    /**
     * @return list<string>
     */
    public function disabledFunctions(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions')))));
    }

    /**
     * @return array<string, mixed>
     */
    public function hostingFingerprint(): array
    {
        $root = base_path();
        $hints = [
            'cpanel' => is_dir('/usr/local/cpanel') || isset($_SERVER['CPANEL']),
            'plesk' => is_dir('/usr/local/psa') || isset($_SERVER['PLESK_DOMAIN']),
            'directadmin' => is_dir('/usr/local/directadmin'),
            'cyberpanel' => is_dir('/usr/local/CyberCP'),
            'cloudpanel' => is_dir('/home/clp'),
            'forge' => File::exists(base_path('forge.json')) || str_contains((string) gethostname(), 'forge'),
            'vapor' => isset($_ENV['VAPOR_SSM_PATH']) || isset($_SERVER['VAPOR_SSM_PATH']),
            'laravel_cloud' => isset($_ENV['LARAVEL_CLOUD']) || isset($_SERVER['LARAVEL_CLOUD']),
            'coolify' => isset($_ENV['COOLIFY_APP_ID']) || isset($_SERVER['COOLIFY_APP_ID']) || isset($_ENV['COOLIFY_BRANCH']),
            'railway' => isset($_ENV['RAILWAY_ENVIRONMENT']) || isset($_SERVER['RAILWAY_ENVIRONMENT']) || isset($_ENV['RAILWAY_SERVICE_NAME']),
            'fly' => isset($_ENV['FLY_APP_NAME']) || isset($_SERVER['FLY_APP_NAME']),
            'render' => isset($_ENV['RENDER']) || isset($_SERVER['RENDER']),
            'k8s' => isset($_ENV['KUBERNETES_SERVICE_HOST']) || isset($_SERVER['KUBERNETES_SERVICE_HOST']),
            'herd' => str_contains((string) PHP_BINARY, 'Herd') || isset($_SERVER['HERD_HOME']),
            'sail' => File::exists(base_path('vendor/laravel/sail')),
            'octane' => class_exists(\Laravel\Octane\Octane::class),
            'frankenphp' => PHP_SAPI === 'frankenphp' || isset($_SERVER['FRANKENPHP_WORKER']),
            'reverb' => class_exists(\Laravel\Reverb\ReverbServiceProvider::class),
            'pulse' => class_exists(\Laravel\Pulse\Pulse::class),
            'nightwatch' => class_exists(\Laravel\Nightwatch\NightwatchServiceProvider::class) || isset($_ENV['NIGHTWATCH_TOKEN']),
            'docker' => File::exists('/.dockerenv'),
            'shared' => ! function_exists('proc_open') || in_array('proc_open', $this->disabledFunctions(), true),
        ];

        $detected = collect($hints)->filter()->keys()->values()->all();

        return [
            'os' => PHP_OS_FAMILY,
            'sapi' => PHP_SAPI,
            'hostname' => gethostname() ?: 'unknown',
            'path' => $root,
            'detected' => $detected !== [] ? $detected : ['generic'],
            'ssh_required' => false,
        ];
    }

    /**
     * @param  list<string>  $disabled
     * @return array<string, mixed>
     */
    protected function functionCheck(string $function, array $disabled): array
    {
        $ok = function_exists($function) && ! in_array($function, $disabled, true);

        return [
            'label' => $function.'()',
            'ok' => $ok,
            'detail' => $ok ? 'available' : 'disabled by hosting policy',
        ];
    }

    /**
     * @param  list<string>  $arguments
     * @return array<string, mixed>
     */
    protected function binaryCheck(string $binary, array $arguments): array
    {
        if (! function_exists('proc_open') || in_array('proc_open', $this->disabledFunctions(), true)) {
            return [
                'label' => $binary,
                'ok' => false,
                'detail' => 'cannot probe — proc_open disabled',
            ];
        }

        try {
            $process = new Process(array_merge([$binary], $arguments));
            $process->setTimeout(8);
            $process->run();

            $ok = $process->isSuccessful();

            return [
                'label' => $binary,
                'ok' => $ok,
                'detail' => $ok ? trim($process->getOutput() ?: $process->getErrorOutput()) : 'not available',
            ];
        } catch (\Throwable $e) {
            return [
                'label' => $binary,
                'ok' => false,
                'detail' => $e->getMessage(),
            ];
        }
    }

    protected function composerBinary(): string
    {
        foreach ([base_path('composer.phar'), 'composer'] as $binary) {
            if ($binary === 'composer' || File::exists($binary)) {
                return $binary;
            }
        }

        return 'composer';
    }

    protected function ensureStorage(): bool
    {
        $path = (string) config('webshell.storage_path');

        if (! is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return is_dir($path) && is_writable($path);
    }
}
