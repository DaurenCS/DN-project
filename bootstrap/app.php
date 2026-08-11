<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->append(\App\Http\Middleware\SetLocale::class);
        $middleware->append(\App\Http\Middleware\EnsureUserHasRole::class);
        $middleware->trustProxies(at: '*');

    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'message' => 'Пользователь не авторизован.',
                    ], 401);
                }

                if ($e instanceof ModelNotFoundException) {
                    return response()->json(['message' => 'Ресурс не найден'], 404);
                }

                $statusCode = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : (($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500);

                return response()->json([
                    'message' => $e->getMessage() ?: 'Произошла ошибка сервера',
                ], $statusCode);
            }
        });
    })->create();
