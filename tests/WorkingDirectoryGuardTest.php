<?php

namespace Imran\WebShell\Tests;

use Imran\WebShell\Exceptions\CommandBlockedException;
use Imran\WebShell\Services\Security\WorkingDirectoryGuard;

class WorkingDirectoryGuardTest extends TestCase
{
    public function test_it_rejects_paths_outside_allowed_roots(): void
    {
        $this->app['config']->set('webshell.terminal.restrict_working_directory', true);
        $this->app['config']->set('webshell.terminal.allowed_roots', [base_path()]);

        $this->expectException(CommandBlockedException::class);

        app(WorkingDirectoryGuard::class)->resolve(dirname(base_path()));
    }

    public function test_it_accepts_the_application_root(): void
    {
        $cwd = app(WorkingDirectoryGuard::class)->resolve(base_path());

        $this->assertSame(
            app(WorkingDirectoryGuard::class)->canonicalize(base_path()),
            $cwd,
        );
    }
}
