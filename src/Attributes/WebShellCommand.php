<?php

namespace Imran\WebShell\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class WebShellCommand
{
    public function __construct(
        public string $id,
        public string $label,
        public ?string $command = null,
        public ?string $description = null,
    ) {}
}
