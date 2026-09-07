<?php

namespace Imran\WebShell\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Imran\WebShell\Contracts\AuditLogger;
use Imran\WebShell\Contracts\HistoryStore;

class HistoryController extends Controller
{
    public function index(Request $request, HistoryStore $history)
    {
        return response()->json([
            'records' => $history->latest(
                (int) $request->integer('limit', 100),
                $request->input('search'),
            ),
        ]);
    }

    public function favorite(Request $request, string $id, HistoryStore $history)
    {
        $history->favorite($id, $request->boolean('favorite', true));

        return response()->json(['ok' => true]);
    }

    public function destroy(string $id, HistoryStore $history)
    {
        $history->delete($id);

        return response()->json(['ok' => true]);
    }

    public function clear(HistoryStore $history)
    {
        $history->clear();

        return response()->json(['ok' => true]);
    }

    public function audit(AuditLogger $audit)
    {
        return response()->json([
            'records' => $audit->latest(100),
        ]);
    }
}
