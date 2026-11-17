<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebBackoffice
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('web')?->ehBackoffice(), 403, 'Acesso restrito à administração.');

        return $next($request);
    }
}
