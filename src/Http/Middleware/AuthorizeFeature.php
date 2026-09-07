<?php

namespace Imran\WebShell\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Imran\WebShell\Services\Security\RoleService;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeFeature
{
    public function __construct(
        protected RoleService $roles,
    ) {}

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        if (! $this->roles->can($request->user(), $ability)) {
            abort(403, "Your WebShell role cannot use [{$ability}].");
        }

        return $next($request);
    }
}
