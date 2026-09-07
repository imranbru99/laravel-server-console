<?php

namespace Imran\WebShell\Tests;

use Imran\WebShell\Services\Security\DangerousCommandDetector;

class DangerousCommandDetectorTest extends TestCase
{
    public function test_it_detects_classic_destructive_shell_patterns(): void
    {
        $detector = new DangerousCommandDetector;

        $this->assertTrue($detector->isDangerous('rm -rf /'));
        $this->assertTrue($detector->isDangerous('php artisan migrate:fresh'));
        $this->assertTrue($detector->isDangerous('DROP DATABASE production'));
        $this->assertFalse($detector->isDangerous('php artisan migrate --force'));
        $this->assertFalse($detector->isDangerous('git status'));
    }
}
