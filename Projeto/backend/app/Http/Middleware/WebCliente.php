<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebCliente
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('web')?->perfil === 'CLIENTE', 403, 'Esta área é exclusiva de clientes.');

        return $next($request);
    }
}
