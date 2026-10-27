<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Conta;
use App\Services\AgendamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Agendamento de transferências (RF06/RF25). */
class AgendamentoController extends Controller
{
    public function __construct(private AgendamentoService $agendamentos) {}

    public function index(Request $request): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        return response()->json([
            'agendamentos' => $conta->agendamentos()->latest('id')->get()->map(fn (Agendamento $a) => $this->formatar($a)),
        ]);
    }

    /** POST /api/v1/agendamentos */
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'conta_destino_id' => 'required|integer|exists:contas,id',
            'valor_centavos' => 'required|integer|min:1',
            'agendado_para' => 'required|date|after:now',
            'descricao' => 'nullable|string|max:120',
        ], [
            'agendado_para.after' => 'A data do agendamento precisa estar no futuro.',
        ]);

        $conta = $request->user()->conta()->firstOrFail();

        $agendamento = $this->agendamentos->agendar(
            $conta,
            Conta::findOrFail($dados['conta_destino_id']),
            (int) $dados['valor_centavos'],
            $dados['agendado_para'],
            $dados['descricao'] ?? null,
            Agendamento::TIPO_TED_INTERNA,
            (string) ($request->header('Idempotency-Key') ?? ''),
        );

        return response()->json([
            'mensagem' => 'Transferência agendada.',
            'agendamento' => $this->formatar($agendamento),
        ], 201);
    }

    /** DELETE /api/v1/agendamentos/{agendamento} */
    public function cancelar(Request $request, Agendamento $agendamento): JsonResponse
    {
        $conta = $request->user()->conta()->firstOrFail();

        $agendamento = $this->agendamentos->cancelar($agendamento, $conta);

        return response()->json([
            'mensagem' => 'Agendamento cancelado.',
            'agendamento' => $this->formatar($agendamento),
        ]);
    }

    private function formatar(Agendamento $a): array
    {
        return [
            'id' => $a->id,
            'tipo_operacao' => $a->tipo_operacao,
            'valor_centavos' => $a->valor_centavos,
            'agendado_para' => $a->agendado_para?->toIso8601String(),
            'status' => $a->status,
            'tentativas' => $a->tentativas,
            'transacao_id' => $a->transacao_id,
            'erro' => $a->erro,
        ];
    }
}
