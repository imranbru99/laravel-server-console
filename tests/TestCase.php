<?php

namespace Imran\WebShell\Tests;

use Imran\WebShell\WebShellServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [WebShellServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('webshell.enabled', true);
        $app['config']->set('webshell.security.audit', false);
        $app['config']->set('webshell.features.history', false);
        $app['config']->set('webshell.storage', 'disabled');
        $app['config']->set('webshell.middleware', ['web']);
        $app['config']->set('webshell.authorization.gate', '');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
