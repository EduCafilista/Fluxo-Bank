<?php

namespace App\Services;

use App\Models\LogAuditoria;
use App\Support\Sanitizador;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditoriaService
{
    public function registrar(
        string $categoria,
        string $operacao,
        array $payload = [],
        ?string $tabela = null,
        ?string $registroId = null,
        ?int $usuarioId = null,
        ?Request $request = null,
        ?int $statusHttp = null,
    ): LogAuditoria {
        $request ??= app()->runningInConsole() ? null : request();

        return LogAuditoria::create([
            'tabela' => $tabela ?? 'http',
            'registro_id' => $registroId,
            'operacao' => $operacao,
            'categoria' => $categoria,
            'usuario_id' => $usuarioId ?? $request?->user()?->id ?? auth()->id(),
            'request_id' => $this->requestId($request),
            'rota' => $request?->path(),
            'metodo' => $request?->method(),
            'status_http' => $statusHttp,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
            'payload' => $payload ? Sanitizador::limpar($payload) : null,
            'registrado_em' => now(),
        ]);
    }

    public function categoriaDaRequisicao(Request $request): string
    {
        $rota = mb_strtolower($request->path());

        if (str_contains($rota, 'login') || str_contains($rota, 'logout')
            || str_contains($rota, '2fa') || str_contains($rota, 'cadastro')
            || str_contains($rota, 'senha')) {
            return LogAuditoria::CATEGORIA_ACESSO;
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return LogAuditoria::CATEGORIA_CONSULTA;
        }

        return LogAuditoria::CATEGORIA_MOVIMENTACAO;
    }

    public function requestId(?Request $request = null): ?string
    {
        $id = $request?->attributes->get('request_id')
            ?? $request?->header('X-Request-Id');

        return $id && Str::isUuid($id) ? $id : null;
    }
}
