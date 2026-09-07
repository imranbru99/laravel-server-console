<?php

namespace Imran\WebShell\Support;

class CommandResult
{
    public function __construct(
        public string $id,
        public string $command,
        public int $exitCode,
        public string $output,
        public string $errorOutput,
        public float $duration,
        public string $workingDirectory,
        public string $status = 'completed',
        public ?int $pid = null,
        public bool $truncated = false,
        public bool $timedOut = false,
        public bool $blocked = false,
        public ?string $blockReason = null,
        public bool $requiresConfirmation = false,
        public ?string $startedAt = null,
        public ?string $finishedAt = null,
    ) {}

    public static function blocked(string $command, string $reason, bool $requiresConfirmation = false): self
    {
        return new self(
            id: self::newId(),
            command: $command,
            exitCode: 126,
            output: '',
            errorOutput: $reason,
            duration: 0,
            workingDirectory: (string) config('webshell.terminal.working_directory', base_path()),
            status: $requiresConfirmation ? 'needs_confirmation' : 'blocked',
            blocked: true,
            blockReason: $reason,
            requiresConfirmation: $requiresConfirmation,
            startedAt: now()->toIso8601String(),
            finishedAt: now()->toIso8601String(),
        );
    }

    public static function pendingApproval(string $command, string $id): self
    {
        return new self(
            id: $id,
            command: $command,
            exitCode: 0,
            output: "Queued for administrator approval (#{$id}).",
            errorOutput: '',
            duration: 0,
            workingDirectory: (string) config('webshell.terminal.working_directory', base_path()),
            status: 'pending_approval',
            startedAt: now()->toIso8601String(),
            finishedAt: now()->toIso8601String(),
        );
    }

    public static function failed(string $command, string $message, int $exitCode = 1): self
    {
        return new self(
            id: self::newId(),
            command: $command,
            exitCode: $exitCode,
            output: '',
            errorOutput: $message,
            duration: 0,
            workingDirectory: (string) config('webshell.terminal.working_directory', base_path()),
            status: 'failed',
            startedAt: now()->toIso8601String(),
            finishedAt: now()->toIso8601String(),
        );
    }

    public function successful(): bool
    {
        return ! $this->blocked && ! $this->timedOut && $this->exitCode === 0;
    }

    public function isFailed(): bool
    {
        return ! $this->successful();
    }

    public function exitCode(): int
    {
        return $this->exitCode;
    }

    public function output(): string
    {
        return $this->output;
    }

    public function errorOutput(): string
    {
        return $this->errorOutput;
    }

    public function duration(): float
    {
        return $this->duration;
    }

    public function combinedOutput(): string
    {
        return trim($this->output.($this->errorOutput !== '' ? "\n".$this->errorOutput : ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'command' => $this->command,
            'exit_code' => $this->exitCode,
            'output' => $this->output,
            'error_output' => $this->errorOutput,
            'combined_output' => $this->combinedOutput(),
            'duration' => $this->duration,
            'working_directory' => $this->workingDirectory,
            'status' => $this->status,
            'pid' => $this->pid,
            'truncated' => $this->truncated,
            'timed_out' => $this->timedOut,
            'blocked' => $this->blocked,
            'block_reason' => $this->blockReason,
            'requires_confirmation' => $this->requiresConfirmation,
            'successful' => $this->successful(),
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
        ];
    }

    public static function newId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
