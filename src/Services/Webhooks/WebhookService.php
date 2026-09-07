<?php

namespace Imran\WebShell\Services\Webhooks;

use Imran\WebShell\Exceptions\CommandBlockedException;
use Imran\WebShell\Services\Deployment\DeploymentService;
use Imran\WebShell\Support\JsonFileStore;

class WebhookService
{
    public function __construct(
        protected DeploymentService $deployments,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(string $provider, string $signature, string $rawBody, array $payload): array
    {
        if (! config('webshell.features.webhooks') && ! config('webshell.webhooks.enabled')) {
            throw new CommandBlockedException('Webhooks are disabled.');
        }

        $this->verify($provider, $signature, $rawBody);

        $branch = $this->branch($provider, $payload);
        $expected = (string) config('webshell.webhooks.branch', config('webshell.deployment.default_branch', 'main'));

        if ($branch !== $expected) {
            return [
                'ignored' => true,
                'reason' => "Branch [{$branch}] does not match [{$expected}].",
            ];
        }

        $deployment = $this->deployments->deploy(
            (string) config('webshell.webhooks.environment', 'production'),
            $branch,
            true,
        );

        $this->store()->prepend([
            'provider' => $provider,
            'branch' => $branch,
            'deployment_id' => $deployment['id'] ?? null,
            'status' => $deployment['status'] ?? 'unknown',
            'created_at' => now()->toIso8601String(),
        ], 100);

        return $deployment;
    }

    public function verify(string $provider, string $signature, string $rawBody): void
    {
        $secret = (string) config('webshell.webhooks.secret', '');

        if ($secret === '') {
            throw new CommandBlockedException('WEBSHELL_WEBHOOK_SECRET is not configured.');
        }

        $valid = match ($provider) {
            'github' => $this->github($signature, $rawBody, $secret),
            'gitlab' => hash_equals($secret, $signature),
            'bitbucket' => $this->github($signature, $rawBody, $secret),
            default => throw new CommandBlockedException("Unknown webhook provider [{$provider}]."),
        };

        if (! $valid) {
            throw new CommandBlockedException('Webhook signature is invalid.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function branch(string $provider, array $payload): string
    {
        $ref = match ($provider) {
            'github', 'gitlab' => (string) ($payload['ref'] ?? ''),
            'bitbucket' => (string) data_get($payload, 'push.changes.0.new.name', ''),
            default => '',
        };

        return str_starts_with($ref, 'refs/heads/') ? substr($ref, 11) : $ref;
    }

    protected function github(string $signature, string $rawBody, string $secret): bool
    {
        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    protected function store(): JsonFileStore
    {
        return new JsonFileStore(rtrim((string) config('webshell.storage_path'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'webhooks.json');
    }
}
