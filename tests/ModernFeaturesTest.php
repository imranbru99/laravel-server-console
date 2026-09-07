<?php

namespace Imran\WebShell\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Imran\WebShell\Services\Backup\BackupService;
use Imran\WebShell\Services\Capability\CapabilityDetector;
use Imran\WebShell\Services\Database\DatabaseService;
use Imran\WebShell\Services\Environment\PhpInspector;
use Imran\WebShell\Services\Node\NodeService;
use Imran\WebShell\Services\Queue\QueueService;

class ModernFeaturesTest extends TestCase
{
    public function test_capability_detector_reports_2027_runtimes(): void
    {
        $detector = app(CapabilityDetector::class);
        $detected = $detector->detect();

        $this->assertArrayHasKey('checks', $detected);
        $this->assertArrayHasKey('yarn', $detected['checks']);
        $this->assertArrayHasKey('deno', $detected['checks']);
        $this->assertArrayHasKey('larastan', $detected['checks']);
        $this->assertArrayHasKey('jit', $detected['checks']);

        $fingerprint = $detector->hostingFingerprint();
        $this->assertArrayHasKey('detected', $fingerprint);
    }

    public function test_php_inspector_exposes_jit_and_modern_stack(): void
    {
        $inspector = app(PhpInspector::class);
        $snapshot = $inspector->snapshot();

        $this->assertArrayHasKey('ini', $snapshot);
        $this->assertArrayHasKey('opcache.jit', $snapshot['ini']);
        $this->assertArrayHasKey('opcache.jit_buffer_size', $snapshot['ini']);

        $stack = $inspector->modernStack();
        $this->assertArrayHasKey('bun', $stack);
        $this->assertArrayHasKey('pnpm', $stack);
        $this->assertArrayHasKey('yarn', $stack);
        $this->assertArrayHasKey('larastan', $stack);
        $this->assertArrayHasKey('tailwind', $stack);
    }

    public function test_node_service_detects_manager_and_overview(): void
    {
        $node = app(NodeService::class);
        $this->assertContains($node->detectManager(), ['npm', 'bun', 'pnpm', 'yarn']);

        $overview = $node->overview();
        if ($overview['available']) {
            $this->assertArrayHasKey('package_manager', $overview);
        }
    }

    public function test_database_service_export(): void
    {
        $db = app(DatabaseService::class);
        
        $this->expectException(\Imran\WebShell\Exceptions\CommandBlockedException::class);
        $db->export('non_existent_table_xyz');
    }

    public function test_queue_service_failed_jobs(): void
    {
        $queue = app(QueueService::class);
        $failed = $queue->failedJobs(10);
        $this->assertIsArray($failed);
    }

    public function test_backup_service_delete(): void
    {
        $backups = app(BackupService::class);
        
        $this->expectException(\Imran\WebShell\Exceptions\CommandBlockedException::class);
        $backups->delete('missing-backup-archive.zip');
    }

    public function test_modern_web_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('webshell.backups.delete'));
        $this->assertTrue(Route::has('webshell.database.export'));
        $this->assertTrue(Route::has('webshell.files.directory'));
        $this->assertTrue(Route::has('webshell.files.delete'));
        $this->assertTrue(Route::has('webshell.files.rename'));
        $this->assertTrue(Route::has('webshell.queue.failed'));
    }
}
