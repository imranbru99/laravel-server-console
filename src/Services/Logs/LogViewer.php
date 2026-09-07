<?php

namespace Imran\WebShell\Services\Logs;

use Illuminate\Support\Facades\File;
use Imran\WebShell\Exceptions\CommandBlockedException;

class LogViewer
{
    /**
     * @return array<string, mixed>
     */
    public function tail(?string $file = null, int $lines = 0): array
    {
        $path = $this->resolve($file);
        $limit = $lines ?: (int) config('webshell.logs.tail_lines', 400);

        if (! File::exists($path)) {
            return [
                'file' => $this->relative($path),
                'exists' => false,
                'entries' => [],
                'raw' => '',
            ];
        }

        $raw = $this->readTail($path, $limit);
        $entries = $this->parse($raw);

        return [
            'file' => $this->relative($path),
            'exists' => true,
            'size' => File::size($path),
            'modified' => date(DATE_ATOM, File::lastModified($path)),
            'entries' => $entries,
            'raw' => $raw,
        ];
    }

    /**
     * @return list<string>
     */
    public function files(): array
    {
        $directory = (string) config('webshell.logs.sources.laravel', storage_path('logs'));

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->map(fn ($file) => $file->getFilename())
            ->values()
            ->all();
    }

    public function clear(?string $file = null): void
    {
        if (config('webshell.security.read_only')) {
            throw new CommandBlockedException('Clearing logs is disabled in read-only mode.');
        }

        File::put($this->resolve($file), '');
    }

    protected function resolve(?string $file): string
    {
        if (! $file) {
            return (string) config('webshell.logs.default', storage_path('logs/laravel.log'));
        }

        $base = (string) config('webshell.logs.sources.laravel', storage_path('logs'));
        $path = str_contains($file, DIRECTORY_SEPARATOR) || str_contains($file, '/')
            ? $file
            : $base.DIRECTORY_SEPARATOR.basename($file);

        $real = realpath($path) ?: $path;
        $allowed = realpath($base) ?: $base;

        if (! str_starts_with($real, $allowed)) {
            throw new CommandBlockedException('Log file is outside the allowed log directory.');
        }

        return $real;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function parse(string $raw): array
    {
        $entries = [];
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[^\]]+)\]\s+(\w+)\.(\w+):\s+(.*)$/m';

        if (preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $entries[] = [
                    'timestamp' => $match[1],
                    'env' => $match[2],
                    'level' => strtoupper($match[3]),
                    'message' => $match[4],
                ];
            }
        }

        return array_slice($entries, -100);
    }

    protected function readTail(string $path, int $lines): string
    {
        $max = (int) config('webshell.logs.max_bytes', 256 * 1024);
        $size = File::size($path);
        $start = max(0, $size - $max);
        $fh = fopen($path, 'rb');

        if ($fh === false) {
            return '';
        }

        fseek($fh, $start);
        $chunk = stream_get_contents($fh) ?: '';
        fclose($fh);

        $parts = preg_split("/\r\n|\n|\r/", $chunk) ?: [];

        return implode("\n", array_slice($parts, -$lines));
    }

    protected function relative(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
    }
}
