<?php

namespace Imran\WebShell\Services\Process;

use Imran\WebShell\Contracts\AuditLogger;
use Imran\WebShell\Contracts\CommandValidator;
use Imran\WebShell\Contracts\HistoryStore;
use Imran\WebShell\Events\CommandBlocked;
use Imran\WebShell\Events\CommandCompleted;
use Imran\WebShell\Events\CommandFailed;
use Imran\WebShell\Events\CommandStarted;
use Imran\WebShell\Services\Security\WorkingDirectoryGuard;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;
use Imran\WebShell\Support\JsonFileStore;

class ProcessManager
{
    public function __construct(
        protected ProcessRunner $runner,
        protected CommandValidator $validator,
        protected HistoryStore $history,
        protected AuditLogger $audit,
        protected WorkingDirectoryGuard $directories,
    ) {}

    public function run(CommandRequest $request): CommandResult
    {
        $result = $this->before($request);

        if ($result !== null) {
            return $result;
        }

        if ($builtin = $this->builtin($request)) {
            $this->after($request, $builtin);

            return $builtin;
        }

        CommandStarted::dispatch($request);

        $result = $request->background
            ? $this->queueBackground($request)
            : $this->runner->run($request);

        $this->after($request, $result);

        return $result;
    }

    /**
     * @param  callable(string $stream, string $chunk): void  $onChunk
     */
    public function stream(CommandRequest $request, callable $onChunk): CommandResult
    {
        $result = $this->before($request);

        if ($result !== null) {
            $onChunk('stderr', (string) $result->errorOutput);

            return $result;
        }

        if ($builtin = $this->builtin($request)) {
            if ($builtin->output !== '') {
                $onChunk('stdout', $builtin->output);
            }
            $this->after($request, $builtin);

            return $builtin;
        }

        CommandStarted::dispatch($request);

        $result = $this->runner->stream($request, $onChunk);

        $this->after($request, $result);

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function processes(): array
    {
        return $this->store()->all();
    }

    protected function needsApproval(CommandRequest $request): bool
    {
        if ($request->source === 'approval') {
            return false;
        }

        return (bool) config('webshell.features.approvals')
            && (bool) config('webshell.production.require_approval')
            && app()->environment('production');
    }

    protected function builtin(CommandRequest $request): ?CommandResult
    {
        $command = trim($request->command);

        if ($command === 'pwd') {
            $cwd = $this->runnerCwd($request);

            return new CommandResult(
                id: CommandResult::newId(),
                command: $command,
                exitCode: 0,
                output: $cwd.PHP_EOL,
                errorOutput: '',
                duration: 0,
                workingDirectory: $cwd,
                status: 'completed',
                startedAt: now()->toIso8601String(),
                finishedAt: now()->toIso8601String(),
            );
        }

        if (preg_match('/^cd(?:\s+(.+))?$/', $command, $matches)) {
            $target = trim($matches[1] ?? '', "\"' ") ?: (string) config('webshell.terminal.working_directory', base_path());

            if (! str_starts_with($target, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $target)) {
                $target = rtrim((string) $request->workingDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$target;
            }

            try {
                $cwd = $this->directories->resolve($target);
            } catch (\Throwable $e) {
                return CommandResult::failed($command, $e->getMessage());
            }

            return new CommandResult(
                id: CommandResult::newId(),
                command: $command,
                exitCode: 0,
                output: $cwd.PHP_EOL,
                errorOutput: '',
                duration: 0,
                workingDirectory: $cwd,
                status: 'completed',
                startedAt: now()->toIso8601String(),
                finishedAt: now()->toIso8601String(),
            );
        }

        if ($command === 'clear' || $command === 'cls') {
            return new CommandResult(
                id: CommandResult::newId(),
                command: $command,
                exitCode: 0,
                output: '',
                errorOutput: '',
                duration: 0,
                workingDirectory: (string) $request->workingDirectory,
                status: 'completed',
                startedAt: now()->toIso8601String(),
                finishedAt: now()->toIso8601String(),
            );
        }

        return null;
    }

    protected function runnerCwd(CommandRequest $request): string
    {
        return $this->directories->resolve($request->workingDirectory);
    }

    protected function before(CommandRequest $request): ?CommandResult
    {
        $verdict = $this->validator->validate($request);

        if ($verdict->allowed) {
            if ($this->needsApproval($request)) {
                $record = app(\Imran\WebShell\Services\Approvals\ApprovalService::class)->request($request);

                return CommandResult::pendingApproval($request->command, $record['id']);
            }

            return null;
        }

        $result = CommandResult::blocked(
            $request->command,
            (string) $verdict->reason,
            $verdict->requiresConfirmation,
        );

        CommandBlocked::dispatch($request, $result);
        $this->audit->record($request, $result);

        return $result;
    }

    protected function after(CommandRequest $request, CommandResult $result): void
    {
        $this->history->record($request, $result);
        $this->audit->record($request, $result);

        if ($result->successful()) {
            CommandCompleted::dispatch($request, $result);
        } elseif (! $result->blocked) {
            CommandFailed::dispatch($request, $result);
        }
    }

    protected function queueBackground(CommandRequest $request): CommandResult
    {
        $id = CommandResult::newId();

        $this->store()->prepend([
            'id' => $id,
            'command' => $request->command,
            'status' => 'queued',
            'working_directory' => $request->workingDirectory,
            'created_at' => now()->toIso8601String(),
        ], 100);

        return new CommandResult(
            id: $id,
            command: $request->command,
            exitCode: 0,
            output: "Queued as background job #{$id}.",
            errorOutput: '',
            duration: 0,
            workingDirectory: (string) $request->workingDirectory,
            status: 'queued',
            startedAt: now()->toIso8601String(),
            finishedAt: now()->toIso8601String(),
        );
    }

    protected function store(): JsonFileStore
    {
        return new JsonFileStore(rtrim((string) config('webshell.storage_path'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'processes.json');
    }
}
