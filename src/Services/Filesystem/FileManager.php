<?php

namespace Imran\WebShell\Services\Filesystem;

use Illuminate\Support\Facades\File;
use Imran\WebShell\Exceptions\CommandBlockedException;
use Imran\WebShell\Services\Security\WorkingDirectoryGuard;
use SplFileInfo;

class FileManager
{
    public function __construct(
        protected WorkingDirectoryGuard $directories,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function list(?string $path = null): array
    {
        $root = $this->resolveDirectory($path ?: (string) config('webshell.files.root', base_path()));
        $hidden = (array) config('webshell.files.hidden', []);

        $directories = collect(File::directories($root))->map(fn (string $dir) => $this->entry($dir, true));
        $files = collect(File::files($root))->map(fn (SplFileInfo $file) => $this->entry($file->getPathname(), false));

        return $directories
            ->concat($files)
            ->reject(function (array $entry) use ($hidden) {
                foreach ($hidden as $pattern) {
                    if (str_contains($entry['relative'], (string) $pattern) || $entry['name'] === $pattern) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $path): array
    {
        $resolved = $this->resolveExisting($path);
        $this->assertNotProtected($resolved);

        if (is_dir($resolved)) {
            return [
                'path' => $this->relative($resolved),
                'type' => 'directory',
                'entries' => $this->list($resolved),
            ];
        }

        $size = filesize($resolved) ?: 0;
        $max = (int) config('webshell.files.max_edit_bytes', 512 * 1024);

        if ($size > $max) {
            throw new CommandBlockedException('File is too large to open in the editor.');
        }

        return [
            'path' => $this->relative($resolved),
            'type' => 'file',
            'size' => $size,
            'modified' => date(DATE_ATOM, File::lastModified($resolved)),
            'content' => File::get($resolved),
        ];
    }

    public function write(string $path, string $content): void
    {
        $this->assertWritable();
        $resolved = $this->resolveWritableTarget($path);
        $this->assertNotProtected($resolved);
        File::ensureDirectoryExists(dirname($resolved));
        File::put($resolved, $content);
    }

    public function createDirectory(string $path): void
    {
        $this->assertWritable();
        File::ensureDirectoryExists($this->resolveWritableTarget($path));
    }

    public function delete(string $path): void
    {
        $this->assertWritable();
        $resolved = $this->resolveExisting($path);
        $this->assertNotProtected($resolved);

        if (is_dir($resolved)) {
            File::deleteDirectory($resolved);

            return;
        }

        File::delete($resolved);
    }

    public function rename(string $from, string $to): void
    {
        $this->assertWritable();
        $source = $this->resolveExisting($from);
        $this->assertNotProtected($source);
        File::move($source, $this->resolveWritableTarget($to));
    }

    public function download(string $path): string
    {
        $resolved = $this->resolveExisting($path);
        $this->assertNotProtected($resolved);

        if (is_dir($resolved)) {
            throw new CommandBlockedException('Directories cannot be downloaded from the file manager.');
        }

        return $resolved;
    }

    public function upload(string $directory, string $name, string $contents): string
    {
        $this->assertWritable();

        $safe = basename($name);
        $target = $this->resolveWritableTarget(trim($directory, '/\\').DIRECTORY_SEPARATOR.$safe);
        $this->assertNotProtected($target);

        $max = (int) config('webshell.files.max_upload_bytes', 8 * 1024 * 1024);

        if (strlen($contents) > $max) {
            throw new CommandBlockedException('Uploaded file exceeds the configured size limit.');
        }

        File::ensureDirectoryExists(dirname($target));
        File::put($target, $contents);

        return $this->relative($target);
    }

    protected function resolveDirectory(?string $path): string
    {
        $absolute = $this->absolute($path ?: base_path());

        return $this->directories->resolve($absolute);
    }

    protected function resolveExisting(string $path): string
    {
        $absolute = $this->absolute($path);
        $parent = $this->directories->resolve(is_dir($absolute) ? $absolute : dirname($absolute));

        return is_dir($absolute) ? $parent : $parent.DIRECTORY_SEPARATOR.basename($absolute);
    }

    protected function resolveWritableTarget(string $path): string
    {
        $absolute = $this->absolute($path);
        $parent = $this->directories->resolve(dirname($absolute));

        return $parent.DIRECTORY_SEPARATOR.basename($absolute);
    }

    protected function absolute(string $path): string
    {
        if ($this->isAbsolute($path)) {
            return $path;
        }

        return base_path($path);
    }

    protected function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function entry(string $path, bool $directory): array
    {
        return [
            'name' => basename($path),
            'path' => $this->relative($path),
            'absolute' => $path,
            'relative' => $this->relative($path),
            'type' => $directory ? 'directory' : 'file',
            'size' => $directory ? null : File::size($path),
            'modified' => date(DATE_ATOM, File::lastModified($path)),
            'protected' => $this->isProtected($path),
        ];
    }

    protected function relative(string $path): string
    {
        $root = $this->directories->canonicalize(base_path());
        $normalized = $this->directories->canonicalize($path);

        if ($normalized === $root) {
            return '.';
        }

        return ltrim(str_replace($root, '', $normalized), DIRECTORY_SEPARATOR);
    }

    protected function assertNotProtected(string $path): void
    {
        if ($this->isProtected($path) && config('webshell.security.protect_env_files', true)) {
            throw new CommandBlockedException('This file is protected and cannot be opened or modified by WebShell.');
        }
    }

    protected function isProtected(string $path): bool
    {
        $name = basename($path);

        foreach ((array) config('webshell.protected_files', []) as $protected) {
            if (strcasecmp($name, (string) $protected) === 0 || str_ends_with(str_replace('\\', '/', $path), (string) $protected)) {
                return true;
            }
        }

        return false;
    }

    protected function assertWritable(): void
    {
        if (config('webshell.security.read_only')) {
            throw new CommandBlockedException('File writes are disabled in read-only mode.');
        }
    }
}
