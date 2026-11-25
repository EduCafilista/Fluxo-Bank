<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class WebAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        if (! $user) {
            $request->session()->forget(['fluxo_api_token', 'fluxo_ultima_atividade']);

            return redirect()->route('web.login');
        }

        $agora = now();
        $ultima = (int) $request->session()->get('fluxo_ultima_atividade');
        $token = PersonalAccessToken::findToken((string) $request->session()->get('fluxo_api_token'));
        $tokenDoUsuario = $token?->tokenable_type === $user->getMorphClass()
            && (int) $token->tokenable_id === (int) $user->id;
        $expiracao = (int) config('sanctum.expiration');
        $expirou = $agora->timestamp - $ultima >= 900
            || $token?->expires_at?->lte($agora)
            || ($token && $expiracao > 0 && $token->created_at->lte($agora->copy()->subMinutes($expiracao)));

        if (! $tokenDoUsuario || $expirou || in_array('2fa:pendente', $token->abilities ?? [], true)) {
            if ($tokenDoUsuario) {
                $token->delete();
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('web.login')->with('erro', 'Sua sessão expirou. Entre novamente.');
        }

        $request->session()->put('fluxo_ultima_atividade', $agora->timestamp);

        return $next($request);
    }
}
