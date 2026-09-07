<?php

use Illuminate\Support\Facades\Route;
use Imran\WebShell\Http\Controllers\ModernController;

Route::get('/healthz', [ModernController::class, 'healthz'])->name('webshell.healthz');
Route::get('/manifest.webmanifest', [ModernController::class, 'manifest'])->name('webshell.manifest');
Route::get('/sw.js', [ModernController::class, 'serviceWorker'])->name('webshell.sw');
Route::post('/webhooks/{provider}', [ModernController::class, 'webhook'])->name('webshell.webhook');
