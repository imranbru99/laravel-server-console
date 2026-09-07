<?php

namespace Imran\WebShell\Services\Security;

class DangerousCommandDetector
{
    /**
     * @return list<string>
     */
    public function matches(string $command): array
    {
        $hits = [];

        foreach ((array) config('webshell.dangerous_patterns', []) as $pattern) {
            $regex = '~'.$pattern.'~i';

            if (@preg_match($regex, $command) === 1) {
                $hits[] = $pattern;
            }
        }

        return $hits;
    }

    public function isDangerous(string $command): bool
    {
        return $this->matches($command) !== [];
    }
}
