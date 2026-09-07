<?php

namespace Imran\WebShell\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeWebShell
{
    public function handle(Request $request, Closure $next): Response
    {
        $gate = (string) config('webshell.authorization.gate', 'webshell.access');

        if ($gate !== '' && Gate::has($gate) && Gate::denies($gate)) {
            abort(403, 'You are not authorized to use Laravel WebShell.');
        }

        return $next($request);
    }
}
