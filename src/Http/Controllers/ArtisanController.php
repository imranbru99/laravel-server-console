<?php

namespace Imran\WebShell\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Imran\WebShell\Http\Controllers\Concerns\InteractsWithWebShell;
use Imran\WebShell\Services\Artisan\ArtisanService;
use Throwable;

class ArtisanController extends Controller
{
    use InteractsWithWebShell;

    public function catalog(ArtisanService $artisan)
    {
        return response()->json([
            'groups' => $artisan->catalog(),
        ]);
    }

    public function definition(string $command, ArtisanService $artisan)
    {
        return response()->json($artisan->definition($command));
    }

    public function run(Request $request, ArtisanService $artisan)
    {
        $request->validate([
            'command' => ['required', 'string', 'max:200'],
            'parameters' => ['nullable', 'array'],
        ]);

        try {
            return $this->resultResponse($artisan->run(
                $request->string('command')->toString(),
                (array) $request->input('parameters', []),
                $this->requestContext($request, 'artisan'),
            ));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }
}
