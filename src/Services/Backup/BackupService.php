<?php

namespace Imran\WebShell\Services\Backup;

use Illuminate\Support\Facades\File;
use Imran\WebShell\Exceptions\CommandBlockedException;
use ZipArchive;

class BackupService
{
    /**
     * @param  list<string>  $parts
     * @return array<string, mixed>
     */
    public function create(array $parts = ['database', 'storage']): array
    {
        if (config('webshell.security.read_only')) {
            throw new CommandBlockedException('Backups are disabled in read-only mode.');
        }

        $dir = $this->directory();
        File::ensureDirectoryExists($dir);

        $name = 'backup-'.now()->format('Y-m-d-His').'.zip';
        $path = $dir.DIRECTORY_SEPARATOR.$name;
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            throw new CommandBlockedException('Unable to create the backup archive. Enable the PHP zip extension.');
        }

        if (in_array('database', $parts, true)) {
            $this->addDatabase($zip);
        }

        if (in_array('storage', $parts, true)) {
            $this->addDirectory($zip, storage_path('app'), 'storage/app');
        }

        if (in_array('uploaded-files', $parts, true)) {
            $this->addDirectory($zip, storage_path('app/public'), 'storage/app/public');
        }

        $zip->addFromString('manifest.json', json_encode([
            'created_at' => now()->toIso8601String(),
            'parts' => $parts,
            'laravel' => app()->version(),
            'environment' => app()->environment(),
        ], JSON_PRETTY_PRINT));

        $zip->close();

        return [
            'name' => $name,
            'path' => $this->relative($path),
            'size' => File::size($path),
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $dir = $this->directory();

        if (! File::isDirectory($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.zip'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified' => date(DATE_ATOM, $file->getMTime()),
            ])
            ->values()
            ->all();
    }

    public function absolute(string $name): string
    {
        $path = $this->directory().DIRECTORY_SEPARATOR.basename($name);

        if (! File::exists($path)) {
            throw new CommandBlockedException('Backup was not found.');
        }

        return $path;
    }

    public function delete(string $name): void
    {
        if (config('webshell.security.read_only')) {
            throw new CommandBlockedException('Deleting backups is disabled in read-only mode.');
        }

        $path = $this->absolute($name);

        if (! File::delete($path)) {
            throw new CommandBlockedException("Failed to delete backup [{$name}].");
        }
    }

    protected function addDatabase(ZipArchive $zip): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            $database = config("database.connections.{$connection}.database");

            if (is_string($database) && File::exists($database)) {
                $zip->addFile($database, 'database/database.sqlite');
            }
        }

        $zip->addFromString('database/connection.json', json_encode([
            'connection' => $connection,
            'driver' => $driver,
            'database' => config("database.connections.{$connection}.database"),
        ], JSON_PRETTY_PRINT));
    }

    protected function addDirectory(ZipArchive $zip, string $root, string $prefix): void
    {
        if (! File::isDirectory($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = $prefix.'/'.ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $relative));
        }
    }

    protected function directory(): string
    {
        return rtrim((string) config('webshell.storage_path'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'backups';
    }

    protected function relative(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
    }
}
