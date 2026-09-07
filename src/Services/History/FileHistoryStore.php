<?php

namespace Imran\WebShell\Services\History;

use Imran\WebShell\Contracts\HistoryStore;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;
use Imran\WebShell\Support\JsonFileStore;

class FileHistoryStore implements HistoryStore
{
    public function record(CommandRequest $request, CommandResult $result): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->store()->prepend([
            'id' => $result->id,
            'command' => $request->command,
            'source' => $request->source,
            'family' => $request->family(),
            'exit_code' => $result->exitCode,
            'duration' => $result->duration,
            'status' => $result->status,
            'favorite' => false,
            'working_directory' => $result->workingDirectory,
            'user_id' => $request->userId,
            'created_at' => $result->finishedAt ?? now()->toIso8601String(),
        ], (int) config('webshell.terminal.history_limit', 200));
    }

    public function latest(int $limit = 100, ?string $search = null): array
    {
        $records = $this->store()->all();

        if ($search) {
            $needle = strtolower($search);
            $records = array_values(array_filter($records, fn ($row) => str_contains(strtolower((string) ($row['command'] ?? '')), $needle)));
        }

        return array_slice($records, 0, $limit);
    }

    public function favorite(string $id, bool $favorite = true): void
    {
        $records = $this->store()->all();

        foreach ($records as &$record) {
            if (($record['id'] ?? null) === $id) {
                $record['favorite'] = $favorite;
            }
        }

        $this->store()->write($records);
    }

    public function delete(string $id): void
    {
        $records = array_values(array_filter($this->store()->all(), fn ($row) => ($row['id'] ?? null) !== $id));
        $this->store()->write($records);
    }

    public function clear(): void
    {
        $this->store()->write([]);
    }

    protected function enabled(): bool
    {
        return config('webshell.storage') !== 'disabled' && config('webshell.features.history', true);
    }

    protected function store(): JsonFileStore
    {
        return new JsonFileStore($this->path());
    }

    protected function path(): string
    {
        return rtrim((string) config('webshell.storage_path'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'history.json';
    }
}
