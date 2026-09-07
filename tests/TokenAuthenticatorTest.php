<?php

namespace Imran\WebShell\Tests;

use Imran\WebShell\Services\Api\TokenAuthenticator;

class TokenAuthenticatorTest extends TestCase
{
    public function test_it_never_allows_wildcard_scopes(): void
    {
        $this->app['config']->set('webshell.api.enabled', true);
        $this->app['config']->set('webshell.api.tokens', [[
            'token' => 'wsh_test',
            'scopes' => ['*'],
        ]]);

        $auth = new TokenAuthenticator;
        $record = $auth->authenticate('wsh_test');

        $this->assertNotNull($record);
        $this->assertFalse($auth->can($record, 'webshell:deploy'));
    }

    public function test_it_allows_an_explicit_scope(): void
    {
        $this->app['config']->set('webshell.api.enabled', true);
        $this->app['config']->set('webshell.api.tokens', [[
            'token' => 'wsh_health',
            'scopes' => ['webshell:health'],
        ]]);

        $auth = new TokenAuthenticator;

        $this->assertTrue($auth->can($auth->authenticate('wsh_health'), 'webshell:health'));
        $this->assertFalse($auth->can($auth->authenticate('wsh_health'), 'webshell:deploy'));
    }
}
