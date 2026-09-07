<?php

namespace Imran\WebShell\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Imran\WebShell\Http\Controllers\Concerns\InteractsWithWebShell;
use Imran\WebShell\Http\Requests\RunCommandRequest;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TerminalController extends Controller
{
    use InteractsWithWebShell;

    public function execute(RunCommandRequest $request, ProcessManager $manager)
    {
        try {
            $result = $manager->run(CommandRequest::make(
                $request->string('command')->toString(),
                $request->context(),
            ));

            return $this->resultResponse($result);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function stream(RunCommandRequest $request, ProcessManager $manager): StreamedResponse
    {
        $command = CommandRequest::make(
            $request->string('command')->toString(),
            $request->context(),
        );

        return response()->stream(function () use ($manager, $command): void {
            echo "event: start\n";
            echo 'data: '.json_encode(['command' => $command->command])."\n\n";

            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            flush();

            $result = $manager->stream($command, function (string $stream, string $chunk): void {
                echo "event: chunk\n";
                echo 'data: '.json_encode(['stream' => $stream, 'chunk' => $chunk])."\n\n";

                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();
            });

            echo "event: done\n";
            echo 'data: '.json_encode($result->toArray())."\n\n";

            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function complete(Request $request)
    {
        $input = (string) $request->input('input', '');
        $cwd = (string) ($request->input('working_directory') ?: config('webshell.terminal.working_directory'));
        $suggestions = [];

        if (str_starts_with($input, 'php artisan') || str_starts_with($input, 'artisan')) {
            $partial = trim(preg_replace('/^(php\s+)?artisan\s*/i', '', $input) ?? '');

            foreach (array_keys(\Illuminate\Support\Facades\Artisan::all()) as $name) {
                if ($partial === '' || str_starts_with($name, $partial)) {
                    $suggestions[] = (str_starts_with($input, 'php ') ? 'php artisan ' : 'artisan ').$name;
                }
            }
        } else {
            $prefix = $input;
            $dir = is_dir($cwd) ? $cwd : base_path();

            foreach (array_merge(glob($dir.DIRECTORY_SEPARATOR.$prefix.'*') ?: [], glob($dir.DIRECTORY_SEPARATOR.$prefix.'*/') ?: []) as $match) {
                $suggestions[] = basename(rtrim($match, DIRECTORY_SEPARATOR));
            }
        }

        return response()->json([
            'suggestions' => array_values(array_unique(array_slice($suggestions, 0, 30))),
        ]);
    }
}
