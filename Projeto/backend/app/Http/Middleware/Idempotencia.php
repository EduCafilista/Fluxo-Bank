<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class Idempotencia
{
    public function handle(Request $request, Closure $next): Response
    {
        $chave = $request->header('Idempotency-Key');

        if (! $chave || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        if (strlen($chave) > 200) {
            return response()->json(['erro' => 'Chave de idempotência muito longa.'], 422);
        }

        $cacheKey = 'idempotencia:'.hash('sha256', $request->user()->getAuthIdentifier().'|'.$request->method().'|'.$request->path().'|'.$chave);
        $fingerprint = hash('sha256', $request->getContent());

        // Cache compartilhado entre instâncias é necessário em produção.
        $lock = Cache::lock($cacheKey.':lock', 120);

        try {
            $lock->block(10);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return response()->json(['erro' => 'Operação em andamento. Tente novamente.'], 409);
        }

        try {
            $salvo = Cache::get($cacheKey);

            if ($salvo !== null) {
                if (! hash_equals($salvo['fingerprint'], $fingerprint)) {
                    return response()->json(['erro' => 'Chave de idempotência reutilizada com dados diferentes.'], 409);
                }

                return response()->json($salvo['corpo'], $salvo['status'])
                    ->header('X-Idempotent-Replay', 'true');
            }

            $response = $next($request);

            // Erros de validação podem ser corrigidos e reenviados com a mesma chave.
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
                Cache::put($cacheKey, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'corpo' => json_decode($response->getContent(), true) ?? [],
                ], now()->addHours(24));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
