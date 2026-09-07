<?php

namespace Imran\WebShell\Services\Security;

use Imran\WebShell\Contracts\CommandValidator as CommandValidatorContract;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\ValidationVerdict;

class CommandValidator implements CommandValidatorContract
{
    public function __construct(
        protected DangerousCommandDetector $detector,
    ) {}

    public function validate(CommandRequest $request): ValidationVerdict
    {
        if ($request->command === '') {
            return ValidationVerdict::deny('Command cannot be empty.');
        }

        if ($this->containsNullByte($request->command)) {
            return ValidationVerdict::deny('Command contains invalid characters.');
        }

        if (config('webshell.security.read_only') && ! $this->matchesAny($request->command, (array) config('webshell.policies.read_only_allowlist', []))) {
            return ValidationVerdict::deny('WebShell is in read-only mode. This command is not permitted.');
        }

        $allowlist = (array) config('webshell.policies.allowlist', []);

        if ($allowlist !== [] && ! $this->matchesAny($request->command, $allowlist)) {
            return ValidationVerdict::deny('Command is not on the allowlist.');
        }

        $denylist = (array) config('webshell.policies.denylist', []);

        if ($this->matchesAny($request->command, $denylist)) {
            return ValidationVerdict::deny('Command is explicitly denied.');
        }

        if (! $this->familyAllowed($request)) {
            return ValidationVerdict::deny("The {$request->family()} tool family is disabled by policy.");
        }

        $dangerous = $this->detector->isDangerous($request->command);
        $blockDangerous = (bool) config('webshell.security.block_dangerous_commands', true);
        $productionBlock = app()->environment('production') && config('webshell.production.block_destructive_commands', true);

        if ($dangerous && ($blockDangerous || $productionBlock) && ! config('webshell.policies.dangerous')) {
            if ($request->confirmed && config('webshell.security.require_dangerous_confirmation', true)) {
                return ValidationVerdict::allow();
            }

            if (config('webshell.security.require_dangerous_confirmation', true) && ! $productionBlock) {
                return ValidationVerdict::confirm('This command can permanently destroy data or take the server offline. Confirm to continue.');
            }

            return ValidationVerdict::deny('Dangerous command blocked by security policy.');
        }

        if ($dangerous && ! $request->confirmed && config('webshell.security.confirmation', true)) {
            return ValidationVerdict::confirm('This command looks dangerous. Confirm that you understand the risk.');
        }

        if (app()->environment('production') && config('webshell.production.require_confirmation') && ! $request->confirmed && $this->isMutating($request)) {
            return ValidationVerdict::confirm('You are about to run a mutating command in production.');
        }

        return ValidationVerdict::allow();
    }

    protected function familyAllowed(CommandRequest $request): bool
    {
        $policy = config('webshell.policies.'.$request->family());

        if ($policy === null || $policy === true) {
            return true;
        }

        if ($policy === false) {
            return false;
        }

        if (! is_array($policy)) {
            return true;
        }

        $tokens = $request->tokens();
        $subcommand = strtolower((string) ($tokens[1] ?? ''));

        if ($request->family() === 'artisan') {
            $signature = strtolower((string) $request->artisanSignature());

            return in_array($signature, array_map('strtolower', $policy), true);
        }

        return in_array($subcommand, array_map('strtolower', $policy), true);
    }

    protected function isMutating(CommandRequest $request): bool
    {
        return (bool) preg_match('/\b(migrate|wipe|fresh|reset|push|commit|rm|delete|drop|install|update|require|remove|build|deploy|optimize|down)\b/i', $request->command);
    }

    /**
     * @param  list<string>  $patterns
     */
    protected function matchesAny(string $command, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = trim((string) $pattern);

            if ($pattern === '') {
                continue;
            }

            if (str_starts_with($pattern, '/') && strrpos($pattern, '/') > 0) {
                if (@preg_match($pattern, $command) === 1) {
                    return true;
                }

                continue;
            }

            if (strcasecmp($command, $pattern) === 0 || str_starts_with(strtolower($command), strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    protected function containsNullByte(string $command): bool
    {
        return str_contains($command, "\0");
    }
}
