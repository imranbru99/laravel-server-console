<?php

namespace Imran\WebShell\Contracts;

use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;

interface HistoryStore
{
    public function record(CommandRequest $request, CommandResult $result): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(int $limit = 100, ?string $search = null): array;

    public function favorite(string $id, bool $favorite = true): void;

    public function delete(string $id): void;

    public function clear(): void;
}
