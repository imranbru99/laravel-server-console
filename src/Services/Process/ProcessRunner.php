<?php

namespace Imran\WebShell\Services\Process;

use Imran\WebShell\Contracts\ProcessRunner as ProcessRunnerContract;
use Imran\WebShell\Exceptions\CapabilityException;
use Imran\WebShell\Services\Security\WorkingDirectoryGuard;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ProcessRunner implements ProcessRunnerContract
{
    public function __construct(
        protected WorkingDirectoryGuard $directories,
    ) {}

    public function run(CommandRequest $request): CommandResult
    {
        return $this->stream($request, function (): void {});
    }

    public function stream(CommandRequest $request, callable $onChunk): CommandResult
    {
        $this->assertProcessSupport();

        $cwd = $this->directories->resolve($request->workingDirectory);
        $id = CommandResult::newId();
        $startedAt = now();
        $started = microtime(true);

        $process = Process::fromShellCommandline(
            $request->command,
            $cwd,
            $this->environment($request),
        );

        $process->setTimeout($request->timeout);
        $process->setIdleTimeout((int) config('webshell.terminal.idle_timeout', 30));

        $stdout = '';
        $stderr = '';
        $truncated = false;
        $max = $request->maxOutput ?? (int) config('webshell.terminal.max_output', 1024 * 1024);

        try {
            $process->run(function (string $type, string $buffer) use ($onChunk, &$stdout, &$stderr, &$truncated, $max): void {
                if ($truncated) {
                    return;
                }

                $stream = $type === Process::ERR ? 'stderr' : 'stdout';

                if ($stream === 'stderr') {
                    $stderr = $this->append($stderr, $buffer, $max, $truncated);
                } else {
                    $stdout = $this->append($stdout, $buffer, $max, $truncated);
                }

                $onChunk($stream, $buffer);
            });
        } catch (ProcessTimedOutException) {
            $this->stop($process);

            return new CommandResult(
                id: $id,
                command: $request->command,
                exitCode: $process->getExitCode() ?? 124,
                output: $stdout,
                errorOutput: trim($stderr."\nProcess timed out after {$request->timeout} seconds."),
                duration: round(microtime(true) - $started, 3),
                workingDirectory: $cwd,
                status: 'timeout',
                pid: $process->getPid(),
                truncated: $truncated,
                timedOut: true,
                startedAt: $startedAt->toIso8601String(),
                finishedAt: now()->toIso8601String(),
            );
        }

        return new CommandResult(
            id: $id,
            command: $request->command,
            exitCode: (int) ($process->getExitCode() ?? 1),
            output: $stdout,
            errorOutput: $stderr,
            duration: round(microtime(true) - $started, 3),
            workingDirectory: $cwd,
            status: ($process->getExitCode() === 0) ? 'completed' : 'failed',
            pid: $process->getPid(),
            truncated: $truncated,
            startedAt: $startedAt->toIso8601String(),
            finishedAt: now()->toIso8601String(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function environment(CommandRequest $request): array
    {
        $defaults = [
            'TERM' => 'xterm-256color',
            'FORCE_COLOR' => '1',
            'COMPOSER_NO_INTERACTION' => '1',
            'GIT_TERMINAL_PROMPT' => '0',
            'WEBSHELL' => '1',
        ];

        $configured = (array) config('webshell.terminal.environment', []);

        return array_merge($defaults, $configured, $request->environment);
    }

    protected function append(string $current, string $chunk, int $max, bool &$truncated): string
    {
        if (strlen($current) + strlen($chunk) <= $max) {
            return $current.$chunk;
        }

        $remaining = $max - strlen($current);
        $truncated = true;

        return $remaining > 0 ? $current.substr($chunk, 0, $remaining)."\n[output truncated]" : $current;
    }

    protected function stop(Process $process): void
    {
        if ($process->isRunning()) {
            $process->stop(1);
        }
    }

    protected function assertProcessSupport(): void
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (! function_exists('proc_open') || in_array('proc_open', $disabled, true)) {
            throw new CapabilityException('proc_open() is disabled. WebShell cannot execute processes in this hosting environment.');
        }
    }
}
