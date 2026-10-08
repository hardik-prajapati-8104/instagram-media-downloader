<?php

use App\Exceptions\MediaException;
use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin.auth' => AdminAuthenticate::class]);
        $middleware->appendToGroup('web', SecurityHeaders::class);
        $middleware->appendToGroup('api', SecurityHeaders::class);

        // Behind a load balancer / reverse proxy (needed for correct https + signed URLs):
        // $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (MediaException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => $e->userMessage(), 'error' => ['code' => $e->errorCode]], $e->statusCode());
            }
        });
    })->create();
