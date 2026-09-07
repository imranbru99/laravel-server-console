<?php

namespace Imran\WebShell\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebShellLogin
{
    use Dispatchable, SerializesModels;

    public function __construct(public mixed $user) {}
}
