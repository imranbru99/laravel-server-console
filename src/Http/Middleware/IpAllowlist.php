<?php

namespace Imran\WebShell\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowlist = array_values(array_filter((array) config('webshell.security.ip_allowlist', [])));

        if ($allowlist === []) {
            return $next($request);
        }

        if (! in_array($request->ip(), $allowlist, true)) {
            abort(403, 'Your IP address is not allowed to use WebShell.');
        }

        return $next($request);
    }
}
