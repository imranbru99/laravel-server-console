<?php

namespace Imran\WebShell\Support;

class CommandRequest
{
    /**
     * @param  array<string, string>  $environment
     */
    public function __construct(
        public string $command,
        public ?string $workingDirectory = null,
        public ?int $timeout = null,
        public ?int $maxOutput = null,
        public bool $background = false,
        public bool $confirmed = false,
        public string $source = 'terminal',
        public ?string $project = null,
        public array $environment = [],
        public ?int $userId = null,
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {
        $this->command = trim($this->command);
        $this->workingDirectory = $this->workingDirectory ?: (string) config('webshell.terminal.working_directory', base_path());
        $this->timeout ??= (int) config('webshell.terminal.timeout', 60);
        $this->maxOutput ??= (int) config('webshell.terminal.max_output', 1024 * 1024);
    }

    public static function make(string $command, array $attributes = []): self
    {
        return new self(
            command: $command,
            workingDirectory: $attributes['working_directory'] ?? $attributes['cwd'] ?? null,
            timeout: $attributes['timeout'] ?? null,
            maxOutput: $attributes['max_output'] ?? null,
            background: (bool) ($attributes['background'] ?? false),
            confirmed: (bool) ($attributes['confirmed'] ?? false),
            source: (string) ($attributes['source'] ?? 'terminal'),
            project: $attributes['project'] ?? null,
            environment: $attributes['environment'] ?? [],
            userId: $attributes['user_id'] ?? null,
            ip: $attributes['ip'] ?? null,
            userAgent: $attributes['user_agent'] ?? null,
        );
    }

    public function binary(): string
    {
        $parts = preg_split('/\s+/', $this->command) ?: [];

        return strtolower((string) ($parts[0] ?? ''));
    }

    public function tokens(): array
    {
        return preg_split('/\s+/', $this->command) ?: [];
    }

    public function isArtisan(): bool
    {
        return (bool) preg_match('/^(php\s+)?artisan\b/i', $this->command);
    }

    public function artisanSignature(): ?string
    {
        if (! preg_match('/^(?:php\s+)?artisan\s+([^\s]+)/i', $this->command, $matches)) {
            return null;
        }

        return $matches[1];
    }

    public function family(): string
    {
        if ($this->isArtisan()) {
            return 'artisan';
        }

        return match ($this->binary()) {
            'git' => 'git',
            'composer' => 'composer',
            'npm', 'npx', 'node', 'yarn', 'pnpm' => 'node',
            default => 'shell',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'working_directory' => $this->workingDirectory,
            'timeout' => $this->timeout,
            'max_output' => $this->maxOutput,
            'background' => $this->background,
            'confirmed' => $this->confirmed,
            'source' => $this->source,
            'project' => $this->project,
            'family' => $this->family(),
            'user_id' => $this->userId,
            'ip' => $this->ip,
        ];
    }
}
