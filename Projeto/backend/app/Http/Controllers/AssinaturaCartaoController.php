<?php

namespace App\Http\Controllers;

use App\Models\AssinaturaCartao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssinaturaCartaoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        $consulta = AssinaturaCartao::where('conta_id', $conta->id);

        if ($request->filled('cartao_id')) {
            $consulta->where('cartao_id', (int) $request->query('cartao_id'));
        }

        return response()->json([
            'assinaturas_cartao' => $consulta->latest('id')->get(),
            'observacao' => 'Read model de recorrências do cartão; não substitui as assinaturas de planos (RN40).',
        ]);
    }

    public function cancelar(Request $request, AssinaturaCartao $assinaturaCartao): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        abort_unless($assinaturaCartao->conta_id === $conta->id, 404, 'Assinatura não encontrada.');

        $assinaturaCartao->update(['status' => 'CANCELADA']);

        return response()->json([
            'mensagem' => 'Recorrência marcada como cancelada (não apaga transações — RN40).',
        ]);
    }
}
