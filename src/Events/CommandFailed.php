<?php

namespace Imran\WebShell\Events;

use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CommandRequest $request,
        public CommandResult $result,
    ) {}
}
