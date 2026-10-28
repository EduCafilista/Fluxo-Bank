<?php

namespace App\Http\Controllers;

use App\Models\Assinatura;
use App\Models\Item;
use App\Models\Plano;
use App\Services\AssinaturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssinaturaController extends Controller
{
    public function __construct(private AssinaturaService $assinaturas) {}

    public function planos(): JsonResponse
    {
        return response()->json([
            'planos' => Plano::where('ativo', true)->get(),
            'itens' => Item::all(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $cliente = $request->user()->cliente;

        abort_unless($cliente, 404, 'Cliente não encontrado.');

        return response()->json([
            'assinaturas' => $cliente->assinaturas()->with(['plano', 'itens.item', 'cobrancas'])->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'plano_id' => 'required|integer|exists:planos,id',
            'dia_cobranca' => 'nullable|integer|min:1|max:28',
        ]);

        $cliente = $request->user()->cliente;

        abort_unless($cliente, 404, 'Cliente não encontrado.');

        $assinatura = $this->assinaturas->contratar(
            $cliente,
            Plano::findOrFail($dados['plano_id']),
            $dados['dia_cobranca'] ?? null,
        );

        return response()->json([
            'mensagem' => 'Plano contratado.',
            'assinatura' => $assinatura->load(['plano', 'cobrancas']),
        ], 201);
    }

    public function adicionarItem(Request $request, Assinatura $assinatura): JsonResponse
    {
        $dados = $request->validate(['item_id' => 'required|integer|exists:itens,id']);

        $this->garantirDono($request, $assinatura);

        $vinculo = $this->assinaturas->adicionarItem($assinatura, Item::findOrFail($dados['item_id']));

        return response()->json([
            'mensagem' => 'Item adicionado.',
            'item_assinatura_id' => $vinculo->id,
            'valor_atual_centavos' => $assinatura->refresh()->valor_atual_centavos,
        ], 201);
    }

    public function removerItem(Request $request, Assinatura $assinatura, Item $item): JsonResponse
    {
        $dados = $request->validate(['motivo' => 'required|string|max:190']);

        $this->garantirDono($request, $assinatura);

        $this->assinaturas->removerItem($assinatura, $item, $dados['motivo']);

        return response()->json([
            'mensagem' => 'Item retirado (exclusão lógica, RN22).',
            'valor_atual_centavos' => $assinatura->refresh()->valor_atual_centavos,
        ]);
    }

    public function cancelar(Request $request, Assinatura $assinatura): JsonResponse
    {
        $this->garantirDono($request, $assinatura);

        return response()->json([
            'mensagem' => 'Assinatura cancelada.',
            'assinatura' => $this->assinaturas->cancelar($assinatura),
        ]);
    }

    private function garantirDono(Request $request, Assinatura $assinatura): void
    {
        abort_unless($assinatura->cliente_id === $request->user()->cliente?->id, 404, 'Assinatura não encontrada.');
    }
}
