<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Conta;
use App\Services\AgendamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'tipo_operacao' => 'nullable|in:TED_INTERNA,BOLETO',
            'conta_destino_id' => 'required_unless:tipo_operacao,BOLETO|nullable|integer|exists:contas,id',
            'linha_digitavel' => 'required_if:tipo_operacao,BOLETO|nullable|string|max:70',
            'valor_centavos' => 'required|integer|min:1',
            'agendado_para' => 'required|date|after:now',
            'descricao' => 'nullable|string|max:120',
            'recorrente' => 'nullable|boolean',
        ], ['agendado_para.after' => 'A data do agendamento precisa estar no futuro.']);

        $conta = $request->user()->conta()->firstOrFail();
        $chave = (string) $request->header('Idempotency-Key', '');
        $valor = (int) $dados['valor_centavos'];
        $quando = $dados['agendado_para'];
        $boleto = ($dados['tipo_operacao'] ?? null) === Agendamento::TIPO_BOLETO;

        $agendamento = $boleto
            ? $this->agendamentos->agendarBoleto($conta, $dados['linha_digitavel'], $valor, $quando, $chave)
            : $this->agendamentos->agendar(
                $conta, Conta::findOrFail($dados['conta_destino_id']), $valor, $quando,
                $dados['descricao'] ?? null, chaveIdempotencia: $chave, recorrente: (bool) ($dados['recorrente'] ?? false),
            );

        return response()->json([
            'mensagem' => $boleto ? 'Pagamento agendado.' : 'Transferência agendada.',
            'agendamento' => $this->formatar($agendamento),
        ], 201);
    }

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
            'recorrente' => $a->recorrente,
            'proxima_execucao' => $a->proxima_execucao?->toIso8601String(),
            'transacao_id' => $a->transacao_id,
            'erro' => $a->erro,
        ];
    }
}
