<?php

namespace Imran\WebShell\Support;

class ValidationVerdict
{
    public function __construct(
        public bool $allowed,
        public ?string $reason = null,
        public bool $dangerous = false,
        public bool $requiresConfirmation = false,
        public string $severity = 'info',
    ) {}

    public static function allow(): self
    {
        return new self(true);
    }

    public static function deny(string $reason, string $severity = 'critical'): self
    {
        return new self(false, $reason, severity: $severity);
    }

    public static function confirm(string $reason): self
    {
        return new self(
            allowed: false,
            reason: $reason,
            dangerous: true,
            requiresConfirmation: true,
            severity: 'warning',
        );
    }
}
