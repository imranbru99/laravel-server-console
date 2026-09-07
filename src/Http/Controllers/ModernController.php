<?php

namespace Imran\WebShell\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Imran\WebShell\Contracts\AuditLogger;
use Imran\WebShell\Http\Controllers\Concerns\InteractsWithWebShell;
use Imran\WebShell\Services\Approvals\ApprovalService;
use Imran\WebShell\Services\Backup\BackupService;
use Imran\WebShell\Services\Database\DatabaseService;
use Imran\WebShell\Services\Filesystem\FileManager;
use Imran\WebShell\Services\Webhooks\WebhookService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ModernController extends Controller
{
    use InteractsWithWebShell;

    public function backups(BackupService $backups)
    {
        return response()->json(['backups' => $backups->list()]);
    }

    public function backupCreate(Request $request, BackupService $backups)
    {
        $data = $request->validate([
            'parts' => ['nullable', 'array'],
            'parts.*' => ['string', 'in:database,storage,uploaded-files'],
        ]);

        try {
            return response()->json($backups->create($data['parts'] ?? ['database', 'storage']));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function backupDownload(string $name, BackupService $backups)
    {
        return response()->download($backups->absolute($name));
    }

    public function backupDelete(string $name, BackupService $backups)
    {
        try {
            $backups->delete($name);

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function approvals(ApprovalService $approvals)
    {
        return response()->json([
            'pending' => $approvals->pending(),
            'records' => $approvals->all(),
        ]);
    }

    public function approve(string $id, Request $request, ApprovalService $approvals)
    {
        try {
            return $this->resultResponse($approvals->approve($id, $request->user()));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function reject(string $id, Request $request, ApprovalService $approvals)
    {
        try {
            $approvals->reject($id, $request->user());

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function sql(Request $request, DatabaseService $database)
    {
        $data = $request->validate([
            'sql' => ['required', 'string', 'max:4000'],
            'confirmed' => ['sometimes', 'boolean'],
        ]);

        try {
            return response()->json($database->query($data['sql'], $request->boolean('confirmed')));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function databaseExport(string $table, Request $request, DatabaseService $database)
    {
        try {
            $format = $request->query('format', 'json') === 'csv' ? 'csv' : 'json';
            $exported = $database->export($table, $format);

            return response($exported['content'], 200, [
                'Content-Type' => $exported['mime'],
                'Content-Disposition' => 'attachment; filename="'.$exported['filename'].'"',
            ]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function fileDownload(Request $request, FileManager $files)
    {
        try {
            $path = $files->download((string) $request->query('path'));

            return response()->download($path);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function fileUpload(Request $request, FileManager $files)
    {
        $request->validate([
            'directory' => ['nullable', 'string'],
            'file' => ['required', 'file'],
        ]);

        try {
            $uploaded = $request->file('file');
            $path = $files->upload(
                (string) $request->input('directory', '.'),
                (string) $uploaded?->getClientOriginalName(),
                (string) $uploaded?->get(),
            );

            return response()->json(['path' => $path]);
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function auditExport(Request $request, AuditLogger $audit)
    {
        $format = $request->query('format', 'json') === 'csv' ? 'csv' : 'json';
        $body = $audit->export($format);

        return response($body, 200, [
            'Content-Type' => $format === 'csv' ? 'text/csv' : 'application/json',
            'Content-Disposition' => 'attachment; filename="webshell-audit.'.$format.'"',
        ]);
    }

    public function webhook(string $provider, Request $request, WebhookService $webhooks)
    {
        $signature = (string) ($request->header('X-Hub-Signature-256')
            ?: $request->header('X-Gitlab-Token')
            ?: $request->header('X-Hook-Signature'));

        try {
            return response()->json($webhooks->handle(
                $provider,
                $signature,
                $request->getContent(),
                $request->all(),
            ));
        } catch (Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function healthz()
    {
        return response()->json([
            'status' => 'ok',
            'time' => now()->toIso8601String(),
        ]);
    }

    public function manifest()
    {
        return response()->json([
            'name' => config('webshell.name'),
            'short_name' => 'WebShell',
            'start_url' => '/'.trim((string) config('webshell.path'), '/'),
            'display' => 'standalone',
            'background_color' => '#10140f',
            'theme_color' => '#c6f54a',
            'lang' => config('webshell.ui.locale', 'en'),
        ]);
    }

    public function serviceWorker(): StreamedResponse
    {
        return response()->stream(function (): void {
            echo "self.addEventListener('install', (event) => event.waitUntil(self.skipWaiting()));\n";
            echo "self.addEventListener('fetch', (event) => { if (event.request.method === 'GET' && event.request.mode === 'navigate') { event.respondWith(fetch(event.request).catch(() => caches.match(event.request))); } });\n";
        }, 200, ['Content-Type' => 'application/javascript']);
    }
}
