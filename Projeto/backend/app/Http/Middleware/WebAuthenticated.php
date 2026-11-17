<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/** Sessão web independente das rotas Sanctum da API; limite de inatividade RNF11. */
class WebAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->route('web.login');
        }

        $agora = now()->timestamp;
        $ultima = (int) $request->session()->get('fluxo_ultima_atividade', $agora);
        if ($agora - $ultima > 900) {
            $token = PersonalAccessToken::findToken((string) $request->session()->get('fluxo_api_token', ''));
            if ($token && (int) $token->tokenable_id === (int) Auth::id()) $token->delete();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('web.login')->with('erro', 'Sua sessão expirou após 15 minutos de inatividade.');
        }

        $request->session()->put('fluxo_ultima_atividade', $agora);

        return $next($request);
    }
}
