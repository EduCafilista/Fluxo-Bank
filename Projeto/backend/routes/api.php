<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CadastroController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\TransacaoController;
use Illuminate\Support\Facades\Route;

Route::post('/cadastro', [CadastroController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/conta', [ContaController::class, 'show']);
    Route::post('/transferencias', [TransacaoController::class, 'store']);
});
