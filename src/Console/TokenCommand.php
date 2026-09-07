<?php

namespace Imran\WebShell\Console;

use Illuminate\Console\Command;

class TokenCommand extends Command
{
    protected $signature = 'webshell:token {--name=ci : Token label} {--scopes=webshell:health : Comma-separated scopes}';

    protected $description = 'Generate a scoped WebShell API token (never unrestricted)';

    public function handle(): int
    {
        $scopes = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('scopes')))));
        $allowed = (array) config('webshell.api.scopes', []);

        if (in_array('*', $scopes, true)) {
            $this->components->error('Unrestricted tokens (*) are not allowed.');

            return self::FAILURE;
        }

        foreach ($scopes as $scope) {
            if (! in_array($scope, $allowed, true)) {
                $this->components->error("Unknown scope [{$scope}]. Allowed: ".implode(', ', $allowed));

                return self::FAILURE;
            }
        }

        $token = 'wsh_'.bin2hex(random_bytes(24));

        $this->components->info('Store this token now. It will not be shown again.');
        $this->line('  WEBSHELL_API=true');
        $this->line('  WEBSHELL_API_TOKEN='.$token);
        $this->line('  WEBSHELL_API_SCOPES='.implode(',', $scopes));
        $this->newLine();
        $this->components->warn('Never create an unrestricted API token. Scopes: '.implode(', ', $scopes));

        return self::SUCCESS;
    }
}
