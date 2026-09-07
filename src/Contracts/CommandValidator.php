<?php

namespace Imran\WebShell\Contracts;

use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\ValidationVerdict;

interface CommandValidator
{
    public function validate(CommandRequest $request): ValidationVerdict;
}
