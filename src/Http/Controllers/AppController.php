<?php

namespace Imran\WebShell\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Imran\WebShell\Contracts\HistoryStore;
use Imran\WebShell\Services\Capability\CapabilityDetector;
use Imran\WebShell\Services\Environment\PhpInspector;
use Imran\WebShell\Services\Health\HealthChecker;
use Imran\WebShell\Services\Registry\PluginRegistry;
use Imran\WebShell\Services\Security\RoleService;

class AppController extends Controller
{
    public function index()
    {
        return view('webshell::app');
    }

    public function bootstrap(
        Request $request,
        PhpInspector $inspector,
        CapabilityDetector $capabilities,
        HealthChecker $health,
        HistoryStore $history,
        PluginRegistry $registry,
        RoleService $roles,
    ) {
        return response()->json([
            'app' => [
                'name' => config('webshell.name'),
                'path' => config('webshell.path'),
                'edition' => config('webshell.edition'),
                'environment' => app()->environment(),
                'user' => $request->user()?->only(['id', 'name', 'email']) ?? null,
                'read_only' => (bool) config('webshell.security.read_only'),
                'auto_lock' => (bool) config('webshell.security.auto_lock'),
                'session_timeout' => (int) config('webshell.security.session_timeout', 30),
                'pwa' => (bool) config('webshell.ui.pwa'),
                'rbac' => $roles->snapshot($request->user()),
            ],
            'features' => config('webshell.features'),
            'ui' => config('webshell.ui'),
            'terminal' => [
                'timeout' => config('webshell.terminal.timeout'),
                'working_directory' => config('webshell.terminal.working_directory'),
                'max_output' => config('webshell.terminal.max_output'),
            ],
            'macros' => config('webshell.macros'),
            'palette' => config('webshell.palette'),
            'deployment' => config('webshell.deployment'),
            'application' => $inspector->application(),
            'server' => $inspector->server(),
            'php' => $inspector->snapshot(),
            'environment' => $inspector->environment(),
            'capabilities' => $capabilities->detect(),
            'health' => $health->run(),
            'history' => $history->latest(20),
            'plugins' => $registry->toArray(),
            'security_warnings' => $this->warnings($inspector, $health),
        ]);
    }

    /**
     * @return list<array<string, string>>
     */
    protected function warnings(PhpInspector $inspector, HealthChecker $health): array
    {
        $warnings = [];

        if (app()->environment('production') && config('app.debug')) {
            $warnings[] = [
                'severity' => 'critical',
                'title' => 'Production debugging is enabled',
                'message' => 'APP_DEBUG=true exposes stack traces and configuration in production.',
            ];
        }

        foreach ($health->doctor()['recommendations'] as $recommendation) {
            $warnings[] = [
                'severity' => $recommendation['severity'],
                'title' => $recommendation['title'],
                'message' => $recommendation['recommendation'],
            ];
        }

        return $warnings;
    }
}
