<?php

namespace Imran\WebShell\Console;

use Illuminate\Console\Command;
use Imran\WebShell\Services\Health\HealthChecker;

class DoctorCommand extends Command
{
    protected $signature = 'webshell:doctor';

    protected $description = 'Diagnose Laravel WebShell capabilities and Laravel application health';

    public function handle(HealthChecker $health): int
    {
        $this->components->info('Laravel WebShell Doctor');

        $report = $health->doctor();

        foreach ($report['capabilities']['checks'] as $check) {
            $icon = $check['ok'] ? '<info>✓</info>' : '<comment>✗</comment>';
            $this->components->twoColumnDetail($check['label'], $icon.' '.$check['detail']);
        }

        $this->newLine();
        $this->components->twoColumnDetail('Health passed', (string) $report['health']['passed']);
        $this->components->twoColumnDetail('Warnings', (string) $report['health']['warnings']);
        $this->components->twoColumnDetail('Critical', (string) $report['health']['critical']);

        if ($report['recommendations'] !== []) {
            $this->newLine();
            $this->components->warn('Recommendations');

            foreach ($report['recommendations'] as $item) {
                $this->line('  ['.$item['severity'].'] '.$item['title']);
                $this->line('      '.$item['recommendation']);
            }
        }

        if ($report['capabilities']['restricted_mode']) {
            $this->newLine();
            $this->components->warn('WebShell can operate in restricted mode. Process execution is unavailable on this host.');
        }

        return $report['health']['critical'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
