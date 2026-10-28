<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransacaoResource;
use App\Models\Conta;
use App\Models\Transacao;
use App\Services\RelatorioService;
use App\Services\TransferenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransacaoController extends Controller
{
    public function __construct(private TransferenciaService $transferencias) {}

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'conta_destino_id' => 'required_without:chave_pix|integer|exists:contas,id',
            'chave_pix' => 'required_without:conta_destino_id|string|max:120',
            'valor_centavos' => 'required|integer|min:1',
            'descricao' => 'nullable|string|max:120',
        ], [
            'conta_destino_id.required_without' => 'Informe a conta de destino ou uma chave Pix.',
            'chave_pix.required_without' => 'Informe a conta de destino ou uma chave Pix.',
            'conta_destino_id.exists' => 'Conta de destino inexistente.',
            'valor_centavos.required' => 'Informe o valor.',
            'valor_centavos.integer' => 'O valor deve estar em centavos (número inteiro).',
            'valor_centavos.min' => 'O valor deve ser maior que zero.',
        ]);

        $origem = $request->user()->conta()->firstOrFail();
        $idempotencia = $request->header('Idempotency-Key');

        if (! empty($dados['chave_pix'])) {
            $transacao = $this->transferencias->transferirPorChavePix(
                $origem,
                $dados['chave_pix'],
                (int) $dados['valor_centavos'],
                $dados['descricao'] ?? null,
                $idempotencia,
            );
        } else {
            $transacao = $this->transferencias->transferir(
                $origem,
                Conta::findOrFail($dados['conta_destino_id']),
                (int) $dados['valor_centavos'],
                $dados['descricao'] ?? null,
                Transacao::TIPO_TED_INTERNA,
                $idempotencia,
            );
        }

        return response()->json(new TransacaoResource($transacao), 201)
            ->header('X-Saldo-Centavos', (string) $origem->refresh()->saldo());
    }

    public function show(Request $request, Transacao $transacao): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        $pertence = $transacao->lancamentos()->where('conta_id', $conta->id)->exists();

        abort_unless($pertence || $request->user()->ehBackoffice(), 404, 'Transação não encontrada.');

        return response()->json(app(RelatorioService::class)->comprovante($transacao));
    }

    public function index(Request $request): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        $transacoes = Transacao::whereHas('lancamentos', fn ($q) => $q->where('conta_id', $conta->id))
            ->with('lancamentos')
            ->latest('id')
            ->paginate(min((int) $request->query('per_page', 20), 50));

        return response()->json([
            'pagina' => $transacoes->currentPage(),
            'total' => $transacoes->total(),
            'transacoes' => TransacaoResource::collection($transacoes->items()),
        ]);
    }
}
