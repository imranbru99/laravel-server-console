<?php

namespace Imran\WebShell\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebShellEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('webshell.enabled')) {
            abort(404);
        }

        return $next($request);
    }
}
