<?php

namespace Imran\WebShell\Facades;

use Illuminate\Support\Facades\Facade;
use Imran\WebShell\Support\CommandResult;

/**
 * @method static CommandResult run(string $command, array $options = [])
 * @method static CommandResult artisan(string $command, array $parameters = [], array $context = [])
 * @method static CommandResult git(string $command, array $context = [])
 * @method static CommandResult composer(string $command, array $context = [])
 * @method static CommandResult node(string $command, array $context = [])
 * @method static \Imran\WebShell\WebShell registerCommand(string $id, array $definition)
 * @method static \Imran\WebShell\WebShell registerTool(string $id, array $definition)
 * @method static \Imran\WebShell\WebShell registerPanel(string $id, array $definition)
 * @method static \Imran\WebShell\WebShell registerHealthCheck(string $id, callable $check)
 * @method static \Imran\WebShell\WebShell registerDeploymentStep(string $id, array $step)
 * @method static \Imran\WebShell\WebShell registerArtisan(string $signature)
 * @method static \Imran\WebShell\Services\Registry\PluginRegistry registry()
 *
 * @see \Imran\WebShell\WebShell
 */
class WebShell extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Imran\WebShell\WebShell::class;
    }
}
