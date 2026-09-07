<?php

namespace Imran\WebShell\Services\Approvals;

use Imran\WebShell\Exceptions\CommandBlockedException;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;
use Imran\WebShell\Support\JsonFileStore;

class ApprovalService
{
    public function __construct(
        protected ProcessManager $processes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function request(CommandRequest $request, mixed $user = null): array
    {
        $record = [
            'id' => bin2hex(random_bytes(6)),
            'command' => $request->command,
            'working_directory' => $request->workingDirectory,
            'source' => $request->source,
            'status' => 'pending',
            'requested_by' => $user?->getAuthIdentifier() ?? $request->userId,
            'ip' => $request->ip,
            'created_at' => now()->toIso8601String(),
        ];

        $this->store()->prepend($record, 200);

        return $record;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pending(): array
    {
        return array_values(array_filter($this->all(), fn ($row) => ($row['status'] ?? '') === 'pending'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->store()->all();
    }

    public function approve(string $id, mixed $user = null): CommandResult
    {
        $record = $this->find($id);

        if (($record['status'] ?? '') !== 'pending') {
            throw new CommandBlockedException('This approval is no longer pending.');
        }

        $result = $this->processes->run(CommandRequest::make($record['command'], [
            'working_directory' => $record['working_directory'] ?? null,
            'confirmed' => true,
            'source' => 'approval',
            'user_id' => $user?->getAuthIdentifier(),
        ]));

        $this->update($id, [
            'status' => $result->successful() ? 'approved' : 'failed',
            'approved_by' => $user?->getAuthIdentifier(),
            'result_id' => $result->id,
            'finished_at' => now()->toIso8601String(),
        ]);

        return $result;
    }

    public function reject(string $id, mixed $user = null): void
    {
        $this->update($id, [
            'status' => 'rejected',
            'rejected_by' => $user?->getAuthIdentifier(),
            'finished_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function find(string $id): array
    {
        foreach ($this->all() as $record) {
            if (($record['id'] ?? null) === $id) {
                return $record;
            }
        }

        throw new CommandBlockedException('Approval request was not found.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function update(string $id, array $attributes): void
    {
        $records = $this->all();

        foreach ($records as &$record) {
            if (($record['id'] ?? null) === $id) {
                $record = array_merge($record, $attributes);
            }
        }

        $this->store()->write($records);
    }

    protected function store(): JsonFileStore
    {
        return new JsonFileStore(rtrim((string) config('webshell.storage_path'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'approvals.json');
    }
}
