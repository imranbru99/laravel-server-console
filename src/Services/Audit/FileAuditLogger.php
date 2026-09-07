<?php

namespace Imran\WebShell\Services\Audit;

use Imran\WebShell\Contracts\AuditLogger;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;
use Imran\WebShell\Support\JsonFileStore;

class FileAuditLogger implements AuditLogger
{
    public function record(CommandRequest $request, CommandResult $result): void
    {
        if (! config('webshell.security.audit', true) || config('webshell.storage') === 'disabled') {
            return;
        }

        $this->store()->prepend([
            'id' => $result->id,
            'user_id' => $request->userId,
            'ip' => $request->ip,
            'user_agent' => $request->userAgent,
            'command' => $request->command,
            'source' => $request->source,
            'family' => $request->family(),
            'exit_code' => $result->exitCode,
            'duration' => $result->duration,
            'status' => $result->status,
            'blocked' => $result->blocked,
            'working_directory' => $result->workingDirectory,
            'created_at' => $result->finishedAt ?? now()->toIso8601String(),
        ], 1000);
    }

    public function latest(int $limit = 50): array
    {
        return array_slice($this->store()->all(), 0, $limit);
    }

    public function export(string $format = 'json'): string
    {
        $records = $this->store()->all();

        if ($format === 'csv') {
            $lines = ['id,created_at,user_id,ip,command,status,exit_code,duration'];

            foreach ($records as $row) {
                $lines[] = implode(',', array_map(
                    fn ($value) => '"'.str_replace('"', '""', (string) $value).'"',
                    [
                        $row['id'] ?? '',
                        $row['created_at'] ?? '',
                        $row['user_id'] ?? '',
                        $row['ip'] ?? '',
                        $row['command'] ?? '',
                        $row['status'] ?? '',
                        $row['exit_code'] ?? '',
                        $row['duration'] ?? '',
                    ],
                ));
            }

            return implode("\n", $lines);
        }

        return json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    protected function store(): JsonFileStore
    {
        return new JsonFileStore(rtrim((string) config('webshell.storage_path'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'audit.json');
    }
}
