<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Services\AuditoriaService;
use App\Services\ContaService;
use App\Services\PagamentosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositoSimuladoController extends Controller
{
    public function __invoke(Request $request, PagamentosService $pagamentos, ContaService $contas, AuditoriaService $auditoria): JsonResponse
    {
        $dados = $request->validate([
            'conta_destino_id' => 'required|integer|exists:contas,id',
            'valor_centavos' => 'required|integer|min:1|max:100000000',
            'descricao' => 'nullable|string|max:120',
        ]);

        $conta = Conta::findOrFail($dados['conta_destino_id']);
        $transacao = $pagamentos->depositar(
            $conta,
            (int) $dados['valor_centavos'],
            $dados['descricao'] ?? 'Depósito simulado',
            $request->header('Idempotency-Key'),
        );

        $auditoria->registrar('MOVIMENTACAO', 'DEPOSITO_SIMULADO', [
            'transacao_id' => $transacao->id,
            'conta_destino_id' => $conta->id,
            'valor_centavos' => (int) $dados['valor_centavos'],
        ], 'transacoes', (string) $transacao->id);

        return response()->json([
            'mensagem' => 'Depósito simulado registrado.',
            'simulado' => true,
            'transacao_id' => $transacao->id,
            'conta_destino_id' => $conta->id,
            'saldo_centavos' => $contas->saldo($conta->refresh()),
        ], 201);
    }
}
