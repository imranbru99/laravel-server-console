<?php

namespace Imran\WebShell\Support;

use Illuminate\Support\Facades\File;

class JsonFileStore
{
    public function __construct(protected string $path) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        if (! File::exists($this->path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($this->path), true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     */
    public function write(array $records): void
    {
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public function prepend(array $record, int $limit = 500): void
    {
        $records = $this->all();
        array_unshift($records, $record);
        $this->write(array_slice($records, 0, $limit));
    }
}
