<?php

namespace Imran\WebShell\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebShellLogout
{
    use Dispatchable, SerializesModels;

    public function __construct(public mixed $user) {}
}
