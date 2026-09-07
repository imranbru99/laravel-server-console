<?php

namespace Imran\WebShell\Services\Node;

use Illuminate\Support\Facades\File;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;

class NodeService
{
    public function __construct(
        protected ProcessManager $processes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $path = base_path('package.json');

        if (! File::exists($path)) {
            return ['available' => false, 'message' => 'package.json was not found.'];
        }

        $json = json_decode((string) File::get($path), true) ?: [];

        return [
            'available' => true,
            'name' => $json['name'] ?? null,
            'package_manager' => $this->detectManager(),
            'scripts' => $json['scripts'] ?? [],
            'dependencies' => $json['dependencies'] ?? [],
            'devDependencies' => $json['devDependencies'] ?? [],
        ];
    }

    public function detectManager(): string
    {
        if (File::exists(base_path('bun.lockb')) || File::exists(base_path('bun.lock'))) {
            return 'bun';
        }

        if (File::exists(base_path('pnpm-lock.yaml'))) {
            return 'pnpm';
        }

        if (File::exists(base_path('yarn.lock'))) {
            return 'yarn';
        }

        return 'npm';
    }

    public function run(string $arguments, array $context = [], ?string $manager = null): CommandResult
    {
        $bin = $manager ?: $this->detectManager();

        return $this->processes->run(CommandRequest::make(
            $bin.' '.$arguments,
            array_merge($context, ['source' => 'node', 'manager' => $bin]),
        ));
    }
}
