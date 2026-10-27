<?php

use Illuminate\Support\Facades\Route;

/*
| A raiz confirma que o servidor está no ar e aponta a versão da API.
| A página inicial real (Blade + Tailwind) fica para a etapa do frontend web.
*/
// Documentação da API (RNF30)
Route::get('/docs/openapi.yaml', fn () => response()
    ->file(base_path('docs/openapi.yaml'), ['Content-Type' => 'application/yaml']))
    ->name('openapi');

Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'status' => 'ok',
    'versao' => config('fluxo.api.versao'),
    'health' => url('/api/v1/health'),
    'openapi' => url('/docs/openapi.yaml'),
]));
