<?php

namespace Imran\WebShell\Services\Registry;

class PluginRegistry
{
    /** @var array<string, array<string, mixed>> */
    protected array $commands = [];

    /** @var array<string, array<string, mixed>> */
    protected array $tools = [];

    /** @var array<string, array<string, mixed>> */
    protected array $panels = [];

    /** @var array<string, callable> */
    protected array $healthChecks = [];

    /** @var array<string, array<string, mixed>> */
    protected array $deploymentSteps = [];

    /** @var list<string> */
    protected array $artisan = [];

    /**
     * @param  array<string, mixed>  $definition
     */
    public function registerCommand(string $id, array $definition): static
    {
        $this->commands[$id] = array_merge(['id' => $id], $definition);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function registerTool(string $id, array $definition): static
    {
        $this->tools[$id] = array_merge(['id' => $id], $definition);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function registerPanel(string $id, array $definition): static
    {
        $this->panels[$id] = array_merge(['id' => $id], $definition);

        return $this;
    }

    public function registerHealthCheck(string $id, callable $check): static
    {
        $this->healthChecks[$id] = $check;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $step
     */
    public function registerDeploymentStep(string $id, array $step): static
    {
        $this->deploymentSteps[$id] = array_merge(['key' => $id], $step);

        return $this;
    }

    public function artisan(string $signature): static
    {
        $this->artisan[] = $signature;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'commands' => array_values($this->commands),
            'tools' => array_values($this->tools),
            'panels' => array_values($this->panels),
            'health_checks' => array_keys($this->healthChecks),
            'deployment_steps' => array_values($this->deploymentSteps),
            'artisan' => $this->artisan,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function commands(): array
    {
        return $this->commands;
    }
}
