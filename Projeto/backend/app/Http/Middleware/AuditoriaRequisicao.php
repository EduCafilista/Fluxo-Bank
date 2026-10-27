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
 * RF-SEG01 + RF-SEG04: registra 100% das requisições da API e da web.
 * Grava IP, user-agent, rota, método, payload sanitizado, status, usuário,
 * data/hora e request_id — e classifica em ACESSO, CONSULTA ou MOVIMENTACAO.
 *
 * Também propaga o contexto ao PostgreSQL (SET LOCAL), para que os triggers
 * consigam gravar quem fez cada alteração.
 */
class AuditoriaRequisicao
{
    /** Rotas que não geram registro (evita ruído de healthcheck). */
    private array $ignorar = ['up', 'api/v1/health', 'storage/*'];

    public function __construct(private AuditoriaService $auditoria) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id');

        if (! $requestId || ! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $requestId);

        if ($request->hasSession()) {
            // nada: a sessão não é usada na API por token
        }

        // Contexto para os triggers de auditoria (vive só nesta requisição)
        try {
            DB::statement('SELECT set_config(?, ?, true)', ['fluxo.request_id', $requestId]);
            // O set_config não aceita NULL: usa string vazia quando não autenticado
            DB::statement('SELECT set_config(?, ?, true)', [
                'fluxo.usuario_id',
                (string) ($request->user()?->id ?? ''),
            ]);
        } catch (\Throwable $e) {
            // Banco indisponível no momento do log não deve derrubar a requisição
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
