<?php

namespace App\Http\Controllers;

use App\Models\CobrancaPix;
use App\Services\ConfiguracaoLimitePixService;
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
            'expiracao_minutos' => 'nullable|integer|between:1,1440',
        ]);

        $conta = $request->user()->conta()->firstOrFail();

        return response()->json(
            $this->pagamentos->gerarCobrancaPix($conta, (int) $dados['valor_centavos'],
                $dados['descricao'] ?? null, (int) ($dados['expiracao_minutos'] ?? 5)),
            201,
        );
    }

    public function listarCobrancas(Request $request): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();
        $paginas = CobrancaPix::where('conta_id', $conta->id)->latest('id')
            ->paginate(min(max((int) $request->query('per_page', 20), 1), 50));
        return response()->json($paginas->through(fn (CobrancaPix $c) => [
            'id' => $c->id, 'txid' => $c->txid, 'valor_centavos' => $c->valor_centavos,
            'descricao' => $c->descricao, 'situacao' => $c->situacao(),
            'criada_em' => $c->created_at?->toIso8601String(),
            'expira_em' => $c->expira_em->toIso8601String(),
            'payload_emv' => $c->situacao() === 'ATIVA' ? $c->payload_emv : null,
        ]));
    }

    public function cancelarCobranca(Request $request, CobrancaPix $cobranca): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();
        $this->pagamentos->cancelarCobrancaPix($conta, $cobranca);
        return response()->json(['mensagem' => 'Cobrança Pix cancelada.']);
    }

    public function revisarPagamento(Request $request): JsonResponse
    {
        $dados = $request->validate(['payload_emv' => 'required|string|max:512']);
        $conta = $request->user()->conta()->firstOrFail();

        return response()->json($this->pagamentos->revisarCobrancaPix($conta, $dados['payload_emv']));
    }

    public function pagarCobranca(Request $request): JsonResponse
    {
        $dados = $request->validate(['payload_emv' => 'required|string|max:512']);
        $conta = $request->user()->conta()->firstOrFail();
        $transacao = $this->pagamentos->pagarCobrancaPix($conta, $dados['payload_emv']);

        return response()->json(['mensagem' => 'Cobrança Pix paga.', 'transacao_id' => $transacao->id], 201);
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

    public function ajustarLimites(Request $request, ConfiguracaoLimitePixService $servico): JsonResponse
    {
        $dados = $request->validate([
            'por_transacao' => 'required|integer|min:1',
            'diario' => 'required|integer|min:1',
            'noturno' => 'required|integer|min:1',
        ]);
        $conta = $request->user()->conta()->firstOrFail();
        $servico->ajustar($conta, (int) $dados['por_transacao'], (int) $dados['diario'], (int) $dados['noturno']);

        return response()->json($this->limites->resumo($conta));
    }
}
