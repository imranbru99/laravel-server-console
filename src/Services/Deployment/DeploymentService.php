<?php

namespace Imran\WebShell\Services\Deployment;

use Imran\WebShell\Events\DeploymentCompleted;
use Imran\WebShell\Events\DeploymentFailed;
use Imran\WebShell\Events\DeploymentStarted;
use Imran\WebShell\Exceptions\CommandBlockedException;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\JsonFileStore;

class DeploymentService
{
    public function __construct(
        protected ProcessManager $processes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return [
            'environments' => config('webshell.deployment.environments', []),
            'default_environment' => config('webshell.deployment.default_environment'),
            'default_branch' => config('webshell.deployment.default_branch'),
            'steps' => config('webshell.deployment.steps', []),
        ];
    }

    /**
     * @param  list<string>|null  $only
     * @return array<string, mixed>
     */
    public function deploy(string $environment, ?string $branch = null, bool $confirmed = false, ?array $only = null): array
    {
        $environments = (array) config('webshell.deployment.environments', []);
        $meta = $environments[$environment] ?? null;

        if (! $meta) {
            throw new CommandBlockedException("Unknown deployment environment [{$environment}].");
        }

        if (($meta['confirm'] ?? false) && ! $confirmed) {
            throw new CommandBlockedException("Deploying to {$environment} requires explicit confirmation.", true);
        }

        $id = bin2hex(random_bytes(6));
        $started = microtime(true);
        $steps = (array) config('webshell.deployment.steps', []);
        $results = [];

        $deployment = [
            'id' => $id,
            'environment' => $environment,
            'branch' => $branch ?: ($meta['branch'] ?? config('webshell.deployment.default_branch')),
            'status' => 'running',
            'started_at' => now()->toIso8601String(),
        ];

        DeploymentStarted::dispatch($deployment);

        foreach ($steps as $step) {
            if ($only && ! in_array($step['key'], $only, true)) {
                continue;
            }

            $command = $step['command'];

            if (($step['key'] ?? '') === 'git' && $branch) {
                $command = 'git pull origin '.$branch;
            }

            $result = $this->processes->run(CommandRequest::make($command, [
                'source' => 'deployment',
                'confirmed' => $confirmed,
            ]));

            $results[] = [
                'key' => $step['key'],
                'label' => $step['label'],
                'successful' => $result->successful(),
                'exit_code' => $result->exitCode,
                'output' => $result->combinedOutput(),
                'duration' => $result->duration,
            ];

            if (! $result->successful() && ! ($step['optional'] ?? false)) {
                $deployment['status'] = 'failed';
                $deployment['steps'] = $results;
                $deployment['duration'] = round(microtime(true) - $started, 3);
                DeploymentFailed::dispatch($deployment);
                $this->store()->prepend($deployment, 50);
                app(\Imran\WebShell\Services\Notifications\NotificationService::class)->send(
                    'deployment_failed',
                    'Deployment failed',
                    $deployment,
                );

                return $deployment;
            }
        }

        $deployment['status'] = 'completed';
        $deployment['steps'] = $results;
        $deployment['duration'] = round(microtime(true) - $started, 3);
        $deployment['finished_at'] = now()->toIso8601String();

        DeploymentCompleted::dispatch($deployment);
        $this->store()->prepend($deployment, 50);
        app(\Imran\WebShell\Services\Notifications\NotificationService::class)->send(
            'deployment_completed',
            'Deployment successful',
            $deployment,
        );

        return $deployment;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function history(): array
    {
        return $this->store()->all();
    }

    protected function store(): JsonFileStore
    {
        return new JsonFileStore(rtrim((string) config('webshell.storage_path'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'deployments.json');
    }
}
