<?php

use App\Exceptions\RegraDeNegocioException;
use App\Http\Middleware\ApenasBackoffice;
use App\Http\Middleware\ApenasDoisFatoresConcluido;
use App\Http\Middleware\AuditoriaRequisicao;
use App\Http\Middleware\Idempotencia;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->throttleApi('api');
        $middleware->append(AuditoriaRequisicao::class);

        $middleware->alias([
            'backoffice' => ApenasBackoffice::class,
            '2fa.concluido' => ApenasDoisFatoresConcluido::class,
            'idempotencia' => Idempotencia::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (RegraDeNegocioException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['erro' => $e->getMessage()], $e->statusHttp());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['erro' => 'Não autenticado. Envie o token Bearer.'], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'erro' => collect($e->errors())->flatten()->first() ?? 'Dados inválidos.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();
