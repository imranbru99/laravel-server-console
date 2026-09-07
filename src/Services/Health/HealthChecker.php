<?php

namespace Imran\WebShell\Services\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Imran\WebShell\Services\Capability\CapabilityDetector;
use Throwable;

class HealthChecker
{
    public function __construct(
        protected CapabilityDetector $capabilities,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $checks = [
            $this->check('application', 'Application', true, app()->version()),
            $this->try('database', 'Database', fn () => DB::connection()->getPdo() && 'Connected'),
            $this->try('cache', 'Cache', function () {
                Cache::put('webshell:health', 'ok', 10);

                return Cache::get('webshell:health') === 'ok' ? 'Writable' : 'Failed';
            }),
            $this->check('queue', 'Queue', filled(config('queue.default')), (string) config('queue.default')),
            $this->check('storage', 'Storage writable', is_writable(storage_path()), storage_path()),
            $this->check('bootstrap_cache', 'Bootstrap cache writable', is_writable(base_path('bootstrap/cache')), base_path('bootstrap/cache')),
            $this->check('environment', 'Environment', (bool) app()->environment(), (string) app()->environment()),
            $this->check('app_key', 'Application key', filled(config('app.key')), filled(config('app.key')) ? 'set' : 'missing'),
            $this->check('permissions', 'Public directory', is_readable(public_path()), public_path()),
            $this->check('php', 'PHP', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION),
            $this->check('debug', 'Debug mode', ! (app()->environment('production') && config('app.debug')), config('app.debug') ? 'APP_DEBUG=true' : 'APP_DEBUG=false', app()->environment('production') && config('app.debug') ? 'warning' : 'ok'),
            $this->check('disk', 'Disk space', $this->diskFreePercent() < 90, $this->diskFreePercent().'% used'),
        ];

        $capabilities = $this->capabilities->detect();

        foreach (['composer', 'git', 'node'] as $key) {
            $item = $capabilities['checks'][$key] ?? null;

            if ($item) {
                $checks[] = $this->check($key, $item['label'], (bool) $item['ok'], (string) $item['detail'], $item['ok'] ? 'ok' : 'warning');
            }
        }

        $passed = collect($checks)->where('status', 'ok')->count();
        $warnings = collect($checks)->where('status', 'warning')->count();
        $critical = collect($checks)->where('status', 'critical')->count();

        return [
            'passed' => $passed,
            'warnings' => $warnings,
            'critical' => $critical,
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function doctor(): array
    {
        $health = $this->run();
        $recommendations = [];

        if (app()->environment('production') && config('app.debug')) {
            $recommendations[] = [
                'severity' => 'critical',
                'title' => 'APP_DEBUG=true in production',
                'recommendation' => 'Set APP_DEBUG=false so stack traces and secrets are not exposed.',
            ];
        }

        if (! filled(config('app.key'))) {
            $recommendations[] = [
                'severity' => 'critical',
                'title' => 'Missing APP_KEY',
                'recommendation' => 'Run php artisan key:generate.',
            ];
        }

        if (! is_writable(storage_path())) {
            $recommendations[] = [
                'severity' => 'critical',
                'title' => 'Storage is not writable',
                'recommendation' => 'Fix permissions on storage/ and bootstrap/cache.',
            ];
        }

        $envPublic = File::exists(public_path('.env'));

        if ($envPublic) {
            $recommendations[] = [
                'severity' => 'critical',
                'title' => '.env appears publicly accessible',
                'recommendation' => 'Remove public/.env immediately. Environment files must never be web-accessible.',
            ];
        }

        return [
            'health' => $health,
            'capabilities' => $this->capabilities->detect(),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function try(string $key, string $label, callable $callback): array
    {
        try {
            $detail = $callback();

            return $this->check($key, $label, true, is_string($detail) ? $detail : 'ok');
        } catch (Throwable $e) {
            return $this->check($key, $label, false, $e->getMessage(), 'critical');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function check(string $key, string $label, bool $ok, string $detail, ?string $status = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ok' => $ok,
            'detail' => $detail,
            'status' => $status ?? ($ok ? 'ok' : 'critical'),
        ];
    }

    protected function diskFreePercent(): int
    {
        $root = base_path();
        $total = @disk_total_space($root);
        $free = @disk_free_space($root);

        if (! $total || $total <= 0) {
            return 0;
        }

        return (int) round((($total - ($free ?: 0)) / $total) * 100);
    }
}
