<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use App\Support\Sanitizador;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        try {
            DB::statement('SELECT set_config(?, ?, true)', ['fluxo.request_id', $requestId]);
            DB::statement('SELECT set_config(?, ?, true)', [
                'fluxo.usuario_id',
                (string) ($request->user()?->id ?? ''),
            ]);
        } catch (\Throwable $e) {
            // Ignora falha de conexão ao propagar variáveis de sessão.
        }

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
                    'query' => Sanitizador::limpar($request->query()),
                    'body' => Sanitizador::limpar($request->all()),
                    'cabecalhos' => Sanitizador::cabecalhos($request->headers->all()),
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
