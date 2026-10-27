<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Agendamento;
use App\Models\Conta;
use App\Models\Transacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Agendamento de transferências (RF06/RF25 + RN45).
 * Estados: AGENDADO → EXECUTADO | FALHOU | CANCELADO | EXPIRADO.
 * O worker (agendamentos:processar) só pega o que está AGENDADO.
 */
class AgendamentoService
{
    public function __construct(private TransferenciaService $transferencias) {}

    public function agendar(
        Conta $origem,
        Conta $destino,
        int $valorCentavos,
        Carbon|string $quando,
        ?string $descricao = null,
        string $tipo = Agendamento::TIPO_TED_INTERNA,
        string $chaveIdempotencia = '',
    ): Agendamento {
        $quando = $quando instanceof Carbon ? $quando : Carbon::parse($quando);

        if ($quando->isPast()) {
            throw new RegraDeNegocioException('A data do agendamento precisa estar no futuro.');
        }

        if ($valorCentavos <= 0) {
            throw new RegraDeNegocioException('O valor deve ser maior que zero.');
        }

        if ($origem->id === $destino->id) {
            throw new RegraDeNegocioException('Não é possível agendar para a mesma conta.');
        }

        $chaveIdempotencia = $chaveIdempotencia !== '' ? $chaveIdempotencia : 'AG-'.Str::uuid();

        if (Agendamento::where('chave_idempotencia', $chaveIdempotencia)->exists()) {
            return Agendamento::where('chave_idempotencia', $chaveIdempotencia)->firstOrFail();
        }

        $agendamento = Agendamento::create([
            'conta_id' => $origem->id,
            'tipo_operacao' => $tipo,
            'valor_centavos' => $valorCentavos,
            'agendado_para' => $quando,
            'payload_operacao' => [
                'conta_destino_id' => $destino->id,
                'descricao' => $descricao,
            ],
            'status' => Agendamento::STATUS_AGENDADO,
            'chave_idempotencia' => $chaveIdempotencia,
            'proxima_execucao' => $quando,
        ]);

        app(AuditoriaService::class)->registrar(
            'MOVIMENTACAO',
            'AGENDAMENTO_CRIADO',
            ['agendamento_id' => $agendamento->id, 'valor_centavos' => $valorCentavos, 'quando' => $quando->toIso8601String()],
            'agendamentos',
            (string) $agendamento->id,
        );

        return $agendamento;
    }

    public function cancelar(Agendamento $agendamento, Conta $conta): Agendamento
    {
        if ($agendamento->conta_id !== $conta->id) {
            throw new RegraDeNegocioException('Agendamento não pertence a esta conta.', 403);
        }

        if ($agendamento->status !== Agendamento::STATUS_AGENDADO) {
            throw new RegraDeNegocioException('Somente agendamentos AGENDADO podem ser cancelados.');
        }

        $agendamento->update(['status' => Agendamento::STATUS_CANCELADO]);

        return $agendamento->refresh();
    }

    /**
     * Executa um agendamento com lock e idempotência (RN45).
     * Chamado pelo worker. Devolve o status final.
     */
    public function executar(Agendamento $agendamento): string
    {
        return DB::transaction(function () use ($agendamento) {
            $travado = Agendamento::whereKey($agendamento->id)->lockForUpdate()->firstOrFail();

            // Só AGENDADO pode ser capturado (proteção contra dois workers)
            if ($travado->status !== Agendamento::STATUS_AGENDADO) {
                return $travado->status;
            }

            if (! $travado->agendado_para->isPast()) {
                return $travado->status;
            }

            if ($travado->tentativas >= Agendamento::MAX_TENTATIVAS) {
                $travado->update(['status' => Agendamento::STATUS_FALHOU, 'erro' => 'Máximo de tentativas atingido.']);

                return $travado->status;
            }

            $origem = Conta::find($travado->conta_id);
            $destino = Conta::find($travado->payload_operacao['conta_destino_id'] ?? 0);

            if (! $origem || ! $destino) {
                $travado->increment('tentativas');
                $travado->update(['status' => Agendamento::STATUS_FALHOU, 'erro' => 'Conta de origem/destino inexistente.']);

                return $travado->status;
            }

            try {
                $transacao = $this->transferencias->transferir(
                    $origem,
                    $destino,
                    $travado->valor_centavos,
                    $travado->payload_operacao['descricao'] ?? 'Transferência agendada',
                    Transacao::TIPO_TED_INTERNA,
                    'agendamento-'.$travado->id,
                );

                $travado->update([
                    'status' => Agendamento::STATUS_EXECUTADO,
                    'transacao_id' => $transacao->id,
                    'tentativas' => $travado->tentativas + 1,
                ]);

                return $travado->status;
            } catch (\Throwable $e) {
                $tentativas = $travado->tentativas + 1;

                $travado->update([
                    'tentativas' => $tentativas,
                    'status' => $tentativas >= Agendamento::MAX_TENTATIVAS
                        ? Agendamento::STATUS_FALHOU
                        : Agendamento::STATUS_AGENDADO,
                    'proxima_execucao' => now()->addMinutes(15),
                    'erro' => $e->getMessage(),
                ]);

                return $travado->status;
            }
        });
    }

    /** Marca como EXPIRADO o que passou muito da hora (NC: mais de 7 dias). */
    public function expirarAntigos(): int
    {
        return Agendamento::where('status', Agendamento::STATUS_AGENDADO)
            ->where('agendado_para', '<', now()->subDays(7))
            ->update(['status' => Agendamento::STATUS_EXPIRADO]);
    }
}
