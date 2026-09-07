<?php

use Illuminate\Support\Facades\Route;
use Imran\WebShell\Http\Controllers\AppController;
use Imran\WebShell\Http\Controllers\ArtisanController;
use Imran\WebShell\Http\Controllers\HistoryController;
use Imran\WebShell\Http\Controllers\OperationsController;
use Imran\WebShell\Http\Controllers\ModernController;
use Imran\WebShell\Http\Controllers\TerminalController;
use Imran\WebShell\Http\Controllers\ToolsController;

Route::get('/', [AppController::class, 'index'])->name('webshell.index');

Route::prefix('api')->group(function (): void {
    Route::get('/bootstrap', [AppController::class, 'bootstrap'])->name('webshell.bootstrap');

    Route::post('/terminal', [TerminalController::class, 'execute'])->name('webshell.terminal.execute');
    Route::post('/terminal/stream', [TerminalController::class, 'stream'])->name('webshell.terminal.stream');
    Route::post('/terminal/complete', [TerminalController::class, 'complete'])->name('webshell.terminal.complete');

    Route::get('/artisan', [ArtisanController::class, 'catalog'])->name('webshell.artisan.catalog');
    Route::get('/artisan/{command}', [ArtisanController::class, 'definition'])
        ->where('command', '.*')
        ->name('webshell.artisan.definition');
    Route::post('/artisan', [ArtisanController::class, 'run'])->name('webshell.artisan.run');

    Route::get('/history', [HistoryController::class, 'index'])->name('webshell.history');
    Route::post('/history/{id}/favorite', [HistoryController::class, 'favorite'])->name('webshell.history.favorite');
    Route::delete('/history/{id}', [HistoryController::class, 'destroy'])->name('webshell.history.delete');
    Route::delete('/history', [HistoryController::class, 'clear'])->name('webshell.history.clear');
    Route::get('/audit', [HistoryController::class, 'audit'])->name('webshell.audit');

    Route::get('/git', [ToolsController::class, 'git'])->name('webshell.git');
    Route::post('/git', [ToolsController::class, 'gitRun'])->name('webshell.git.run');
    Route::get('/composer', [ToolsController::class, 'composer'])->name('webshell.composer');
    Route::post('/composer', [ToolsController::class, 'composerRun'])->name('webshell.composer.run');
    Route::get('/node', [ToolsController::class, 'node'])->name('webshell.node');
    Route::post('/node', [ToolsController::class, 'nodeRun'])->name('webshell.node.run');

    Route::get('/files', [ToolsController::class, 'files'])->name('webshell.files');
    Route::get('/files/show', [ToolsController::class, 'fileShow'])->name('webshell.files.show');
    Route::post('/files', [ToolsController::class, 'fileSave'])->name('webshell.files.save');
    Route::post('/files/directory', [ToolsController::class, 'fileDirectory'])->name('webshell.files.directory');
    Route::delete('/files', [ToolsController::class, 'fileDelete'])->name('webshell.files.delete');
    Route::post('/files/rename', [ToolsController::class, 'fileRename'])->name('webshell.files.rename');

    Route::get('/logs', [ToolsController::class, 'logs'])->name('webshell.logs');
    Route::post('/logs/clear', [ToolsController::class, 'logsClear'])->name('webshell.logs.clear');

    Route::get('/database', [ToolsController::class, 'database'])->name('webshell.database');
    Route::get('/database/{table}', [ToolsController::class, 'databaseTable'])->name('webshell.database.table');
    Route::get('/database/{table}/export', [ModernController::class, 'databaseExport'])->name('webshell.database.export');

    Route::get('/queue', [ToolsController::class, 'queue'])->name('webshell.queue');
    Route::post('/queue', [ToolsController::class, 'queueRun'])->name('webshell.queue.run');
    Route::get('/queue/failed', [ToolsController::class, 'queueFailed'])->name('webshell.queue.failed');
    Route::get('/scheduler', [ToolsController::class, 'scheduler'])->name('webshell.scheduler');

    Route::get('/health', [OperationsController::class, 'health'])->name('webshell.health');
    Route::get('/doctor', [OperationsController::class, 'doctor'])->name('webshell.doctor');
    Route::get('/capabilities', [OperationsController::class, 'capabilities'])->name('webshell.capabilities');
    Route::get('/deployments', [OperationsController::class, 'deployments'])->name('webshell.deployments');
    Route::post('/deploy', [OperationsController::class, 'deploy'])->name('webshell.deploy');
    Route::post('/macros', [OperationsController::class, 'macro'])->name('webshell.macros');
    Route::get('/processes', [OperationsController::class, 'processes'])->name('webshell.processes');

    Route::get('/backups', [ModernController::class, 'backups'])->name('webshell.backups');
    Route::post('/backups', [ModernController::class, 'backupCreate'])->name('webshell.backups.create');
    Route::get('/backups/{name}', [ModernController::class, 'backupDownload'])->name('webshell.backups.download');
    Route::delete('/backups/{name}', [ModernController::class, 'backupDelete'])->name('webshell.backups.delete');

    Route::get('/approvals', [ModernController::class, 'approvals'])->name('webshell.approvals');
    Route::post('/approvals/{id}/approve', [ModernController::class, 'approve'])->name('webshell.approvals.approve');
    Route::post('/approvals/{id}/reject', [ModernController::class, 'reject'])->name('webshell.approvals.reject');

    Route::post('/database/query', [ModernController::class, 'sql'])->name('webshell.database.query');
    Route::get('/files/download', [ModernController::class, 'fileDownload'])->name('webshell.files.download');
    Route::post('/files/upload', [ModernController::class, 'fileUpload'])->name('webshell.files.upload');
    Route::get('/audit/export', [ModernController::class, 'auditExport'])->name('webshell.audit.export');
});
