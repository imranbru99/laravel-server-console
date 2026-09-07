<?php

namespace Imran\WebShell\Tests;

use Imran\WebShell\Services\Security\CommandValidator;
use Imran\WebShell\Support\CommandRequest;

class CommandValidatorTest extends TestCase
{
    public function test_it_rejects_empty_commands(): void
    {
        $verdict = $this->validator()->validate(CommandRequest::make(''));

        $this->assertFalse($verdict->allowed);
    }

    public function test_it_requires_confirmation_for_dangerous_commands(): void
    {
        $verdict = $this->validator()->validate(CommandRequest::make('rm -rf /'));

        $this->assertFalse($verdict->allowed);
        $this->assertTrue($verdict->requiresConfirmation);
    }

    public function test_it_hard_blocks_dangerous_commands_when_confirmation_is_disabled(): void
    {
        $this->app['config']->set('webshell.security.require_dangerous_confirmation', false);

        $verdict = $this->validator()->validate(CommandRequest::make('rm -rf /'));

        $this->assertFalse($verdict->allowed);
        $this->assertFalse($verdict->requiresConfirmation);
    }

    public function test_it_allows_an_explicitly_confirmed_dangerous_command_outside_production(): void
    {
        $this->app['config']->set('webshell.production.block_destructive_commands', false);

        $verdict = $this->validator()->validate(CommandRequest::make('rm -rf *', [
            'confirmed' => true,
        ]));

        $this->assertTrue($verdict->allowed);
    }

    public function test_it_honors_the_allowlist(): void
    {
        $this->app['config']->set('webshell.policies.allowlist', ['php artisan about']);

        $denied = $this->validator()->validate(CommandRequest::make('git push origin main'));
        $allowed = $this->validator()->validate(CommandRequest::make('php artisan about'));

        $this->assertFalse($denied->allowed);
        $this->assertTrue($allowed->allowed);
    }

    public function test_read_only_mode_blocks_mutating_commands(): void
    {
        $this->app['config']->set('webshell.security.read_only', true);

        $verdict = $this->validator()->validate(CommandRequest::make('php artisan migrate --force'));

        $this->assertFalse($verdict->allowed);
        $this->assertStringContainsString('read-only', (string) $verdict->reason);
    }

    public function test_read_only_mode_allows_status_commands(): void
    {
        $this->app['config']->set('webshell.security.read_only', true);

        $verdict = $this->validator()->validate(CommandRequest::make('git status'));

        $this->assertTrue($verdict->allowed);
    }

    protected function validator(): CommandValidator
    {
        return $this->app->make(CommandValidator::class);
    }
}
