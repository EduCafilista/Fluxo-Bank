<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ContextoAuditoria
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = (string) $request->user()?->id;
        $id = (string) $request->attributes->get('request_id');

        DB::select("select set_config('fluxo.usuario_id', ?, false), set_config('fluxo.request_id', ?, false)", [$usuario, $id]);

        try {
            return $next($request);
        } finally {
            DB::select("select set_config('fluxo.usuario_id', '', false), set_config('fluxo.request_id', '', false)");
        }
    }
}
