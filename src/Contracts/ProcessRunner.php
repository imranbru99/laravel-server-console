<?php

namespace Imran\WebShell\Contracts;

use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;

interface ProcessRunner
{
    public function run(CommandRequest $request): CommandResult;

    /**
     * @param  callable(string $stream, string $chunk): void  $onChunk
     */
    public function stream(CommandRequest $request, callable $onChunk): CommandResult;
}
