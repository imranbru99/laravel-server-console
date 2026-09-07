<?php

namespace Imran\WebShell;

use Imran\WebShell\Services\Artisan\ArtisanService;
use Imran\WebShell\Services\Composer\ComposerService;
use Imran\WebShell\Services\Git\GitService;
use Imran\WebShell\Services\Node\NodeService;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Services\Registry\PluginRegistry;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;

class WebShell
{
    public function __construct(
        protected ProcessManager $processes,
        protected ArtisanService $artisan,
        protected GitService $git,
        protected ComposerService $composer,
        protected NodeService $node,
        protected PluginRegistry $registry,
    ) {}

    public function run(string $command, array $options = []): CommandResult
    {
        return $this->processes->run(CommandRequest::make($command, $options));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function artisan(string $command, array $parameters = [], array $context = []): CommandResult
    {
        return $this->artisan->run($command, $parameters, $context);
    }

    public function git(string $command, array $context = []): CommandResult
    {
        return $this->git->run($command, $context);
    }

    public function composer(string $command, array $context = []): CommandResult
    {
        return $this->composer->run($command, $context);
    }

    public function node(string $command, array $context = []): CommandResult
    {
        return $this->node->run($command, $context);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function registerCommand(string $id, array $definition): static
    {
        $this->registry->registerCommand($id, $definition);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function registerTool(string $id, array $definition): static
    {
        $this->registry->registerTool($id, $definition);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function registerPanel(string $id, array $definition): static
    {
        $this->registry->registerPanel($id, $definition);

        return $this;
    }

    public function registerHealthCheck(string $id, callable $check): static
    {
        $this->registry->registerHealthCheck($id, $check);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $step
     */
    public function registerDeploymentStep(string $id, array $step): static
    {
        $this->registry->registerDeploymentStep($id, $step);

        return $this;
    }

    public function registerArtisan(string $signature): static
    {
        $this->registry->artisan($signature);

        return $this;
    }

    public function registry(): PluginRegistry
    {
        return $this->registry;
    }

    public function processes(): ProcessManager
    {
        return $this->processes;
    }
}
