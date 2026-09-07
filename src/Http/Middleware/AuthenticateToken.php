<?php

namespace Imran\WebShell\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Imran\WebShell\Services\Api\TokenAuthenticator;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    public function __construct(
        protected TokenAuthenticator $tokens,
    ) {}

    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $record = $this->tokens->authenticate($request->bearerToken());

        if (! $record) {
            abort(401, 'A scoped WebShell API token is required.');
        }

        if ($scope && ! $this->tokens->can($record, $scope)) {
            abort(403, "Token is missing the [{$scope}] scope.");
        }

        $request->attributes->set('webshell_token', $record);

        return $next($request);
    }
}
