<?php

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$middlewaresWebDesnecessarios = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    ValidateCsrfToken::class,
];

// Documentação da API (RNF30)
Route::get('/docs/openapi.yaml', function () {
    $caminho = base_path('docs/openapi.yaml');

    if (! is_file($caminho) || ! is_readable($caminho)) {
        return response()->json(['erro' => 'Especificação OpenAPI não encontrada.'], 404);
    }

    return response((string) file_get_contents($caminho), 200, [
        'Content-Type' => 'application/yaml; charset=UTF-8',
    ]);
})->withoutMiddleware($middlewaresWebDesnecessarios)->name('openapi');

Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'status' => 'ok',
    'versao' => config('fluxo.api.versao'),
    'commit' => substr((string) env('RENDER_GIT_COMMIT', ''), 0, 7) ?: null,
    'health' => url('/api/v1/health'),
    'openapi' => url('/docs/openapi.yaml'),
]))->withoutMiddleware($middlewaresWebDesnecessarios);
