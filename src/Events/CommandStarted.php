<?php

namespace Imran\WebShell\Events;

use Imran\WebShell\Support\CommandRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public CommandRequest $request) {}
}
