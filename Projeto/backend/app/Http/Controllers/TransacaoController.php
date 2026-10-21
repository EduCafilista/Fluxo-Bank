<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Services\TransferenciaService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransacaoController extends Controller
{
    public function __construct(private TransferenciaService $transferencias)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'conta_destino_id' => 'required|integer|exists:contas,id',
            'valor_centavos' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:120',
        ]);

        try {
            $transacao = $this->transferencias->transferir(
                $request->user()->conta,
                Conta::findOrFail($dados['conta_destino_id']),
                $dados['valor_centavos'],
                $dados['descricao'] ?? null,
            );
        } catch (DomainException $e) {
            return response()->json(['erro' => $e->getMessage()], 422);
        }

        return response()->json($transacao->load('lancamentos'), 201);
    }
}
