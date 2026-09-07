<?php

namespace Imran\WebShell\Services\Git;

use Illuminate\Support\Facades\File;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;

class GitService
{
    public function __construct(
        protected ProcessManager $processes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        if (! File::isDirectory(base_path('.git'))) {
            return [
                'available' => false,
                'message' => 'This project is not a Git repository.',
            ];
        }

        $status = $this->run('status --porcelain=v1 --branch');
        $branches = $this->run('branch --list');
        $log = $this->run('log -n 12 --pretty=format:%h|%an|%ad|%s --date=iso');
        $remote = $this->run('remote -v');

        return [
            'available' => true,
            'status_output' => $status->combinedOutput(),
            'branch_output' => $branches->combinedOutput(),
            'log_output' => $log->combinedOutput(),
            'remote_output' => $remote->combinedOutput(),
            'dirty' => trim($status->output) !== '' && ! str_starts_with(trim($status->output), '##'),
        ];
    }

    public function run(string $arguments, array $context = []): CommandResult
    {
        return $this->processes->run(CommandRequest::make(
            'git '.$arguments,
            array_merge($context, ['source' => 'git']),
        ));
    }
}
