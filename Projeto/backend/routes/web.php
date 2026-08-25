<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Support\Facades\Route;
Route::get('/api/status', function () {
    return response()->json([
        'aplicacao' => 'Fluxo API',
        'status' => 'online',
        'ambiente' => env('APP_ENV')
    ]);
});

use Illuminate\Support\Facades\Route;
Route::get('/api/status', function () {
    return response()->json([
        'aplicacao' => 'Fluxo API',
        'status' => 'online',
        'ambiente' => env('APP_ENV')
    ]);
});
