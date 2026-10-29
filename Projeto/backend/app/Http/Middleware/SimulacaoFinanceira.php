<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimulacaoFinanceira
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.fluxo.simulacoes_financeiras', false)) {
            return response()->json(['erro' => 'Simulações financeiras desativadas.'], 403);
        }

        $usuario = $request->user();
        if (! $usuario || ! $usuario->ehBackoffice() || $usuario->perfil !== 'ADMIN') {
            return response()->json(['erro' => 'Simulação restrita a administradores.'], 403);
        }

        return $next($request);
    }
}
