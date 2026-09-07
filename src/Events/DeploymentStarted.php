<?php

namespace Imran\WebShell\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeploymentStarted
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $deployment
     */
    public function __construct(public array $deployment) {}
}
