<?php

namespace Imran\WebShell\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Imran\WebShell\Http\Controllers\Concerns\InteractsWithWebShell;
use Imran\WebShell\Services\Capability\CapabilityDetector;
use Imran\WebShell\Services\Deployment\DeploymentService;
use Imran\WebShell\Services\Health\HealthChecker;
use Imran\WebShell\Services\Process\ProcessManager;
use Imran\WebShell\Support\CommandRequest;
use Throwable;

class OperationsController extends Controller
{
    use InteractsWithWebShell;

    public function health(HealthChecker $health)
    {
        return response()->json($health->run());
    }

    public function doctor(HealthChecker $health)
    {
        return response()->json($health->doctor());
    }

    public function capabilities(CapabilityDetector $capabilities)
    {
        return response()->json($capabilities->detect());
    }

    public function deploy(Request $request, DeploymentService $deployment)
    {
        $data = $request->validate([
            'environment' => ['required', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:200'],
            'confirmed' => ['sometimes', 'boolean'],
            'steps' => ['nullable', 'array'],
        ]);

        try {
            return response()->json($deployment->deploy(
                $data['environment'],
                $data['branch'] ?? null,
                $request->boolean('confirmed'),
                $data['steps'] ?? null,
            ));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deployments(DeploymentService $deployment)
    {
        return response()->json([
            'config' => $deployment->config(),
            'history' => $deployment->history(),
        ]);
    }

    public function macro(Request $request, ProcessManager $manager)
    {
        $data = $request->validate([
            'macro' => ['required', 'string'],
        ]);

        $macros = (array) config('webshell.macros', []);
        $macro = $macros[$data['macro']] ?? null;

        if (! $macro) {
            return response()->json(['message' => 'Unknown macro.'], 404);
        }

        $results = [];

        try {
            foreach ($macro['commands'] as $command) {
                $result = $manager->run(CommandRequest::make(
                    $command,
                    $this->requestContext($request, 'macro'),
                ));
                $results[] = $result->toArray();

                if ($result->requiresConfirmation || $result->blocked || ! $result->successful()) {
                    break;
                }
            }
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }

        return response()->json([
            'macro' => $data['macro'],
            'results' => $results,
        ]);
    }

    public function processes(ProcessManager $manager)
    {
        return response()->json([
            'processes' => $manager->processes(),
        ]);
    }
}
