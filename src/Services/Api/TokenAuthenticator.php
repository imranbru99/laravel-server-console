<?php

namespace Imran\WebShell\Services\Api;

class TokenAuthenticator
{
    /**
     * @return array<string, mixed>|null
     */
    public function authenticate(?string $bearer): ?array
    {
        if (! config('webshell.api.enabled')) {
            return null;
        }

        $token = trim((string) $bearer);

        if ($token === '') {
            return null;
        }

        foreach ((array) config('webshell.api.tokens', []) as $record) {
            $plain = (string) ($record['token'] ?? '');

            if ($plain !== '' && hash_equals($plain, $token)) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function can(array $record, string $scope): bool
    {
        $scopes = (array) ($record['scopes'] ?? []);

        if (in_array('*', $scopes, true)) {
            return false;
        }

        return in_array($scope, $scopes, true);
    }

    /**
     * @return list<string>
     */
    public function allowedScopes(): array
    {
        return array_values((array) config('webshell.api.scopes', []));
    }
}
