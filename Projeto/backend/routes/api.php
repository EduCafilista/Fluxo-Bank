<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Rotas da API
|--------------------------------------------------------------------------
| A versão vigente fica em routes/api_v1.php e é publicada em /api/v1.
| Este arquivo mantém apenas compatibilidade: as rotas antigas (/api/login,
| /api/conta, ...) redirecionam para /api/v1, evitando quebrar clientes já
| integrados (o mobile do checkpoint anterior, por exemplo).
| RNF30: a API é versionada; o prefixo novo é /api/v1.
*/

Route::prefix('v1')->group(base_path('routes/api_v1.php'));

Route::any('{caminho?}', function () {
    $caminho = ltrim(request()->path() === 'api' ? '' : Str::after(request()->path(), 'api'), '/');
    $destino = '/api/v1/'.ltrim( (string) preg_replace('#^v1/?#', '', $caminho), '/');

    return response()->json([
        'erro' => 'Rota não versionada. Use a API v1.',
        'use' => url($destino),
        'versao' => 'v1',
    ], 308)->header('Location', url($destino));
})->where('caminho', '^(?!v1(?:/|$)).*')->name('compatibilidade.v1');
