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

<<<<<<< HEAD
use Illuminate\Support\Facades\Route;
Route::get('/api/status', function () {
    return response()->json([
        'aplicacao' => 'Fluxo API',
        'status' => 'online',
        'ambiente' => env('APP_ENV')
    ]);
});
=======
>>>>>>> e217f45e01ed74ee54a7c834faeb5061dee4afc3
