<?php

namespace Imran\WebShell\Services\Security;

use Imran\WebShell\Exceptions\CommandBlockedException;

class WorkingDirectoryGuard
{
    public function resolve(?string $requested): string
    {
        $fallback = (string) config('webshell.terminal.working_directory', base_path());
        $path = $this->canonicalize($requested ?: $fallback);

        if (! is_dir($path)) {
            throw new CommandBlockedException("Working directory does not exist: {$path}");
        }

        if (! config('webshell.terminal.restrict_working_directory', true)) {
            return $path;
        }

        $roots = array_map(fn ($root) => $this->canonicalize((string) $root), (array) config('webshell.terminal.allowed_roots', [base_path()]));

        foreach ($roots as $root) {
            if ($path === $root || str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
                return $path;
            }
        }

        throw new CommandBlockedException('Working directory is outside the allowed project roots.');
    }

    public function canonicalize(string $path): string
    {
        $real = realpath($path);

        if ($real !== false) {
            return $this->normalize($real);
        }

        return $this->normalize($path);
    }

    protected function normalize(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return rtrim($path, DIRECTORY_SEPARATOR);
    }
}
