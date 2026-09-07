<?php

namespace Imran\WebShell\Tests;

use Imran\WebShell\Support\CommandResult;

class CommandResultTest extends TestCase
{
    public function test_successful_results_expose_a_clean_api(): void
    {
        $result = new CommandResult(
            id: 'abc',
            command: 'php artisan about',
            exitCode: 0,
            output: 'Laravel',
            errorOutput: '',
            duration: 0.42,
            workingDirectory: base_path(),
        );

        $this->assertTrue($result->successful());
        $this->assertSame(0, $result->exitCode);
        $this->assertSame('Laravel', $result->output());
        $this->assertSame(0.42, $result->duration());
    }

    public function test_blocked_results_ask_for_confirmation_when_needed(): void
    {
        $result = CommandResult::blocked('rm -rf storage/*', 'Dangerous command', true);

        $this->assertTrue($result->blocked);
        $this->assertTrue($result->requiresConfirmation);
        $this->assertFalse($result->successful());
        $this->assertSame(126, $result->exitCode());
    }
}
