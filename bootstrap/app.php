<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * API error convention: every JSON error under api/* carries a stable,
         * machine-readable "code". Exceptions that already render their own
         * "code" (e.g. domain conflicts) keep it; otherwise it is derived
         * from the exception type or the HTTP status text (404 → not_found).
         */
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            if (! $request->is('api/*') || ! $response instanceof JsonResponse || $response->getStatusCode() < 400) {
                return $response;
            }

            $body = $response->getData(true);

            if (! is_array($body) || isset($body['code'])) {
                return $response;
            }

            $code = match (true) {
                $e instanceof ValidationException => 'validation_failed',
                $e instanceof AuthenticationException => 'unauthenticated',
                default => Str::slug(Response::$statusTexts[$response->getStatusCode()] ?? 'error', '_'),
            };

            return $response->setData([
                'message' => $body['message'] ?? '',
                'code' => $code,
            ] + $body);
        });
    })->create();
