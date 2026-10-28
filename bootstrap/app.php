<?php

use App\Http\Middleware\RequireModule;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias(['tenant' => SetTenant::class, 'module' => RequireModule::class]);
        $middleware->validateCsrfTokens(except: ['unsubscribe/*', 'feedback/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
