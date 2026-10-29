<?php

namespace App\Http\Controllers;

use App\Services\ContaService;
use App\Services\LimitePixService;
use App\Services\PagamentosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PixController extends Controller
{
    public function __construct(
        private PagamentosService $pagamentos,
        private LimitePixService $limites,
        private ContaService $contas,
    ) {}

    public function cobranca(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'valor_centavos' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:120',
        ]);

        $conta = $request->user()->conta()->firstOrFail();

        return response()->json(
            $this->pagamentos->gerarCobrancaPix($conta, (int) $dados['valor_centavos'], $dados['descricao'] ?? null),
            201,
        );
    }

    public function receber(Request $request): JsonResponse
    {
        // Autorização e habilitação são verificadas pelo middleware da rota.
        $dados = $request->validate([
            'valor_centavos' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:120',
        ]);

        $conta = $request->user()->conta()->firstOrFail();

        $transacao = $this->pagamentos->receberPix(
            $conta,
            (int) $dados['valor_centavos'],
            $dados['descricao'] ?? null,
            $request->header('Idempotency-Key'),
        );

        return response()->json([
            'mensagem' => 'Recebimento registrado.',
            'transacao_id' => $transacao->id,
            'saldo_centavos' => $this->contas->saldo($conta->refresh()),
        ], 201);
    }

    public function limites(Request $request): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        return response()->json($this->limites->resumo($conta));
    }
}
