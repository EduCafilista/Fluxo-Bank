<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApenasDoisFatoresConcluido
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token && in_array('2fa:pendente', (array) $token->abilities, true)) {
            return response()->json([
                'erro' => 'Conclua a verificação em dois fatores.',
                'precisa_2fa' => true,
            ], 403);
        }

        return $next($request);
    }
}
