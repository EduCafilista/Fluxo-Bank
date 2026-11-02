<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use App\Services\CompraDebitoSimuladaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompraDebitoSimuladaController extends Controller
{
    public function __invoke(Request $request, Cartao $cartao, CompraDebitoSimuladaService $service): JsonResponse
    {
        if (! config('services.fluxo.simulacoes_financeiras', false)) {
            return response()->json(['erro' => 'Simulações financeiras desativadas.'], 403);
        }

        $conta = $request->user()->conta()->firstOrFail();
        abort_unless((int) $cartao->conta_id === (int) $conta->id, 404, 'Cartão não encontrado.');

        $dados = $request->validate([
            'valor_centavos' => 'required|integer|min:1|max:100000000',
            'estabelecimento' => 'required|string|min:2|max:120',
            'online' => 'sometimes|boolean',
        ]);
        $chave = $request->header('Idempotency-Key');
        if (! is_string($chave) || strlen($chave) < 8 || strlen($chave) > 200) {
            return response()->json(['erro' => 'Informe Idempotency-Key com 8 a 200 caracteres.'], 422);
        }

        $resultado = $service->comprar(
            $cartao, (int) $dados['valor_centavos'], trim($dados['estabelecimento']),
            $chave, (bool) ($dados['online'] ?? false)
        );
        return response()->json([
            'mensagem' => 'Compra de débito simulada aprovada.',
            'transacao_id' => $resultado['transacao']->id,
            'valor_centavos' => (int) $dados['valor_centavos'],
            'saldo_atual_centavos' => $conta->saldo(),
            'repetida' => $resultado['repetida'],
        ], $resultado['repetida'] ? 200 : 201);
    }
}
