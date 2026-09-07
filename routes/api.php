<?php

use Illuminate\Support\Facades\Route;
use Imran\WebShell\Http\Controllers\ArtisanController;
use Imran\WebShell\Http\Controllers\ModernController;
use Imran\WebShell\Http\Controllers\OperationsController;
use Imran\WebShell\Http\Controllers\ToolsController;
use Imran\WebShell\Http\Middleware\AuthenticateToken;

Route::middleware(AuthenticateToken::class.':webshell:health')->get('/health', [OperationsController::class, 'health']);
Route::middleware(AuthenticateToken::class.':webshell:artisan')->post('/artisan', [ArtisanController::class, 'run']);
Route::middleware(AuthenticateToken::class.':webshell:git')->post('/git', [ToolsController::class, 'gitRun']);
Route::middleware(AuthenticateToken::class.':webshell:deploy')->post('/deploy', [OperationsController::class, 'deploy']);
Route::middleware(AuthenticateToken::class.':webshell:logs')->get('/logs', [ToolsController::class, 'logs']);
