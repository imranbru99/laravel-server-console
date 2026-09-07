<?php

namespace Imran\WebShell\Services\Queue;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Imran\WebShell\Support\CommandResult;
use Throwable;

class QueueService
{
    public function __construct(
        protected ProcessManager $processes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'connection' => config('queue.default'),
            'pending' => $this->countTable('jobs'),
            'failed' => $this->countTable('failed_jobs'),
        ];
    }

    public function artisan(string $command, array $context = []): CommandResult
    {
        return $this->processes->run(CommandRequest::make(
            'php artisan '.$command,
            array_merge($context, ['source' => 'queue']),
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function failedJobs(int $limit = 50): array
    {
        try {
            if (! Schema::hasTable('failed_jobs')) {
                return [];
            }

            return DB::table('failed_jobs')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected function countTable(string $table): int
    {
        try {
            return Schema::hasTable($table) ? DB::table($table)->count() : 0;
        } catch (Throwable) {
            return 0;
        }
    }
}
