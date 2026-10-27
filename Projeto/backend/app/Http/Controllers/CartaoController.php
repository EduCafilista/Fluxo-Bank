<?php

namespace App\Http\Controllers;

use App\Models\Cartao;
use App\Services\CartoesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Ciclo de vida dos cartões (RF07/RF12). */
class CartaoController extends Controller
{
    public function __construct(private CartoesService $cartoes) {}

    public function index(Request $request): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        $cartoes = $conta->cartoes()->with(['fisico', 'virtual'])->get();

        return response()->json([
            'cartoes' => $cartoes->map(fn (Cartao $c) => $this->formatar($c)),
        ]);
    }

    /** POST /api/v1/cartoes  { modalidade: DEBITO|CREDITO, endereco_entrega } */
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'modalidade' => 'required|in:DEBITO,CREDITO,debito,credito',
            'endereco_entrega' => 'nullable|string|max:190',
        ]);

        $conta = $request->user()->conta()->firstOrFail();

        $cartao = $this->cartoes->emitirFisico($conta, $dados['modalidade'], $dados['endereco_entrega'] ?? null);

        return response()->json([
            'mensagem' => 'Cartão físico emitido.',
            'cartao' => $this->formatar($cartao),
        ], 201);
    }

    /** POST /api/v1/cartoes/{cartao}/virtual (RN38) */
    public function gerarVirtual(Request $request, Cartao $cartao): JsonResponse
    {
        $this->garantirDono($request, $cartao);

        $virtual = $this->cartoes->gerarVirtual($cartao);

        return response()->json([
            'mensagem' => 'Cartão virtual gerado a partir do cartão físico.',
            'cartao' => $this->formatar($virtual->load('virtual')),
        ], 201);
    }

    /** PATCH /api/v1/cartoes/{cartao}/bloqueio  { bloquear: true|false } */
    public function bloqueio(Request $request, Cartao $cartao): JsonResponse
    {
        $this->garantirDono($request, $cartao);

        $dados = $request->validate(['bloquear' => 'required|boolean']);

        $cartao = $this->cartoes->bloqueio($cartao, (bool) $dados['bloquear']);

        return response()->json([
            'mensagem' => $dados['bloquear'] ? 'Cartão bloqueado.' : 'Cartão desbloqueado.',
            'cartao' => $this->formatar($cartao->load(['fisico', 'virtual'])),
        ]);
    }

    /** PATCH /api/v1/cartoes/{cartao}/limite  { limite_centavos } */
    public function limite(Request $request, Cartao $cartao): JsonResponse
    {
        $this->garantirDono($request, $cartao);

        $dados = $request->validate(['limite_centavos' => 'required|integer|min:0']);

        $cartao = $this->cartoes->ajustarLimite($cartao, (int) $dados['limite_centavos']);

        return response()->json([
            'mensagem' => 'Limite atualizado.',
            'cartao' => $this->formatar($cartao->load(['fisico', 'virtual'])),
        ]);
    }

    /** PATCH /api/v1/cartoes/{cartao}/compras-online  { permitir: true|false } (RN39) */
    public function comprasOnline(Request $request, Cartao $cartao): JsonResponse
    {
        $this->garantirDono($request, $cartao);

        $dados = $request->validate(['permitir' => 'required|boolean']);

        $fisico = $this->cartoes->definirComprasOnline($cartao, (bool) $dados['permitir']);

        return response()->json([
            'mensagem' => $dados['permitir']
                ? 'Compras on-line liberadas no cartão físico.'
                : 'Compras on-line bloqueadas no cartão físico.',
            'permite_compras_online' => $fisico->permite_compras_online,
        ]);
    }

    private function garantirDono(Request $request, Cartao $cartao): void
    {
        $conta = $request->user()->conta()->firstOrFail();

        abort_unless($cartao->conta_id === $conta->id, 404, 'Cartão não encontrado.');
    }

    private function formatar(Cartao $cartao): array
    {
        return [
            'id' => $cartao->id,
            'modalidade' => $cartao->modalidade,
            'numero_mascarado' => $cartao->numero_mascarado,
            'validade' => $cartao->validade?->format('m/Y'),
            'status' => $cartao->status,
            'limite_aprovado_centavos' => $cartao->limite_aprovado_centavos,
            'limite_atual_centavos' => $cartao->limite_atual_centavos,
            'dia_fechamento_fatura' => $cartao->dia_fechamento_fatura,
            'fisico' => $cartao->fisico ? [
                'data_emissao' => $cartao->fisico->data_emissao?->toDateString(),
                'permite_compras_online' => $cartao->fisico->permite_compras_online,
            ] : null,
            'virtual' => $cartao->virtual ? [
                'gerado_em' => $cartao->virtual->gerado_em?->toIso8601String(),
                'expira_em' => $cartao->virtual->expira_em?->toIso8601String(),
            ] : null,
        ];
    }
}
