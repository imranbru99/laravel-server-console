<?php

namespace Imran\WebShell\Exceptions;

class CommandBlockedException extends WebShellException
{
    public function __construct(
        string $message,
        public readonly bool $requiresConfirmation = false,
    ) {
        parent::__construct($message);
    }
}
