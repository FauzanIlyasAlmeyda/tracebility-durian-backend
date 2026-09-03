<?php

use App\Support\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', 
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::validation($exception->errors(), 'Data tidak valid');
        });

        $exceptions->render(function (AuthenticationException $exception, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error('Unauthenticated', 401);
        });

        $exceptions->render(function (AuthorizationException $exception, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error($exception->getMessage() ?: 'Forbidden', 403);
        });

        $exceptions->render(function (ModelNotFoundException $exception, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error('Data tidak ditemukan', 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error('Endpoint tidak ditemukan', 404);
        });
    })
    ->create();
