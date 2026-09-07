<?php

namespace Imran\WebShell\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Imran\WebShell\Exceptions\CommandBlockedException;
use Imran\WebShell\Exceptions\WebShellException;
use Imran\WebShell\Support\CommandResult;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

trait InteractsWithWebShell
{
    /**
     * @return array<string, mixed>
     */
    protected function requestContext(Request $request, string $source): array
    {
        return [
            'working_directory' => $request->input('working_directory'),
            'timeout' => $request->input('timeout'),
            'background' => $request->boolean('background'),
            'confirmed' => $request->boolean('confirmed'),
            'source' => $source,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ];
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    protected function resultResponse(CommandResult $result)
    {
        $status = $result->requiresConfirmation ? 409 : 200;

        return response()->json($result->toArray(), $status);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    protected function exceptionResponse(Throwable $e)
    {
        $status = $e instanceof CommandBlockedException
            ? ($e->requiresConfirmation ? 409 : 403)
            : ($e instanceof WebShellException ? 422 : Response::HTTP_INTERNAL_SERVER_ERROR);

        return response()->json([
            'message' => $e->getMessage(),
            'requires_confirmation' => $e instanceof CommandBlockedException && $e->requiresConfirmation,
        ], $status);
    }
}
