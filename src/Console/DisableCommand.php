<?php

namespace Imran\WebShell\Console;

use Illuminate\Console\Command;

class DisableCommand extends Command
{
    protected $signature = 'webshell:disable';

    protected $description = 'Emergency-disable Laravel WebShell by writing WEBSHELL_ENABLED=false';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            $this->components->error('.env was not found. Set WEBSHELL_ENABLED=false manually.');

            return self::FAILURE;
        }

        $contents = (string) file_get_contents($envPath);

        if (preg_match('/^WEBSHELL_ENABLED=.*$/m', $contents)) {
            $contents = preg_replace('/^WEBSHELL_ENABLED=.*$/m', 'WEBSHELL_ENABLED=false', $contents);
        } else {
            $contents = rtrim($contents).PHP_EOL.'WEBSHELL_ENABLED=false'.PHP_EOL;
        }

        file_put_contents($envPath, $contents);

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($envPath, true);
        }

        $this->components->warn('Laravel WebShell has been disabled. Routes will 404 until WEBSHELL_ENABLED=true.');

        return self::SUCCESS;
    }
}
