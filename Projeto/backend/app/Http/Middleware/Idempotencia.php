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

        $cacheKey = 'idempotencia:'.sha1($request->user()?->id.'|'.$request->path().'|'.$chave);

        $salvo = Cache::get($cacheKey);

        if ($salvo) {
            return response()->json($salvo['corpo'], $salvo['status'])
                ->header('X-Idempotent-Replay', 'true');
        }

        $response = $next($request);

        if ($response->getStatusCode() < 500) {
            Cache::put($cacheKey, [
                'status' => $response->getStatusCode(),
                'corpo' => json_decode($response->getContent(), true) ?? [],
            ], now()->addHours(24));
        }

        return $response;
    }
}
