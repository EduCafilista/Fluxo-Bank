<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('web')?->perfil === User::PERFIL_ADMIN, 403);

        return $next($request);
    }
}
