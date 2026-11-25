<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use App\Support\Sanitizador;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de auditoria de requisições HTTP e propagação de contexto de sessão ao PostgreSQL.
 */
class AuditoriaRequisicao
{
    public function __construct(private AuditoriaService $auditoria) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id');

        if (! $requestId || ! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $requestId);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        try {
            $this->auditoria->registrar(
                $this->auditoria->categoriaDaRequisicao($request),
                'HTTP_'.$request->method(),
                [
                    'rota' => $request->path(),
                    'metodo' => $request->method(),
                    'status_http' => $response->getStatusCode(),
                    'dados' => Sanitizador::dadosDaRequisicao($request->input()),
                ],
                'http',
                null,
                $request->user()?->id,
                $request,
                $response->getStatusCode(),
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
