<?php

namespace Imran\WebShell\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Imran\WebShell\Exceptions\CommandBlockedException;
use Throwable;

class DatabaseService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $connection = config('database.default');

        try {
            $pdo = DB::connection()->getPdo();
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $tables = $this->tables();

            return [
                'available' => true,
                'connection' => $connection,
                'driver' => $driver,
                'database' => DB::connection()->getDatabaseName(),
                'tables' => $tables,
                'mode' => config('webshell.database.mode', 'read-only'),
            ];
        } catch (Throwable $e) {
            return [
                'available' => false,
                'connection' => $connection,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(string $table): array
    {
        if (! Schema::hasTable($table)) {
            throw new CommandBlockedException("Table [{$table}] was not found.");
        }

        $columns = [];
        if (method_exists(Schema::class, 'getColumns')) {
            try {
                $columns = Schema::getColumns($table);
            } catch (Throwable) {
                $columns = [];
            }
        }

        if ($columns === []) {
            $columns = array_map(fn ($col) => [
                'name' => $col,
                'type_name' => 'unknown',
                'type' => 'unknown',
                'nullable' => true,
                'default' => null,
                'auto_increment' => false,
            ], Schema::getColumnListing($table));
        }

        return [
            'table' => $table,
            'columns' => $columns,
            'count' => DB::table($table)->count(),
        ];
    }

    /**
     * @return array{filename: string, content: string, mime: string}
     */
    public function export(string $table, string $format = 'json'): array
    {
        if (! Schema::hasTable($table)) {
            throw new CommandBlockedException("Table [{$table}] was not found.");
        }

        $limit = (int) config('webshell.database.export_limit', 5000);
        $rows = DB::table($table)->limit($limit)->get()->map(fn ($row) => (array) $row)->all();

        if (strtolower($format) === 'csv') {
            $handle = fopen('php://temp', 'r+');
            if ($rows !== []) {
                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, array_map(fn ($v) => is_scalar($v) || is_null($v) ? (string) $v : json_encode($v), $row));
                }
            }
            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);

            return [
                'filename' => "export_{$table}_".date('Ymd_His').'.csv',
                'content' => $content ?: '',
                'mime' => 'text/csv',
            ];
        }

        return [
            'filename' => "export_{$table}_".date('Ymd_His').'.json',
            'content' => json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            'mime' => 'application/json',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function preview(string $table): array
    {
        if (! Schema::hasTable($table)) {
            throw new CommandBlockedException("Table [{$table}] was not found.");
        }

        return DB::table($table)
            ->limit((int) config('webshell.database.max_rows', 200))
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function query(string $sql, bool $confirmed = false): array
    {
        if (! config('webshell.database.allow_raw_sql')) {
            throw new CommandBlockedException('Raw SQL is disabled. Set WEBSHELL_DB_SQL=true to enable the query runner.');
        }

        $sql = trim($sql);

        if ($sql === '' || str_contains($sql, "\0")) {
            throw new CommandBlockedException('SQL cannot be empty.');
        }

        if (substr_count(rtrim($sql, ';'), ';') > 0) {
            throw new CommandBlockedException('Only a single SQL statement is allowed.');
        }

        $mode = (string) config('webshell.database.mode', 'read-only');
        $write = (bool) preg_match('/\b(INSERT|UPDATE|DELETE|REPLACE|CREATE|ALTER|DROP|TRUNCATE|GRANT|REVOKE)\b/i', $sql);
        $destructive = (bool) preg_match('/\b(DROP|TRUNCATE|ALTER|GRANT|REVOKE)\b/i', $sql);

        if ($write && $mode === 'read-only') {
            throw new CommandBlockedException('Database tools are in read-only mode.');
        }

        if ($destructive && $mode !== 'administrator') {
            throw new CommandBlockedException('Destructive SQL requires administrator database mode.');
        }

        if ($write && ! $confirmed) {
            throw new CommandBlockedException('Write SQL requires explicit confirmation.', true);
        }

        $rows = DB::select($sql);

        return [
            'sql' => $sql,
            'rows' => collect($rows)->map(fn ($row) => (array) $row)->take((int) config('webshell.database.max_rows', 200))->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function tables(): array
    {
        $rows = [];

        foreach (Schema::getTableListing() as $table) {
            try {
                $rows[] = [
                    'name' => $table,
                    'rows' => DB::table($table)->count(),
                ];
            } catch (Throwable) {
                $rows[] = [
                    'name' => $table,
                    'rows' => null,
                ];
            }
        }

        return $rows;
    }
}
