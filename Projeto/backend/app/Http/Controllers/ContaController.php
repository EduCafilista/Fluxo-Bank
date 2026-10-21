<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContaController extends Controller
{
    // Saldo e últimos 50 lançamentos (RNF05: paginação de no máximo 50)
    public function show(Request $request): JsonResponse
    {
        $conta = $request->user()->conta;

        return response()->json([
            'numero' => $conta->numero,
            'saldo_centavos' => $conta->saldo(),
            'extrato' => $conta->lancamentos()->latest()->limit(50)->get(),
        ]);
    }
}
