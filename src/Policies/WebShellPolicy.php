<?php

namespace Imran\WebShell\Policies;

class WebShellPolicy
{
    public function access(mixed $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'canAccessWebShell')) {
            return (bool) $user->canAccessWebShell();
        }

        if (isset($user->is_admin)) {
            return (bool) $user->is_admin;
        }

        return app()->environment('local');
    }
}
