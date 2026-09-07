<?php

namespace Imran\WebShell\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Imran\WebShell\Http\Controllers\Concerns\InteractsWithWebShell;
use Imran\WebShell\Services\Composer\ComposerService;
use Imran\WebShell\Services\Database\DatabaseService;
use Imran\WebShell\Services\Filesystem\FileManager;
use Imran\WebShell\Services\Git\GitService;
use Imran\WebShell\Services\Logs\LogViewer;
use Imran\WebShell\Services\Node\NodeService;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Services\Queue\QueueService;
use Imran\WebShell\Support\CommandRequest;
use Throwable;

class ToolsController extends Controller
{
    use InteractsWithWebShell;

    public function git(GitService $git)
    {
        return response()->json($git->overview());
    }

    public function gitRun(Request $request, GitService $git)
    {
        $request->validate(['arguments' => ['required', 'string', 'max:500']]);

        try {
            return $this->resultResponse($git->run(
                $request->string('arguments')->toString(),
                $this->requestContext($request, 'git'),
            ));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function composer(ComposerService $composer)
    {
        return response()->json($composer->overview());
    }

    public function composerRun(Request $request, ComposerService $composer)
    {
        $request->validate(['arguments' => ['required', 'string', 'max:500']]);

        try {
            return $this->resultResponse($composer->run(
                $request->string('arguments')->toString(),
                $this->requestContext($request, 'composer'),
            ));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function node(NodeService $node)
    {
        return response()->json($node->overview());
    }

    public function nodeRun(Request $request, NodeService $node)
    {
        $request->validate([
            'arguments' => ['required', 'string', 'max:500'],
            'manager' => ['nullable', 'string', 'in:npm,bun,pnpm,yarn'],
        ]);

        try {
            return $this->resultResponse($node->run(
                $request->string('arguments')->toString(),
                $this->requestContext($request, 'node'),
                $request->input('manager'),
            ));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function files(Request $request, FileManager $files)
    {
        return response()->json([
            'path' => $request->input('path', '.'),
            'entries' => $files->list($request->input('path')),
        ]);
    }

    public function fileShow(Request $request, FileManager $files)
    {
        try {
            return response()->json($files->read((string) $request->input('path')));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function fileSave(Request $request, FileManager $files)
    {
        $request->validate([
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
        ]);

        try {
            $files->write((string) $request->input('path'), (string) $request->input('content'));

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function fileDirectory(Request $request, FileManager $files)
    {
        $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        try {
            $files->createDirectory((string) $request->input('path'));

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function fileDelete(Request $request, FileManager $files)
    {
        $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        try {
            $files->delete((string) $request->input('path'));

            return response()->json(['ok' => true]);
        } catch (Throwable) {
            return $this->exceptionResponse(new \Exception("Unable to delete item or item not found."));
        }
    }

    public function fileRename(Request $request, FileManager $files)
    {
        $request->validate([
            'from' => ['required', 'string', 'max:500'],
            'to' => ['required', 'string', 'max:500'],
        ]);

        try {
            $files->rename((string) $request->input('from'), (string) $request->input('to'));

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function logs(Request $request, LogViewer $logs)
    {
        return response()->json([
            'files' => $logs->files(),
            'log' => $logs->tail($request->input('file')),
        ]);
    }

    public function logsClear(Request $request, LogViewer $logs)
    {
        try {
            $logs->clear($request->input('file'));

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function database(DatabaseService $database)
    {
        return response()->json($database->overview());
    }

    public function databaseTable(string $table, DatabaseService $database)
    {
        try {
            return response()->json([
                'schema' => $database->schema($table),
                'rows' => $database->preview($table),
            ]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function queue(QueueService $queue)
    {
        return response()->json($queue->overview());
    }

    public function queueRun(Request $request, QueueService $queue)
    {
        $request->validate(['command' => ['required', 'string', 'max:200']]);

        try {
            return $this->resultResponse($queue->artisan(
                $request->string('command')->toString(),
                $this->requestContext($request, 'queue'),
            ));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function queueFailed(Request $request, QueueService $queue)
    {
        $limit = min(100, max(1, (int) $request->query('limit', 50)));

        return response()->json([
            'failed' => $queue->failedJobs($limit),
        ]);
    }

    public function scheduler(ProcessManager $manager, Request $request)
    {
        try {
            return $this->resultResponse($manager->run(CommandRequest::make(
                'php artisan schedule:list',
                $this->requestContext($request, 'scheduler'),
            )));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}
