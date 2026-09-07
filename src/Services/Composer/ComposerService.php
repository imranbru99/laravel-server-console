<?php

namespace Imran\WebShell\Services\Composer;

use Illuminate\Support\Facades\File;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;

class ComposerService
{
    public function __construct(
        protected ProcessManager $processes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $path = base_path('composer.json');

        if (! File::exists($path)) {
            return ['available' => false, 'message' => 'composer.json was not found.'];
        }

        $json = json_decode((string) File::get($path), true) ?: [];
        $lock = File::exists(base_path('composer.lock'))
            ? json_decode((string) File::get(base_path('composer.lock')), true)
            : [];

        $packages = collect($lock['packages'] ?? [])->map(fn ($package) => [
            'name' => $package['name'] ?? '',
            'version' => $package['version'] ?? '',
        ])->take(40)->values()->all();

        return [
            'available' => true,
            'name' => $json['name'] ?? null,
            'require' => $json['require'] ?? [],
            'require_dev' => $json['require-dev'] ?? [],
            'packages' => $packages,
        ];
    }

    public function run(string $arguments, array $context = []): CommandResult
    {
        return $this->processes->run(CommandRequest::make(
            'composer '.$arguments,
            array_merge($context, ['source' => 'composer']),
        ));
    }
}
