<?php

namespace App\Http\Controllers;

use App\Models\ChamadoSuporte;
use App\Models\Cliente;
use App\Models\Conta;
use App\Services\ContaService;
use App\Services\RecuperacaoSenhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackofficeController extends Controller
{
    public function __construct(
        private ContaService $contas,
        private RecuperacaoSenhaService $recuperacaoSenha,
    ) {}

    public function resumo(): JsonResponse
    {
        return response()->json([
            'clientes' => Cliente::count(),
            'contas_por_status' => Conta::groupBy('status')->selectRaw('status, count(*) as total')->pluck('total', 'status'),
            'contas_pendentes_kyc' => Conta::where('status_abertura', '!=', 'APROVADA')->count(),
            'transacoes_hoje' => DB::table('transacoes')->whereDate('created_at', now()->toDateString())->count(),
            'chamados_abertos' => ChamadoSuporte::where('status', 'ABERTO')->count(),
            'logs_auditoria' => DB::table('logs_auditoria')->count(),
        ]);
    }

    public function contas(Request $request): JsonResponse
    {
        $consulta = Conta::with(['cliente:id,tipo_cliente,nome,razao_social,cpf,cnpj,status', 'user:id,name,email']);

        if ($request->filled('status')) {
            $consulta->where('status', $request->query('status'));
        }

        if ($request->filled('status_abertura')) {
            $consulta->where('status_abertura', $request->query('status_abertura'));
        }

        return response()->json([
            'contas' => $consulta->latest('id')->paginate(50),
        ]);
    }

    public function aprovarKyc(Conta $conta): JsonResponse
    {
        return response()->json([
            'mensagem' => 'KYC aprovado: conta ativada.',
            'conta' => $this->contas->aprovarAbertura($conta),
        ]);
    }

    public function recusarKyc(Request $request, Conta $conta): JsonResponse
    {
        $dados = $request->validate(['motivo' => 'required|string|max:190']);

        return response()->json([
            'mensagem' => 'KYC recusado.',
            'conta' => $this->contas->recusarAbertura($conta, $dados['motivo']),
        ]);
    }

    public function bloquearPorFraude(Request $request, Conta $conta): JsonResponse
    {
        $dados = $request->validate(['motivo' => 'required|string|max:190']);

        return response()->json([
            'mensagem' => 'Conta bloqueada por suspeita de fraude.',
            'conta' => $this->contas->bloquearPorFraude($conta, $dados['motivo']),
        ]);
    }

    public function chamados(): JsonResponse
    {
        return response()->json([
            'chamados' => ChamadoSuporte::with('user:id,name,email')->latest('id')->paginate(50),
        ]);
    }

    public function responderChamado(Request $request, ChamadoSuporte $chamado): JsonResponse
    {
        $dados = $request->validate([
            'resposta' => 'required|string',
            'status' => 'nullable|in:EM_ATENDIMENTO,RESOLVIDO',
        ]);

        $chamado->update([
            'resposta' => $dados['resposta'],
            'status' => $dados['status'] ?? ChamadoSuporte::STATUS_RESOLVIDO,
            'atendente_id' => $request->user()->id,
            'respondido_em' => now(),
        ]);

        return response()->json(['mensagem' => 'Chamado respondido.', 'chamado' => $chamado->refresh()]);
    }

    public function gerarCodigoRecuperacaoSenha(Request $request, ?Conta $conta = null): JsonResponse
    {
        if (! $conta || ! $conta->exists) {
            $identificador = (string) ($request->input('identificador')
                ?? $request->input('cpf')
                ?? $request->input('email')
                ?? $request->input('telefone')
                ?? '');

            if ($identificador !== '') {
                $conta = $this->recuperacaoSenha->localizarContaPorIdentificador($identificador);
            } elseif ($request->filled('conta_id')) {
                $conta = Conta::find((int) $request->input('conta_id'));
            }

            if (! $conta) {
                return response()->json(['erro' => 'Conta não encontrada para o identificador informado.'], 404);
            }
        }

        $dados = $this->recuperacaoSenha->gerarCodigoParaConta($conta, $request->user());

        return response()->json([
            'mensagem' => 'Código de recuperação gerado para entrega manual ao titular.',
        ] + $dados, 201);
    }
}
