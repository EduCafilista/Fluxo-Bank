<?php

use Illuminate\Support\Facades\Route;

// Página inicial: só confirma que o servidor está no ar
Route::get('/', fn () => response()->json(['app' => 'Fluxo', 'status' => 'ok']));
