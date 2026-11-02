<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de auditoria de requisições HTTP e propagação de contexto de sessão ao PostgreSQL.
 */
class AuditoriaRequisicao
{
    private array $ignorar = ['up', 'api/v1/health', 'api/v1/openapi.yaml', 'docs/*', 'storage/*'];

    public function __construct(private AuditoriaService $auditoria) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id');

        if (! $requestId || ! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $requestId);

        // Contexto PostgreSQL deve ser configurado na mesma transação da escrita.
        // Não definir set_config(..., true) aqui: fora de transação não persiste.

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        if (! $this->deveRegistrar($request)) {
            return $response;
        }

        try {
            $this->auditoria->registrar(
                $this->auditoria->categoriaDaRequisicao($request),
                'HTTP_'.$request->method(),
                [
                    'rota' => $request->path(),
                    'metodo' => $request->method(),
                    'status_http' => $response->getStatusCode(),
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

    private function deveRegistrar(Request $request): bool
    {
        if ($request->isMethod('OPTIONS')) {
            return false;
        }

        foreach ($this->ignorar as $padrao) {
            if ($request->is($padrao)) {
                return false;
            }
        }

        return true;
    }
}
