<?php

namespace Imran\WebShell\Services\Security;

class RoleService
{
    public function role(mixed $user): string
    {
        if ($user === null) {
            return (string) config('webshell.rbac.guest_role', 'viewer');
        }

        if (method_exists($user, 'webshellRole')) {
            return (string) $user->webshellRole();
        }

        if (isset($user->webshell_role) && is_string($user->webshell_role) && $user->webshell_role !== '') {
            return $user->webshell_role;
        }

        if (isset($user->is_admin) && $user->is_admin) {
            return 'administrator';
        }

        return (string) config('webshell.rbac.default_role', 'developer');
    }

    /**
     * @return list<string>
     */
    public function abilities(mixed $user): array
    {
        $role = $this->role($user);
        $roles = (array) config('webshell.rbac.roles', []);

        return array_values((array) ($roles[$role] ?? $roles['viewer'] ?? []));
    }

    public function can(mixed $user, string $ability): bool
    {
        $abilities = $this->abilities($user);

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(mixed $user): array
    {
        return [
            'role' => $this->role($user),
            'abilities' => $this->abilities($user),
            'roles' => array_keys((array) config('webshell.rbac.roles', [])),
        ];
    }
}
