<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApenasBackoffice
{
    public function handle(Request $request, Closure $next, string $perfil = 'ATENDENTE'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->ehBackoffice()) {
            return response()->json(['erro' => 'Acesso restrito ao backoffice.'], 403);
        }

        if ($perfil === 'ADMIN' && $user->perfil !== 'ADMIN') {
            return response()->json(['erro' => 'Ação restrita ao perfil ADMIN.'], 403);
        }

        return $next($request);
    }
}
