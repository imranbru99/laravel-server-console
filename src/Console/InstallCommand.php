<?php

namespace Imran\WebShell\Console;

use Illuminate\Console\Command;
use Imran\WebShell\Services\Capability\CapabilityDetector;

class InstallCommand extends Command
{
    protected $signature = 'webshell:install {--force : Overwrite existing published files}';

    protected $description = 'Publish Laravel WebShell configuration and run environment diagnostics';

    public function handle(CapabilityDetector $capabilities): int
    {
        $this->components->info('Installing Laravel WebShell');

        $this->callSilent('vendor:publish', [
            '--tag' => 'webshell-config',
            '--force' => $this->option('force'),
        ]);

        $path = (string) config('webshell.storage_path');

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $report = $capabilities->detect();

        foreach ($report['checks'] as $check) {
            if ($check['ok']) {
                $this->components->twoColumnDetail($check['label'], '<info>✓</info> '.$check['detail']);
            } else {
                $this->components->twoColumnDetail($check['label'], '<comment>✗</comment> '.$check['detail']);
            }
        }

        $this->newLine();
        $this->components->warn('WebShell is a privileged console. Keep WEBSHELL_ENABLED=false until authentication, a gate, and an IP allowlist are configured.');
        $this->components->info('URL: /'.trim((string) config('webshell.path'), '/'));
        $this->components->info('Then set WEBSHELL_ENABLED=true in your environment.');

        if ($report['restricted_mode']) {
            $this->components->warn('Hosting Compatibility Mode: proc_open is unavailable. WebShell will operate in a restricted, mostly read-only diagnostic mode.');
        }

        return self::SUCCESS;
    }
}
