<?php

namespace Imran\WebShell;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Imran\WebShell\Console\DisableCommand;
use Imran\WebShell\Console\DoctorCommand;
use Imran\WebShell\Console\InstallCommand;
use Imran\WebShell\Console\TokenCommand;
use Imran\WebShell\Http\Middleware\EnsureWebShellEnabled;
use Imran\WebShell\Http\Middleware\SecurityHeaders;
use Imran\WebShell\Contracts\AuditLogger;
use Imran\WebShell\Contracts\CommandValidator;
use Imran\WebShell\Contracts\HistoryStore;
use Imran\WebShell\Contracts\ProcessRunner as ProcessRunnerContract;
use Imran\WebShell\Services\Audit\FileAuditLogger;
use Imran\WebShell\Services\History\FileHistoryStore;
use Imran\WebShell\Services\Process\ProcessRunner;
use Imran\WebShell\Services\Registry\PluginRegistry;
use Imran\WebShell\Services\Security\CommandValidator as SecurityCommandValidator;

class WebShellServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/webshell.php', 'webshell');

        $this->app->singleton(PluginRegistry::class);
        $this->app->singleton(WebShell::class);
        $this->app->bind(ProcessRunnerContract::class, ProcessRunner::class);
        $this->app->bind(CommandValidator::class, SecurityCommandValidator::class);
        $this->app->bind(HistoryStore::class, FileHistoryStore::class);
        $this->app->bind(AuditLogger::class, FileAuditLogger::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'webshell');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'webshell');
        $this->registerRoutes();
        $this->registerGates();
        $this->registerCommands();
        $this->publishables();
    }

    protected function registerRoutes(): void
    {
        if (! config('webshell.enabled')) {
            return;
        }

        $prefix = (string) config('webshell.path', 'webshell');

        Route::middleware((array) config('webshell.middleware', ['web']))
            ->prefix($prefix)
            ->group(__DIR__.'/../routes/web.php');

        Route::middleware(['web', EnsureWebShellEnabled::class, SecurityHeaders::class])
            ->prefix($prefix)
            ->group(__DIR__.'/../routes/public.php');

        if (config('webshell.api.enabled')) {
            Route::middleware((array) config('webshell.api_middleware', ['api', EnsureWebShellEnabled::class]))
                ->prefix($prefix.'/api/v1')
                ->group(__DIR__.'/../routes/api.php');
        }
    }

    protected function registerGates(): void
    {
        $gate = (string) config('webshell.authorization.gate', 'webshell.access');

        if ($gate === '' || Gate::has($gate)) {
            return;
        }

        Gate::define($gate, function ($user = null) {
            $policy = config('webshell.authorization.policy');

            if (is_string($policy) && class_exists($policy)) {
                return app($policy)->access($user);
            }

            return $user !== null;
        });
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            DoctorCommand::class,
            DisableCommand::class,
            TokenCommand::class,
        ]);
    }

    protected function publishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/webshell.php' => config_path('webshell.php'),
        ], 'webshell-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/webshell'),
        ], 'webshell-views');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/webshell'),
        ], 'webshell-assets');
    }
}
