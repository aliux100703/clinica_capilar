<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestsDuringMaintenance(except: ['up']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'name', 'email', 'phone', 'message', 'internal_notes']);
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
        // QueryException messages can contain bindings with personal information.
        $exceptions->report(function (Throwable $e) {
            Log::error('Application exception', ['type' => $e::class]);

            return false;
        });
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $status = $e instanceof ValidationException ? 422
                : ($e instanceof HttpExceptionInterface ? $e->getStatusCode()
                : ($e instanceof ModelNotFoundException ? 404 : 500));
            $message = match ($status) {
                422 => 'Los datos enviados no son válidos.',
                404 => 'Recurso no encontrado.',
                429 => 'Demasiadas solicitudes. Inténtalo más tarde.',
                default => $status >= 500 ? 'No se pudo procesar la solicitud.' : 'Solicitud no permitida.',
            };
            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            return response()->json(['data' => null, 'message' => $message, 'errors' => $e instanceof ValidationException ? $e->errors() : (object) []], $status, $headers);
        });
    })->create();
