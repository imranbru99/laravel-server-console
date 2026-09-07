<?php

namespace Imran\WebShell\Contracts;

use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;

interface AuditLogger
{
    public function record(CommandRequest $request, CommandResult $result): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(int $limit = 50): array;

    public function export(string $format = 'json'): string;
}
