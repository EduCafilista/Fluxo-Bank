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
        // API sem tela de login: visitante sem token recebe 401 em JSON
        $middleware->redirectGuestsTo(fn () => null);

        // RNF12: 60 requisições por minuto por usuário/IP na API
        $middleware->throttleApi('api');

        // RF-SEG01/RF-SEG04: auditoria global (API + web)
        $middleware->append(AuditoriaRequisicao::class);

        $middleware->alias([
            'backoffice' => ApenasBackoffice::class,
            '2fa.concluido' => ApenasDoisFatoresConcluido::class,
            'idempotencia' => Idempotencia::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sempre JSON na API
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Exceções de domínio → 422/403/404 com a chave "erro" (padrão do Fluxo)
        $exceptions->render(function (RegraDeNegocioException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['erro' => $e->getMessage()], $e->statusHttp());
            }
        });

        // Token ausente/inválido → 401 em JSON (nunca HTML)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['erro' => 'Não autenticado. Envie o token Bearer.'], 401);
            }
        });

        // Validação → 422 com "erro" e "errors" (RNF23: mensagens em português)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'erro' => collect($e->errors())->flatten()->first() ?? 'Dados inválidos.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();
